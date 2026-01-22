<?php

declare(strict_types=1);

namespace App\Repositories;

final class SchedulingBlockRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listOverlapping(
        int $clinicId,
        ?int $professionalId,
        string $startAt,
        string $endAt
    ): array {
        $sql = "
            SELECT id, clinic_id, professional_id, start_at, end_at, reason, type
            FROM scheduling_blocks
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND (professional_id IS NULL OR professional_id = :professional_id)
              AND start_at < :end_at
              AND end_at > :start_at
            ORDER BY start_at ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'professional_id' => $professionalId,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    public function create(
        int $clinicId,
        ?int $professionalId,
        string $startAt,
        string $endAt,
        ?string $reason,
        string $type,
        ?int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO scheduling_blocks (
                clinic_id, professional_id, start_at, end_at, reason, type, created_by_user_id, created_at
            ) VALUES (
                :clinic_id, :professional_id, :start_at, :end_at, :reason, :type, :created_by_user_id, NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'professional_id' => $professionalId,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'reason' => ($reason === '' ? null : $reason),
            'type' => $type,
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
