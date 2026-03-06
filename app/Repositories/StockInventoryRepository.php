<?php

declare(strict_types=1);

namespace App\Repositories;

final class StockInventoryRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByClinic(int $clinicId, int $limit = 50): array
    {
        $sql = "
            SELECT
                id, clinic_id,
                status,
                notes,
                created_by_user_id,
                confirmed_by_user_id,
                confirmed_at,
                created_at,
                updated_at
            FROM stock_inventories
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT
                id, clinic_id,
                status,
                notes,
                created_by_user_id,
                confirmed_by_user_id,
                confirmed_at,
                created_at,
                updated_at
            FROM stock_inventories
            WHERE clinic_id = :clinic_id
              AND id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $clinicId, ?string $notes, ?int $createdByUserId): int
    {
        $sql = "
            INSERT INTO stock_inventories (
                clinic_id,
                status,
                notes,
                created_by_user_id,
                created_at
            ) VALUES (
                :clinic_id,
                'draft',
                :notes,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'notes' => ($notes === '' ? null : $notes),
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateNotes(int $clinicId, int $id, ?string $notes): void
    {
        $sql = "
            UPDATE stock_inventories
               SET notes = :notes,
                   updated_at = NOW()
             WHERE clinic_id = :clinic_id
               AND id = :id
               AND deleted_at IS NULL
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'id' => $id,
            'notes' => ($notes === '' ? null : $notes),
        ]);
    }

    public function confirm(int $clinicId, int $id, ?int $confirmedByUserId): void
    {
        $sql = "
            UPDATE stock_inventories
               SET status = 'confirmed',
                   confirmed_by_user_id = :confirmed_by_user_id,
                   confirmed_at = NOW(),
                   updated_at = NOW()
             WHERE clinic_id = :clinic_id
               AND id = :id
               AND deleted_at IS NULL
               AND status = 'draft'
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'id' => $id,
            'confirmed_by_user_id' => $confirmedByUserId,
        ]);
    }
}
