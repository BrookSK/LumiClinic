<?php

declare(strict_types=1);

namespace App\Repositories;

final class PaymentRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array{paid_total:float} */
    public function summarizePaidByPatient(int $clinicId, int $patientId): array
    {
        $sql = "
            SELECT COALESCE(SUM(p.amount), 0) AS paid_total
            FROM payments p
            INNER JOIN sales s
                    ON s.id = p.sale_id
                   AND s.clinic_id = p.clinic_id
                   AND s.deleted_at IS NULL
            WHERE p.clinic_id = :clinic_id
              AND s.patient_id = :patient_id
              AND p.deleted_at IS NULL
              AND p.status = 'paid'
              AND s.status <> 'cancelled'
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);
        $row = $stmt->fetch() ?: [];

        return ['paid_total' => (float)($row['paid_total'] ?? 0)];
    }

    /** @return list<array<string, mixed>> */
    public function listBySale(int $clinicId, int $saleId): array
    {
        $sql = "
            SELECT id, clinic_id, sale_id,
                   method, amount, status, fees, gateway_ref,
                   paid_at,
                   created_by_user_id,
                   created_at, updated_at
            FROM payments
            WHERE clinic_id = :clinic_id
              AND sale_id = :sale_id
              AND deleted_at IS NULL
            ORDER BY id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'sale_id' => $saleId]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, sale_id,
                   method, amount, status, fees, gateway_ref,
                   paid_at,
                   created_by_user_id,
                   created_at, updated_at
            FROM payments
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
        int $saleId,
        string $method,
        string $amount,
        string $status,
        string $fees,
        ?string $gatewayRef,
        ?string $paidAt,
        int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO payments (
                clinic_id, sale_id,
                method, amount, status, fees, gateway_ref,
                paid_at,
                created_by_user_id,
                created_at
            )
            VALUES (
                :clinic_id, :sale_id,
                :method, :amount, :status, :fees, :gateway_ref,
                :paid_at,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'sale_id' => $saleId,
            'method' => $method,
            'amount' => $amount,
            'status' => $status,
            'fees' => $fees,
            'gateway_ref' => ($gatewayRef === '' ? null : $gatewayRef),
            'paid_at' => ($paidAt === '' ? null : $paidAt),
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $clinicId, int $id, string $status, ?string $paidAt): void
    {
        $sql = "
            UPDATE payments
            SET status = :status,
                paid_at = :paid_at,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
            'status' => $status,
            'paid_at' => ($paidAt === '' ? null : $paidAt),
        ]);
    }
}
