<?php

declare(strict_types=1);

namespace App\Repositories;

final class ProfessionalScheduleRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listByProfessional(int $clinicId, int $professionalId): array
    {
        $sql = "
            SELECT id, clinic_id, professional_id, weekday, start_time, end_time, interval_minutes
            FROM professional_schedules
            WHERE clinic_id = :clinic_id
              AND professional_id = :professional_id
              AND deleted_at IS NULL
            ORDER BY weekday ASC, start_time ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'professional_id' => $professionalId,
        ]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function listByClinicWeekday(int $clinicId, int $weekday): array
    {
        $sql = "
            SELECT id, clinic_id, professional_id, weekday, start_time, end_time, interval_minutes
            FROM professional_schedules
            WHERE clinic_id = :clinic_id
              AND weekday = :weekday
              AND deleted_at IS NULL
            ORDER BY professional_id ASC, start_time ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'weekday' => $weekday]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    public function create(
        int $clinicId,
        int $professionalId,
        int $weekday,
        string $startTime,
        string $endTime,
        ?int $intervalMinutes
    ): int {
        $sql = "
            INSERT INTO professional_schedules (
                clinic_id, professional_id, weekday, start_time, end_time, interval_minutes, created_at
            ) VALUES (
                :clinic_id, :professional_id, :weekday, :start_time, :end_time, :interval_minutes, NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'professional_id' => $professionalId,
            'weekday' => $weekday,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'interval_minutes' => $intervalMinutes,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE professional_schedules
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
