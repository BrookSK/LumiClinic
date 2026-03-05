<?php

declare(strict_types=1);

namespace App\Repositories;

final class ProcedurePerformedMaterialRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listDetailedByPerformedId(int $clinicId, int $performedId): array
    {
        $sql = "
            SELECT
                u.id,
                u.clinic_id,
                u.performed_id,
                u.material_id,
                u.quantity,
                u.note,
                u.created_at,
                m.name AS material_name,
                m.unit AS material_unit
            FROM procedure_performed_materials u
            INNER JOIN materials m
                    ON m.id = u.material_id
                   AND m.clinic_id = u.clinic_id
                   AND m.deleted_at IS NULL
            WHERE u.clinic_id = :clinic_id
              AND u.performed_id = :performed_id
              AND u.deleted_at IS NULL
            ORDER BY m.name ASC, u.id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'performed_id' => $performedId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<int,string> map material_id => qty */
    public function listQtyByPerformedId(int $clinicId, int $performedId): array
    {
        $sql = "
            SELECT material_id, quantity
            FROM procedure_performed_materials
            WHERE clinic_id = :clinic_id
              AND performed_id = :performed_id
              AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'performed_id' => $performedId]);

        $map = [];
        while ($row = $stmt->fetch()) {
            $mid = (int)($row['material_id'] ?? 0);
            if ($mid <= 0) {
                continue;
            }
            $map[$mid] = number_format((float)($row['quantity'] ?? 0), 3, '.', '');
        }

        return $map;
    }

    public function replaceForPerformed(
        int $clinicId,
        int $performedId,
        array $qtyByMaterialId,
        ?string $note
    ): void {
        $note = $note === null ? null : trim($note);
        if ($note === '') {
            $note = null;
        }

        $sqlSoftDelete = "
            UPDATE procedure_performed_materials
               SET deleted_at = NOW()
             WHERE clinic_id = :clinic_id
               AND performed_id = :performed_id
               AND deleted_at IS NULL
        ";

        $sqlInsert = "
            INSERT INTO procedure_performed_materials (
                clinic_id, performed_id, material_id,
                quantity, note,
                created_at
            ) VALUES (
                :clinic_id, :performed_id, :material_id,
                :quantity, :note,
                NOW()
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $d = $this->pdo->prepare($sqlSoftDelete);
            $d->execute(['clinic_id' => $clinicId, 'performed_id' => $performedId]);

            $i = $this->pdo->prepare($sqlInsert);
            foreach ($qtyByMaterialId as $mid => $qtyStr) {
                $materialId = (int)$mid;
                if ($materialId <= 0) {
                    continue;
                }

                $qty = (float)str_replace(',', '.', trim((string)$qtyStr));
                if ($qty <= 0) {
                    continue;
                }

                $i->execute([
                    'clinic_id' => $clinicId,
                    'performed_id' => $performedId,
                    'material_id' => $materialId,
                    'quantity' => number_format($qty, 3, '.', ''),
                    'note' => $note,
                ]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
