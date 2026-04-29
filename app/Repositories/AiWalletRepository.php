<?php

declare(strict_types=1);

namespace App\Repositories;

final class AiWalletRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed> */
    public function getOrCreate(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ai_wallet WHERE id = 1 LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row) {
            return $row;
        }

        $this->pdo->exec("INSERT INTO ai_wallet (id, balance_brl) VALUES (1, 0.0000)");

        $stmt = $this->pdo->prepare("SELECT * FROM ai_wallet WHERE id = 1 LIMIT 1");
        $stmt->execute();
        return $stmt->fetch() ?: [];
    }

    /**
     * Atomically updates the balance.
     * Property 3: Never read-then-write — uses SQL expression.
     */
    public function updateBalance(float $delta): void
    {
        $this->pdo->prepare(
            "UPDATE ai_wallet SET balance_brl = balance_brl + :delta, updated_at = NOW() WHERE id = 1"
        )->execute(['delta' => $delta]);
    }

    public function saveCardToken(string $customerId, string $cardToken, string $last4): void
    {
        $this->getOrCreate();
        $this->pdo->prepare(
            "UPDATE ai_wallet SET asaas_customer_id = :cid, asaas_card_token = :tok, asaas_card_last4 = :last4, updated_at = NOW() WHERE id = 1"
        )->execute(['cid' => $customerId, 'tok' => $cardToken, 'last4' => $last4]);
    }

    public function saveRechargeConfig(bool $enabled, float $threshold, float $amount): void
    {
        $this->getOrCreate();
        $this->pdo->prepare(
            "UPDATE ai_wallet SET auto_recharge_enabled = :en, auto_recharge_threshold_brl = :thr, auto_recharge_amount_brl = :amt, updated_at = NOW() WHERE id = 1"
        )->execute(['en' => $enabled ? 1 : 0, 'thr' => $threshold, 'amt' => $amount]);
    }
}
