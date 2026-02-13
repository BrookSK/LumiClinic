<?php

declare(strict_types=1);

namespace App\Repositories;

final class AccountsPayableInstallmentRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(
        int $clinicId,
        int $payableId,
        int $installmentNo,
        string $dueDate,
        string $amount,
        string $status
    ): int {
        $sql = "
            INSERT INTO accounts_payable_installments (
                clinic_id, payable_id,
                installment_no, due_date, amount,
                status,
                created_at
            ) VALUES (
                :clinic_id, :payable_id,
                :installment_no, :due_date, :amount,
                :status,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'payable_id' => $payableId,
            'installment_no' => $installmentNo,
            'due_date' => $dueDate,
            'amount' => $amount,
            'status' => $status,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function listByClinicRange(int $clinicId, string $fromDate, string $toDate, ?string $status = null, int $limit = 2000): array
    {
        $limit = max(25, min($limit, 5000));

        $statusFilter = '';
        $params = [
            'clinic_id' => $clinicId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];

        if ($status !== null && $status !== '' && $status !== 'all') {
            $allowed = ['open', 'paid', 'cancelled'];
            if (in_array($status, $allowed, true)) {
                $statusFilter = ' AND i.status = :status ';
                $params['status'] = $status;
            }
        }

        $sql = "
            SELECT
                i.id AS installment_id,
                i.clinic_id,
                i.payable_id,
                i.installment_no,
                i.due_date,
                i.amount,
                i.status,
                i.paid_at,
                i.paid_entry_id,

                p.vendor_name,
                p.title,
                p.cost_center_id,
                p.payable_type
            FROM accounts_payable_installments i
            INNER JOIN accounts_payable p
                    ON p.id = i.payable_id
                   AND p.clinic_id = i.clinic_id
                   AND p.deleted_at IS NULL
            WHERE i.clinic_id = :clinic_id
              AND i.deleted_at IS NULL
              AND i.due_date BETWEEN :from_date AND :to_date
              {$statusFilter}
            ORDER BY i.due_date ASC, i.id ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $installmentId): ?array
    {
        $sql = "
            SELECT id, clinic_id, payable_id, installment_no, due_date, amount, status, paid_at, paid_entry_id
            FROM accounts_payable_installments
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $installmentId, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markPaid(int $clinicId, int $installmentId, string $paidAt, int $paidEntryId): void
    {
        $sql = "
            UPDATE accounts_payable_installments
            SET status = 'paid',
                paid_at = :paid_at,
                paid_entry_id = :paid_entry_id,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'open'
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $installmentId,
            'clinic_id' => $clinicId,
            'paid_at' => $paidAt,
            'paid_entry_id' => $paidEntryId,
        ]);
    }
}
