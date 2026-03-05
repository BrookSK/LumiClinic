<?php

declare(strict_types=1);

namespace App\Repositories;

final class ProcedurePerformedRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed>|null */
    public function findByAppointmentId(int $clinicId, int $appointmentId): ?array
    {
        $sql = "
            SELECT
                id, clinic_id,
                appointment_id, patient_id, professional_id, service_id, procedure_id,
                real_started_at, real_ended_at, real_duration_minutes,
                stock_total_cost_snapshot, stock_movement_ids_json,
                financial_entry_id,
                note,
                created_by_user_id,
                created_at, updated_at
            FROM procedure_performed
            WHERE clinic_id = :clinic_id
              AND appointment_id = :appointment_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'appointment_id' => $appointmentId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function upsertForAppointment(
        int $clinicId,
        int $appointmentId,
        ?int $patientId,
        int $professionalId,
        int $serviceId,
        ?int $procedureId,
        ?string $realStartedAt,
        ?string $realEndedAt,
        ?int $realDurationMinutes,
        float $stockTotalCostSnapshot,
        ?array $stockMovementIds,
        ?int $financialEntryId,
        ?string $note,
        ?int $createdByUserId
    ): int {
        $note = $note === null ? null : trim($note);
        if ($note === '') {
            $note = null;
        }

        $movementJson = null;
        if (is_array($stockMovementIds)) {
            $ids = [];
            foreach ($stockMovementIds as $id) {
                $id = (int)$id;
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
            $movementJson = $ids === [] ? null : json_encode(array_values(array_unique($ids)), JSON_UNESCAPED_UNICODE);
        }

        $sql = "
            INSERT INTO procedure_performed (
                clinic_id,
                appointment_id, patient_id, professional_id, service_id, procedure_id,
                real_started_at, real_ended_at, real_duration_minutes,
                stock_total_cost_snapshot, stock_movement_ids_json,
                financial_entry_id,
                note,
                created_by_user_id,
                created_at
            ) VALUES (
                :clinic_id,
                :appointment_id, :patient_id, :professional_id, :service_id, :procedure_id,
                :real_started_at, :real_ended_at, :real_duration_minutes,
                :stock_total_cost_snapshot, :stock_movement_ids_json,
                :financial_entry_id,
                :note,
                :created_by_user_id,
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                patient_id = VALUES(patient_id),
                professional_id = VALUES(professional_id),
                service_id = VALUES(service_id),
                procedure_id = VALUES(procedure_id),
                real_started_at = VALUES(real_started_at),
                real_ended_at = VALUES(real_ended_at),
                real_duration_minutes = VALUES(real_duration_minutes),
                stock_total_cost_snapshot = VALUES(stock_total_cost_snapshot),
                stock_movement_ids_json = VALUES(stock_movement_ids_json),
                financial_entry_id = VALUES(financial_entry_id),
                note = VALUES(note),
                updated_at = NOW()
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'appointment_id' => $appointmentId,
            'patient_id' => $patientId,
            'professional_id' => $professionalId,
            'service_id' => $serviceId,
            'procedure_id' => $procedureId,
            'real_started_at' => $realStartedAt,
            'real_ended_at' => $realEndedAt,
            'real_duration_minutes' => $realDurationMinutes,
            'stock_total_cost_snapshot' => number_format($stockTotalCostSnapshot, 2, '.', ''),
            'stock_movement_ids_json' => $movementJson,
            'financial_entry_id' => $financialEntryId,
            'note' => $note,
            'created_by_user_id' => $createdByUserId,
        ]);

        $existing = $this->findByAppointmentId($clinicId, $appointmentId);
        return $existing ? (int)$existing['id'] : (int)$this->pdo->lastInsertId();
    }
}
