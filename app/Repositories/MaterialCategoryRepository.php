<?php

declare(strict_types=1);

namespace App\Repositories;

final class MaterialCategoryRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listActiveByClinic(int $clinicId, int $limit = 300): array
    {
        $sql = "
            SELECT id, clinic_id, name, status, created_at
            FROM material_categories
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'ativo'
            ORDER BY name ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listAllByClinic(int $clinicId, int $limit = 500): array
    {
        $sql = "
            SELECT id, clinic_id, name, status, created_at
            FROM material_categories
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
            ORDER BY name ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function create(int $clinicId, string $name): int
    {
        $sql = "
            INSERT INTO material_categories (clinic_id, name, status, created_at)
            VALUES (:clinic_id, :name, 'ativo', NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'name' => $name,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function existsActiveByClinicAndName(int $clinicId, string $name): bool
    {
        $sql = "
            SELECT 1
            FROM material_categories
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'ativo'
              AND name = :name
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'name' => $name]);
        return (bool)$stmt->fetchColumn();
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE material_categories
            SET deleted_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
    }
}
