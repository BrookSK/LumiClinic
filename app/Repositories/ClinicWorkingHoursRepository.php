<?php

declare(strict_types=1);

namespace App\Repositories;

final class ClinicWorkingHoursRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, weekday, start_time, end_time, created_at
            FROM clinic_working_hours
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
            ORDER BY weekday ASC, start_time ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    public function create(int $clinicId, int $weekday, string $start, string $end): int
    {
        $sql = "
            INSERT INTO clinic_working_hours (clinic_id, weekday, start_time, end_time, created_at)
            VALUES (:clinic_id, :weekday, :start_time, :end_time, NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'weekday' => $weekday,
            'start_time' => $start,
            'end_time' => $end,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE clinic_working_hours
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
