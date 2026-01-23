<?php

declare(strict_types=1);

namespace App\Repositories;

final class FinancialEntryRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByClinicRange(int $clinicId, string $fromDate, string $toDate, int $limit = 300, int $offset = 0): array
    {
        $offset = max(0, $offset);
        $sql = "
            SELECT
                id, clinic_id, kind, occurred_on,
                amount, method, status,
                cost_center_id, sale_id, payment_id,
                description,
                created_by_user_id,
                created_at, updated_at
            FROM financial_entries
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND occurred_on BETWEEN :from_date AND :to_date
            ORDER BY occurred_on DESC, id DESC
            LIMIT " . (int)$limit . "
            OFFSET " . (int)$offset . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array{in_total:float,out_total:float} */
    public function summarizeTotalsByClinicRange(int $clinicId, string $fromDate, string $toDate): array
    {
        $sql = "
            SELECT
                COALESCE(SUM(CASE WHEN kind = 'in' THEN amount ELSE 0 END), 0) AS in_total,
                COALESCE(SUM(CASE WHEN kind = 'out' THEN amount ELSE 0 END), 0) AS out_total
            FROM financial_entries
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND occurred_on BETWEEN :from_date AND :to_date
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
        $row = $stmt->fetch() ?: [];

        return [
            'in_total' => (float)($row['in_total'] ?? 0),
            'out_total' => (float)($row['out_total'] ?? 0),
        ];
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT
                id, clinic_id, kind, occurred_on,
                amount, method, status,
                cost_center_id, sale_id, payment_id,
                description,
                created_by_user_id,
                created_at, updated_at
            FROM financial_entries
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(
        int $clinicId,
        string $kind,
        string $occurredOn,
        string $amount,
        ?string $method,
        string $status,
        ?int $costCenterId,
        ?int $saleId,
        ?int $paymentId,
        ?string $description,
        int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO financial_entries (
                clinic_id, kind, occurred_on,
                amount, method, status,
                cost_center_id, sale_id, payment_id,
                description,
                created_by_user_id,
                created_at
            )
            VALUES (
                :clinic_id, :kind, :occurred_on,
                :amount, :method, :status,
                :cost_center_id, :sale_id, :payment_id,
                :description,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'kind' => $kind,
            'occurred_on' => $occurredOn,
            'amount' => $amount,
            'method' => ($method === '' ? null : $method),
            'status' => $status,
            'cost_center_id' => $costCenterId,
            'sale_id' => $saleId,
            'payment_id' => $paymentId,
            'description' => ($description === '' ? null : $description),
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE financial_entries
            SET deleted_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
    }
}
