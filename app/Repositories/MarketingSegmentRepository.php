<?php

declare(strict_types=1);

namespace App\Repositories;

final class MarketingSegmentRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByClinic(int $clinicId, int $limit = 200): array
    {
        $sql = "
            SELECT
                id, clinic_id,
                name, status,
                rules_json,
                created_by_user_id,
                created_at, updated_at
            FROM marketing_segments
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
                name, status,
                rules_json,
                created_by_user_id,
                created_at, updated_at
            FROM marketing_segments
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

    public function create(int $clinicId, string $name, string $status, array $rules, ?int $createdByUserId): int
    {
        $sql = "
            INSERT INTO marketing_segments (
                clinic_id,
                name, status,
                rules_json,
                created_by_user_id,
                created_at
            ) VALUES (
                :clinic_id,
                :name, :status,
                :rules_json,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'name' => $name,
            'status' => $status,
            'rules_json' => json_encode($rules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $clinicId, int $id, string $name, string $status, array $rules): void
    {
        $sql = "
            UPDATE marketing_segments
               SET name = :name,
                   status = :status,
                   rules_json = :rules_json,
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
            'name' => $name,
            'status' => $status,
            'rules_json' => json_encode($rules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
