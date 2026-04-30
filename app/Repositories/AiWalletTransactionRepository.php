<?php

declare(strict_types=1);

namespace App\Repositories;

final class AiWalletTransactionRepository
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly string $environment = 'sandbox'
    ) {}

    /**
     * @param array<string,mixed> $data
     */
    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO ai_wallet_transactions
                (environment, type, amount_brl, balance_after_brl, description, clinic_id, audio_note_id, payment_id, duration_seconds, created_at)
            VALUES
                (:environment, :type, :amount_brl, :balance_after_brl, :description, :clinic_id, :audio_note_id, :payment_id, :duration_seconds, NOW())
        ");

        $stmt->execute([
            'environment'       => $this->environment,
            'type'              => $data['type'],
            'amount_brl'        => $data['amount_brl'],
            'balance_after_brl' => $data['balance_after_brl'],
            'description'       => $data['description'] ?? '',
            'clinic_id'         => $data['clinic_id'] ?? null,
            'audio_note_id'     => $data['audio_note_id'] ?? null,
            'payment_id'        => $data['payment_id'] ?? null,
            'duration_seconds'  => $data['duration_seconds'] ?? null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** Idempotency check — scoped to environment */
    public function findByPaymentId(string $paymentId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM ai_wallet_transactions WHERE payment_id = :pid AND type = 'credit' AND environment = :env LIMIT 1"
        );
        $stmt->execute(['pid' => $paymentId, 'env' => $this->environment]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Auto-recharge dedup — scoped to environment, ignores stale pending charges older than 1 hour */
    public function hasPendingCharge(): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM ai_wallet_transactions 
             WHERE type = 'charge_pending' 
               AND environment = :env 
               AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
             LIMIT 1"
        );
        $stmt->execute(['env' => $this->environment]);
        return (bool)$stmt->fetchColumn();
    }

    /** @return list<array<string,mixed>> */
    public function list(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM ai_wallet_transactions WHERE environment = :env ORDER BY id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset
        );
        $stmt->execute(['env' => $this->environment]);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed> Aggregates for a given YYYY-MM period, scoped to environment */
    public function statsForPeriod(string $yearMonth): array
    {
        $from = $yearMonth . '-01';
        $to = date('Y-m-t', strtotime($from));

        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(CASE WHEN type = 'debit' THEN 1 END) AS transcription_count,
                COALESCE(SUM(CASE WHEN type = 'debit' THEN duration_seconds ELSE 0 END), 0) AS total_seconds,
                COALESCE(SUM(CASE WHEN type = 'debit' THEN amount_brl ELSE 0 END), 0) AS total_charged_brl,
                COALESCE(SUM(CASE WHEN type = 'credit' THEN amount_brl ELSE 0 END), 0) AS total_credited_brl
            FROM ai_wallet_transactions
            WHERE environment = :env AND DATE(created_at) BETWEEN :from AND :to
        ");
        $stmt->execute(['env' => $this->environment, 'from' => $from, 'to' => $to]);
        return $stmt->fetch() ?: [];
    }

    /** @return array<string,mixed> Lifetime aggregates, scoped to environment */
    public function statsTotal(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(CASE WHEN type = 'debit' THEN 1 END) AS transcription_count,
                COALESCE(SUM(CASE WHEN type = 'debit' THEN duration_seconds ELSE 0 END), 0) AS total_seconds,
                COALESCE(SUM(CASE WHEN type = 'debit' THEN amount_brl ELSE 0 END), 0) AS total_charged_brl,
                COALESCE(SUM(CASE WHEN type = 'credit' THEN amount_brl ELSE 0 END), 0) AS total_credited_brl
            FROM ai_wallet_transactions
            WHERE environment = :env
        ");
        $stmt->execute(['env' => $this->environment]);
        return $stmt->fetch() ?: [];
    }
}
