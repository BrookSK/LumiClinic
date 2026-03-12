<?php

declare(strict_types=1);

namespace App\Repositories;

final class ClinicClosedDaysRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, closed_date, reason, is_open, created_at
            FROM clinic_closed_days
            WHERE clinic_id = :clinic_id
              AND id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id]);

        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> */
        return $row;
    }

    /** @return list<array<string, mixed>> */
    public function listByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, closed_date, reason, is_open, created_at
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

    /** @return list<array<string, mixed>> */
    public function listClosedOnlyByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, closed_date, reason, is_open, created_at
            FROM clinic_closed_days
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND is_open = 0
            ORDER BY closed_date DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    public function create(int $clinicId, string $date, string $reason, int $isOpen = 0): int
    {
        $sql = "
            INSERT INTO clinic_closed_days (clinic_id, closed_date, reason, is_open, created_at)
            VALUES (:clinic_id, :closed_date, :reason, :is_open, NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'closed_date' => $date,
            'reason' => ($reason === '' ? null : $reason),
            'is_open' => $isOpen === 1 ? 1 : 0,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function upsert(int $clinicId, string $date, ?string $reason, int $isOpen): void
    {
        $sql = "
            INSERT INTO clinic_closed_days (clinic_id, closed_date, reason, is_open, created_at)
            VALUES (:clinic_id, :closed_date, :reason, :is_open, NOW())
            ON DUPLICATE KEY UPDATE
                reason = VALUES(reason),
                is_open = VALUES(is_open),
                deleted_at = NULL,
                updated_at = NOW()
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'closed_date' => $date,
            'reason' => ($reason !== null && trim($reason) !== '' ? trim($reason) : null),
            'is_open' => $isOpen === 1 ? 1 : 0,
        ]);
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

    public function updateById(int $clinicId, int $id, string $date, ?string $reason, int $isOpen): void
    {
        $sql = "
            UPDATE clinic_closed_days
               SET closed_date = :closed_date,
                   reason = :reason,
                   is_open = :is_open,
                   updated_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'closed_date' => $date,
            'reason' => ($reason !== null && trim($reason) !== '' ? trim($reason) : null),
            'is_open' => $isOpen === 1 ? 1 : 0,
            'id' => $id,
            'clinic_id' => $clinicId,
        ]);
    }
}
