<?php

declare(strict_types=1);

namespace App\Repositories;

final class AiWalletRepository
{
    // id=1 → sandbox, id=2 → production
    private const ENV_IDS = ['sandbox' => 1, 'production' => 2];

    public function __construct(
        private readonly \PDO $pdo,
        private readonly string $environment = 'sandbox'
    ) {}

    private function rowId(): int
    {
        return self::ENV_IDS[$this->environment] ?? 1;
    }

    /** @return array<string,mixed> */
    public function getOrCreate(): array
    {
        $id = $this->rowId();
        $stmt = $this->pdo->prepare("SELECT * FROM ai_wallet WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            return $row;
        }

        $this->pdo->prepare(
            "INSERT INTO ai_wallet (id, environment, balance_brl) VALUES (:id, :env, 0.0000)"
        )->execute(['id' => $id, 'env' => $this->environment]);

        $stmt = $this->pdo->prepare("SELECT * FROM ai_wallet WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: [];
    }

    /**
     * Atomically updates the balance.
     * Property 3: Never read-then-write — uses SQL expression.
     */
    public function updateBalance(float $delta): void
    {
        $this->pdo->prepare(
            "UPDATE ai_wallet SET balance_brl = balance_brl + :delta, updated_at = NOW() WHERE id = :id"
        )->execute(['delta' => $delta, 'id' => $this->rowId()]);
    }

    public function saveCardToken(string $customerId, string $cardToken, string $last4): void
    {
        $this->getOrCreate();
        $this->pdo->prepare(
            "UPDATE ai_wallet SET asaas_customer_id = :cid, asaas_card_token = :tok, asaas_card_last4 = :last4, updated_at = NOW() WHERE id = :id"
        )->execute(['cid' => $customerId, 'tok' => $cardToken, 'last4' => $last4, 'id' => $this->rowId()]);
    }

    public function saveRechargeConfig(bool $enabled, float $threshold, float $amount): void
    {
        $this->getOrCreate();
        $this->pdo->prepare(
            "UPDATE ai_wallet SET auto_recharge_enabled = :en, auto_recharge_threshold_brl = :thr, auto_recharge_amount_brl = :amt, updated_at = NOW() WHERE id = :id"
        )->execute(['en' => $enabled ? 1 : 0, 'thr' => $threshold, 'amt' => $amount, 'id' => $this->rowId()]);
    }

    /**
     * Resets the wallet balance and clears all transactions for this environment.
     * Used to clean up sandbox data before switching to production.
     */
    public function resetForEnvironment(\PDO $pdo): void
    {
        $id = $this->rowId();
        $env = $this->environment;

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE ai_wallet SET balance_brl = 0.0000, asaas_customer_id = NULL, asaas_card_token = NULL, asaas_card_last4 = NULL, updated_at = NOW() WHERE id = :id"
            )->execute(['id' => $id]);

            $pdo->prepare(
                "DELETE FROM ai_wallet_transactions WHERE environment = :env"
            )->execute(['env' => $env]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
