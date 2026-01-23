<?php

declare(strict_types=1);

namespace App\Repositories;

final class MaterialRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByClinic(int $clinicId, int $limit = 300): array
    {
        $sql = "
            SELECT
                id, clinic_id,
                name, category, unit,
                stock_current, stock_minimum,
                unit_cost, validity_date,
                status,
                created_at, updated_at
            FROM materials
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

    /** @return list<array<string,mixed>> */
    public function listOutOfStock(int $clinicId, int $limit = 200): array
    {
        $sql = "
            SELECT
                id, clinic_id,
                name, unit,
                stock_current, stock_minimum,
                GREATEST(stock_minimum - stock_current, 0) AS suggested_buy,
                validity_date
            FROM materials
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'active'
              AND stock_current <= 0
            ORDER BY (stock_minimum - stock_current) DESC, name ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listLowStock(int $clinicId, int $limit = 200): array
    {
        $sql = "
            SELECT
                id, clinic_id,
                name, unit,
                stock_current, stock_minimum,
                GREATEST(stock_minimum - stock_current, 0) AS suggested_buy,
                validity_date
            FROM materials
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'active'
              AND stock_minimum > 0
              AND stock_current > 0
              AND stock_current < stock_minimum
            ORDER BY (stock_minimum - stock_current) DESC, name ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listExpired(int $clinicId, int $limit = 200): array
    {
        $sql = "
            SELECT
                id, clinic_id,
                name, unit,
                stock_current, stock_minimum,
                validity_date
            FROM materials
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'active'
              AND validity_date IS NOT NULL
              AND validity_date < CURDATE()
            ORDER BY validity_date ASC, name ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listExpiringSoon(int $clinicId, int $days, int $limit = 200): array
    {
        $days = max(1, min(365, $days));

        $sql = "
            SELECT
                id, clinic_id,
                name, unit,
                stock_current, stock_minimum,
                validity_date
            FROM materials
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'active'
              AND validity_date IS NOT NULL
              AND validity_date >= CURDATE()
              AND validity_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
            ORDER BY validity_date ASC, name ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'days' => $days]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT
                id, clinic_id,
                name, category, unit,
                stock_current, stock_minimum,
                unit_cost, validity_date,
                status,
                created_at, updated_at
            FROM materials
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

    /** @return array<string,mixed>|null */
    public function findByIdForUpdate(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT
                id, clinic_id,
                name, category, unit,
                stock_current, stock_minimum,
                unit_cost, validity_date,
                status,
                created_at, updated_at
            FROM materials
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
            FOR UPDATE
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(
        int $clinicId,
        string $name,
        ?string $category,
        string $unit,
        string $stockMinimum,
        string $unitCost,
        ?string $validityDate
    ): int {
        $sql = "
            INSERT INTO materials (
                clinic_id,
                name, category, unit,
                stock_current, stock_minimum,
                unit_cost, validity_date,
                status,
                created_at
            )
            VALUES (
                :clinic_id,
                :name, :category, :unit,
                0, :stock_minimum,
                :unit_cost, :validity_date,
                'active',
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'name' => $name,
            'category' => ($category === '' ? null : $category),
            'unit' => $unit,
            'stock_minimum' => $stockMinimum,
            'unit_cost' => $unitCost,
            'validity_date' => ($validityDate === '' ? null : $validityDate),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateStockCurrent(int $clinicId, int $materialId, string $stockCurrent): void
    {
        $sql = "
            UPDATE materials
            SET stock_current = :stock_current,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'stock_current' => $stockCurrent,
            'id' => $materialId,
            'clinic_id' => $clinicId,
        ]);
    }
}
