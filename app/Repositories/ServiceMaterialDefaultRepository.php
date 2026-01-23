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
            throw new \RuntimeException('Quantidade inválida.');
        }

        $sqlFind = "
            SELECT id
            FROM service_material_defaults
            WHERE clinic_id = :clinic_id
              AND service_id = :service_id
              AND material_id = :material_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sqlFind);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'service_id' => $serviceId,
            'material_id' => $materialId,
        ]);

        $existingId = (int)($stmt->fetchColumn() ?: 0);

        if ($existingId > 0) {
            $sqlUpdate = "
                UPDATE service_material_defaults
                   SET quantity_per_session = :qty,
                       updated_at = NOW()
                 WHERE id = :id
                   AND clinic_id = :clinic_id
                   AND deleted_at IS NULL
            ";
            $u = $this->pdo->prepare($sqlUpdate);
            $u->execute(['qty' => number_format($qty, 3, '.', ''), 'id' => $existingId, 'clinic_id' => $clinicId]);
            return $existingId;
        }

        $sqlInsert = "
            INSERT INTO service_material_defaults
                (clinic_id, service_id, material_id, quantity_per_session, created_at)
            VALUES
                (:clinic_id, :service_id, :material_id, :qty, NOW())
        ";

        $i = $this->pdo->prepare($sqlInsert);
        $i->execute([
            'clinic_id' => $clinicId,
            'service_id' => $serviceId,
            'material_id' => $materialId,
            'qty' => number_format($qty, 3, '.', ''),
        ]);

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
