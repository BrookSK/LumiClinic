<?php

declare(strict_types=1);

namespace App\Repositories;

final class ClinicClosedDaysRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, closed_date, reason, created_at
            FROM clinic_closed_days
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
            ORDER BY closed_date DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    public function create(int $clinicId, string $date, string $reason): int
    {
        $sql = "
            INSERT INTO clinic_closed_days (clinic_id, closed_date, reason, created_at)
            VALUES (:clinic_id, :closed_date, :reason, NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'closed_date' => $date,
            'reason' => ($reason === '' ? null : $reason),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE clinic_closed_days
               SET deleted_at = NOW(),
                   updated_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
    }
}
