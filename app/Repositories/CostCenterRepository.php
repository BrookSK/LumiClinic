<?php

declare(strict_types=1);

namespace App\Repositories;

final class CostCenterRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listActiveByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, clinic_id, name, status
            FROM cost_centers
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'active'
            ORDER BY name ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, clinic_id, name, status, created_at, updated_at
            FROM cost_centers
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
            ORDER BY status DESC, name ASC
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
            SELECT id, clinic_id, name, status
            FROM cost_centers
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

    public function create(int $clinicId, string $name): int
    {
        $sql = "
            INSERT INTO cost_centers (clinic_id, name, status, created_at)
            VALUES (:clinic_id, :name, 'active', NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'name' => $name]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $clinicId, int $id, string $name): void
    {
        $sql = "
            UPDATE cost_centers
            SET name = :name,
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
            'name' => $name,
        ]);
    }

    public function setStatus(int $clinicId, int $id, string $status): void
    {
        $sql = "
            UPDATE cost_centers
            SET status = :status,
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
        ]);
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE cost_centers
            SET deleted_at = NOW(),
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
        ]);
    }
}
