<?php

declare(strict_types=1);

namespace App\Repositories;

final class AccountsPayableRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(
        int $clinicId,
        ?string $vendorName,
        string $title,
        ?string $description,
        ?int $costCenterId,
        string $payableType,
        string $status,
        string $startDueDate,
        ?int $totalInstallments,
        ?string $recurrenceInterval,
        ?string $recurrenceUntil,
        ?int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO accounts_payable (
                clinic_id,
                vendor_name, title, description,
                cost_center_id,
                payable_type, status,
                start_due_date,
                total_installments,
                recurrence_interval,
                recurrence_until,
                created_by_user_id,
                created_at
            ) VALUES (
                :clinic_id,
                :vendor_name, :title, :description,
                :cost_center_id,
                :payable_type, :status,
                :start_due_date,
                :total_installments,
                :recurrence_interval,
                :recurrence_until,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'vendor_name' => ($vendorName === '' ? null : $vendorName),
            'title' => $title,
            'description' => ($description === '' ? null : $description),
            'cost_center_id' => $costCenterId,
            'payable_type' => $payableType,
            'status' => $status,
            'start_due_date' => $startDueDate,
            'total_installments' => $totalInstallments,
            'recurrence_interval' => ($recurrenceInterval === '' ? null : $recurrenceInterval),
            'recurrence_until' => ($recurrenceUntil === '' ? null : $recurrenceUntil),
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id,
                   vendor_name, title, description,
                   cost_center_id,
                   payable_type, status,
                   start_due_date,
                   total_installments,
                   recurrence_interval,
                   recurrence_until,
                   created_by_user_id,
                   created_at, updated_at
            FROM accounts_payable
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
}
