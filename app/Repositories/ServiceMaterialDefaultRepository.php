<?php

declare(strict_types=1);

namespace App\Repositories;

final class ServiceMaterialDefaultRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByService(int $clinicId, int $serviceId): array
    {
        $sql = "
            SELECT id, clinic_id, service_id, material_id, quantity_per_session
            FROM service_material_defaults
            WHERE clinic_id = :clinic_id
              AND service_id = :service_id
              AND deleted_at IS NULL
            ORDER BY id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'service_id' => $serviceId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listDetailedByService(int $clinicId, int $serviceId): array
    {
        $sql = "
            SELECT
                d.id,
                d.clinic_id,
                d.service_id,
                d.material_id,
                d.quantity_per_session,
                m.name AS material_name,
                m.unit AS material_unit
            FROM service_material_defaults d
            INNER JOIN materials m
                    ON m.id = d.material_id
                   AND m.clinic_id = d.clinic_id
                   AND m.deleted_at IS NULL
            WHERE d.clinic_id = :clinic_id
              AND d.service_id = :service_id
              AND d.deleted_at IS NULL
            ORDER BY m.name ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'service_id' => $serviceId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function upsert(int $clinicId, int $serviceId, int $materialId, string $qtyStr): int
    {
        $qty = (float)str_replace(',', '.', trim($qtyStr));
        if ($qty <= 0) {
            throw new \RuntimeException('Quantidade invalida.');
        }

        $fmtQty = number_format($qty, 3, '.', '');

        // First try: find active record
        $stmt = $this->pdo->prepare(
            "SELECT id FROM service_material_defaults
             WHERE clinic_id = :c AND service_id = :s AND material_id = :m AND deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute(['c' => $clinicId, 's' => $serviceId, 'm' => $materialId]);
        $existingId = (int)($stmt->fetchColumn() ?: 0);

        if ($existingId > 0) {
            $this->pdo->prepare(
                "UPDATE service_material_defaults SET quantity_per_session = :qty, updated_at = NOW() WHERE id = :id"
            )->execute(['qty' => $fmtQty, 'id' => $existingId]);
            return $existingId;
        }

        // Second try: find soft-deleted record and restore it
        $stmt2 = $this->pdo->prepare(
            "SELECT id FROM service_material_defaults
             WHERE clinic_id = :c AND service_id = :s AND material_id = :m AND deleted_at IS NOT NULL LIMIT 1"
        );
        $stmt2->execute(['c' => $clinicId, 's' => $serviceId, 'm' => $materialId]);
        $deletedId = (int)($stmt2->fetchColumn() ?: 0);

        if ($deletedId > 0) {
            $this->pdo->prepare(
                "UPDATE service_material_defaults SET quantity_per_session = :qty, deleted_at = NULL, updated_at = NOW() WHERE id = :id"
            )->execute(['qty' => $fmtQty, 'id' => $deletedId]);
            return $deletedId;
        }

        // Third: insert new
        $this->pdo->prepare(
            "INSERT INTO service_material_defaults (clinic_id, service_id, material_id, quantity_per_session, created_at)
             VALUES (:c, :s, :m, :qty, NOW())"
        )->execute(['c' => $clinicId, 's' => $serviceId, 'm' => $materialId, 'qty' => $fmtQty]);

        return (int)$this->pdo->lastInsertId();
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE service_material_defaults
               SET deleted_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
    }
}
