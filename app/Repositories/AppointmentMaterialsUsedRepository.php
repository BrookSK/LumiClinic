<?php

declare(strict_types=1);

namespace App\Repositories;

final class AppointmentMaterialsUsedRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listDetailedByAppointment(int $clinicId, int $appointmentId): array
    {
        $sql = "
            SELECT
                u.id,
                u.clinic_id,
                u.appointment_id,
                u.material_id,
                u.quantity,
                u.note,
                u.created_by_user_id,
                u.created_at,
                m.name AS material_name,
                m.unit AS material_unit
            FROM appointment_materials_used u
            INNER JOIN materials m
                    ON m.id = u.material_id
                   AND m.clinic_id = u.clinic_id
                   AND m.deleted_at IS NULL
            WHERE u.clinic_id = :clinic_id
              AND u.appointment_id = :appointment_id
              AND u.deleted_at IS NULL
            ORDER BY m.name ASC, u.id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'appointment_id' => $appointmentId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<int,string> map material_id => qty */
    public function listQtyByAppointment(int $clinicId, int $appointmentId): array
    {
        $sql = "
            SELECT material_id, quantity
            FROM appointment_materials_used
            WHERE clinic_id = :clinic_id
              AND appointment_id = :appointment_id
              AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'appointment_id' => $appointmentId]);

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

    public function replaceForAppointment(
        int $clinicId,
        int $appointmentId,
        array $qtyByMaterialId,
        ?string $note,
        ?int $createdByUserId
    ): void {
        $note = $note === null ? null : trim($note);
        if ($note === '') {
            $note = null;
        }

        $sqlSoftDelete = "
            UPDATE appointment_materials_used
               SET deleted_at = NOW()
             WHERE clinic_id = :clinic_id
               AND appointment_id = :appointment_id
               AND deleted_at IS NULL
        ";

        $sqlInsert = "
            INSERT INTO appointment_materials_used (
                clinic_id, appointment_id, material_id,
                quantity, note,
                created_by_user_id,
                created_at
            ) VALUES (
                :clinic_id, :appointment_id, :material_id,
                :quantity, :note,
                :created_by_user_id,
                NOW()
            )
        ";

        try {
            $this->pdo->beginTransaction();

            $d = $this->pdo->prepare($sqlSoftDelete);
            $d->execute(['clinic_id' => $clinicId, 'appointment_id' => $appointmentId]);

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
                    'appointment_id' => $appointmentId,
                    'material_id' => $materialId,
                    'quantity' => number_format($qty, 3, '.', ''),
                    'note' => $note,
                    'created_by_user_id' => $createdByUserId,
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
