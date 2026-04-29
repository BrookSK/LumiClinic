<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Core\Container\Container;
use App\Repositories\AiBillingSettingsRepository;
use App\Repositories\AiWalletRepository;
use App\Repositories\AiWalletTransactionRepository;

/**
 * All wallet mutations go through this service.
 * Balance updates are always atomic SQL expressions — never read-then-write.
 */
final class AiWalletService
{
    public function __construct(private readonly Container $container) {}

    private function walletRepo(): AiWalletRepository
    {
        return new AiWalletRepository($this->container->get(\PDO::class));
    }

    private function txRepo(): AiWalletTransactionRepository
    {
        return new AiWalletTransactionRepository($this->container->get(\PDO::class));
    }

    private function settingsRepo(): AiBillingSettingsRepository
    {
        return new AiBillingSettingsRepository($this->container->get(\PDO::class));
    }

    /** @return array<string,mixed> */
    public function getOrCreate(): array
    {
        return $this->walletRepo()->getOrCreate();
    }

    /**
     * Credits the wallet (called by webhook processor or manual credit).
     * Property 8: Atomically increments balance.
     *
     * @param string $type  'credit' | 'manual_credit'
     */
    public function credit(float $amountBrl, string $type, string $description, ?string $paymentId = null): void
    {
        if ($amountBrl <= 0) {
            throw new \RuntimeException('O valor deve ser maior que zero.');
        }

        $pdo = $this->container->get(\PDO::class);
        $walletRepo = new AiWalletRepository($pdo);
        $txRepo = new AiWalletTransactionRepository($pdo);

        $pdo->beginTransaction();
        try {
            $walletRepo->updateBalance($amountBrl);
            $wallet = $walletRepo->getOrCreate();
            $balanceAfter = (float)$wallet['balance_brl'];

            $txRepo->insert([
                'type'              => $type,
                'amount_brl'        => $amountBrl,
                'balance_after_brl' => $balanceAfter,
                'description'       => $description,
                'payment_id'        => $paymentId,
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Saves card tokenization result. Never stores raw card data.
     * Property 5: Only asaas_customer_id, asaas_card_token, and last4 are persisted.
     */
    public function saveCardToken(string $customerId, string $cardToken, string $last4): void
    {
        $this->walletRepo()->saveCardToken($customerId, $cardToken, $last4);
    }

    public function saveRechargeConfig(bool $enabled, float $thresholdBrl, float $amountBrl): void
    {
        $this->walletRepo()->saveRechargeConfig($enabled, $thresholdBrl, $amountBrl);
    }

    /** @return list<array<string,mixed>> */
    public function listTransactions(int $limit = 50, int $offset = 0): array
    {
        return $this->txRepo()->list($limit, $offset);
    }

    /**
     * Debits the wallet for a transcription.
     * Property 2: amount = ceil(durationSeconds / 60) * pricePerMinute
     * Property 3: Atomic decrement; negative balance allowed.
     * Only called when AiKeyResolverService::resolve() returns source === 'wallet'.
     */
    public function debitForTranscription(
        int $clinicId,
        int $audioNoteId,
        int $durationSeconds,
        int $fileSizeBytes = 0
    ): void {
        $settings = $this->settingsRepo()->getOrCreate();
        $pricePerMinute = (float)($settings['price_per_minute_brl'] ?? 0.0910);

        if ($pricePerMinute <= 0) {
            error_log('[AiWallet] price_per_minute_brl is 0 — skipping debit for audio_note_id=' . $audioNoteId);
            return;
        }

        // Estimate duration from file size if not provided
        if ($durationSeconds <= 0 && $fileSizeBytes > 0) {
            $durationSeconds = (int)ceil($fileSizeBytes / 16000); // 128 kbps = 16000 bytes/sec
            error_log('[AiWallet] Estimated duration from file size: ' . $durationSeconds . 's for audio_note_id=' . $audioNoteId);
        }

        if ($durationSeconds <= 0) {
            $durationSeconds = 60; // fallback: charge 1 minute minimum
            error_log('[AiWallet] Duration unknown, charging 1 minute minimum for audio_note_id=' . $audioNoteId);
        }

        $minutes = (int)ceil($durationSeconds / 60);
        $amount = round($minutes * $pricePerMinute, 4);

        $pdo = $this->container->get(\PDO::class);
        $walletRepo = new AiWalletRepository($pdo);
        $txRepo = new AiWalletTransactionRepository($pdo);

        $pdo->beginTransaction();
        try {
            $walletRepo->updateBalance(-$amount);
            $wallet = $walletRepo->getOrCreate();
            $balanceAfter = (float)$wallet['balance_brl'];

            $txRepo->insert([
                'type'              => 'debit',
                'amount_brl'        => $amount,
                'balance_after_brl' => $balanceAfter,
                'description'       => 'Transcrição de áudio — ' . $minutes . ' min',
                'clinic_id'         => $clinicId,
                'audio_note_id'     => $audioNoteId,
                'duration_seconds'  => $durationSeconds,
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        // Check auto-recharge after debit (outside transaction)
        try {
            $freshWallet = $walletRepo->getOrCreate();
            $balance = (float)$freshWallet['balance_brl'];
            $threshold = (float)$freshWallet['auto_recharge_threshold_brl'];
            $autoEnabled = (bool)$freshWallet['auto_recharge_enabled'];
            $cardToken = trim((string)($freshWallet['asaas_card_token'] ?? ''));

            if ($autoEnabled && $balance < $threshold && $cardToken !== '') {
                $rechargeAmount = (float)$freshWallet['auto_recharge_amount_brl'];
                $this->triggerRecharge($rechargeAmount);
            }
        } catch (\Throwable $e) {
            error_log('[AiWallet] Auto-recharge check failed: ' . $e->getMessage());
        }
    }

    /**
     * Creates a charge_pending transaction and calls AsaasAiClient to create a payment.
     * Property 7: Checks for existing pending charge before creating a new one.
     */
    public function triggerRecharge(float $amountBrl): void
    {
        if ($amountBrl <= 0) {
            throw new \RuntimeException('Valor de recarga inválido.');
        }

        $txRepo = $this->txRepo();

        // Property 7: Dedup — abort if pending charge already exists
        if ($txRepo->hasPendingCharge()) {
            error_log('[AiWallet] Pending charge already exists — skipping auto-recharge');
            return;
        }

        $wallet = $this->walletRepo()->getOrCreate();
        $customerId = trim((string)($wallet['asaas_customer_id'] ?? ''));
        $cardToken = trim((string)($wallet['asaas_card_token'] ?? ''));

        if ($customerId === '' || $cardToken === '') {
            throw new \RuntimeException('Cartão de crédito não configurado na carteira.');
        }

        // Insert charge_pending BEFORE calling Asaas API
        $pendingId = $txRepo->insert([
            'type'              => 'charge_pending',
            'amount_brl'        => $amountBrl,
            'balance_after_brl' => (float)$wallet['balance_brl'],
            'description'       => 'Recarga automática — R$ ' . number_format($amountBrl, 2, ',', '.'),
        ]);

        try {
            $asaas = new AsaasAiClient($this->container);
            $payment = $asaas->createCharge(
                $customerId,
                $cardToken,
                $amountBrl,
                'Recarga Carteira de IA — LumiClinic'
            );

            // Update the pending transaction with the payment_id
            $pdo = $this->container->get(\PDO::class);
            $pdo->prepare("UPDATE ai_wallet_transactions SET payment_id = :pid WHERE id = :id")
                ->execute(['pid' => $payment['id'] ?? null, 'id' => $pendingId]);
        } catch (\Throwable $e) {
            // Remove orphaned charge_pending on failure
            $pdo = $this->container->get(\PDO::class);
            $pdo->prepare("DELETE FROM ai_wallet_transactions WHERE id = :id AND type = 'charge_pending'")
                ->execute(['id' => $pendingId]);
            throw $e;
        }
    }
}
