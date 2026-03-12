<?php

declare(strict_types=1);

namespace App\Repositories;

final class StockInventoryItemRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByInventoryDetailed(int $clinicId, int $inventoryId): array
    {
        $sql = "
            SELECT
                i.id,
                i.clinic_id,
                i.inventory_id,
                i.material_id,
                i.qty_system_snapshot,
                i.qty_counted,
                i.qty_delta,
                i.unit_cost_snapshot,
                i.total_cost_delta_snapshot,
                i.created_at,
                i.updated_at,
                m.name AS material_name,
                m.unit AS material_unit,
                m.stock_current AS material_stock_current
            FROM stock_inventory_items i
            INNER JOIN materials m
                    ON m.id = i.material_id
                   AND m.clinic_id = i.clinic_id
                   AND m.deleted_at IS NULL
            WHERE i.clinic_id = :clinic_id
              AND i.inventory_id = :inventory_id
              AND i.deleted_at IS NULL
            ORDER BY m.name ASC, i.id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'inventory_id' => $inventoryId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function createSnapshotForAllMaterials(int $clinicId, int $inventoryId): void
    {
        $sql = "
            INSERT INTO stock_inventory_items (
                clinic_id, inventory_id, material_id,
                qty_system_snapshot,
                qty_counted,
                qty_delta,
                unit_cost_snapshot,
                total_cost_delta_snapshot,
                created_at
            )
            SELECT
                m.clinic_id,
                :inventory_id,
                m.id,
                m.stock_current,
                m.stock_current,
                0,
                m.unit_cost,
                0,
                NOW()
            FROM materials m
            LEFT JOIN stock_inventory_items it
                   ON it.clinic_id = m.clinic_id
                  AND it.inventory_id = :inventory_id2
                  AND it.material_id = m.id
                  AND it.deleted_at IS NULL
            WHERE m.clinic_id = :clinic_id
              AND m.deleted_at IS NULL
              AND it.id IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'inventory_id' => $inventoryId,
            'inventory_id2' => $inventoryId,
        ]);
    }

    public function createForMaterialIfMissing(int $clinicId, int $inventoryId, int $materialId, ?float $qtyCounted = null): void
    {
        $sql = "
            INSERT INTO stock_inventory_items (
                clinic_id, inventory_id, material_id,
                qty_system_snapshot,
                qty_counted,
                qty_delta,
                unit_cost_snapshot,
                total_cost_delta_snapshot,
                created_at
            )
            SELECT
                m.clinic_id,
                :inventory_id,
                m.id,
                m.stock_current,
                :qty_counted,
                (:qty_counted - m.stock_current),
                m.unit_cost,
                ROUND((:qty_counted - m.stock_current) * m.unit_cost, 2),
                NOW()
            FROM materials m
            LEFT JOIN stock_inventory_items it
                   ON it.clinic_id = m.clinic_id
                  AND it.inventory_id = :inventory_id2
                  AND it.material_id = m.id
                  AND it.deleted_at IS NULL
            WHERE m.clinic_id = :clinic_id
              AND m.id = :material_id
              AND m.deleted_at IS NULL
              AND it.id IS NULL
            LIMIT 1
        ";

        if ($qtyCounted === null) {
            $stmt = $this->pdo->prepare("SELECT stock_current FROM materials WHERE clinic_id = :clinic_id AND id = :material_id AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['clinic_id' => $clinicId, 'material_id' => $materialId]);
            $row = $stmt->fetch();
            $qtyCounted = (float)($row['stock_current'] ?? 0);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'inventory_id' => $inventoryId,
            'inventory_id2' => $inventoryId,
            'material_id' => $materialId,
            'qty_counted' => number_format($qtyCounted, 3, '.', ''),
        ]);
    }

    public function updateCounted(int $clinicId, int $inventoryId, int $materialId, float $qtyCounted): void
    {
        $sql = "
            UPDATE stock_inventory_items
               SET qty_counted = :qty_counted,
                   qty_delta = (qty_counted - qty_system_snapshot),
                   total_cost_delta_snapshot = ROUND((qty_counted - qty_system_snapshot) * unit_cost_snapshot, 2),
                   updated_at = NOW()
             WHERE clinic_id = :clinic_id
               AND inventory_id = :inventory_id
               AND material_id = :material_id
               AND deleted_at IS NULL
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'inventory_id' => $inventoryId,
            'material_id' => $materialId,
            'qty_counted' => number_format($qtyCounted, 3, '.', ''),
        ]);
    }
}
