<?php

declare(strict_types=1);

namespace App\Repositories;

final class ConsultationRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed>|null */
    public function findByAppointmentId(int $clinicId, int $appointmentId): ?array
    {
        $sql = "
            SELECT id, clinic_id, appointment_id, patient_id, professional_id, executed_at, notes, created_by_user_id, created_at, updated_at
            FROM consultations
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

    public function create(
        int $clinicId,
        int $appointmentId,
        int $patientId,
        int $professionalId,
        string $executedAt,
        ?string $notes,
        ?int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO consultations (
                clinic_id,
                appointment_id, patient_id, professional_id,
                executed_at, notes,
                created_by_user_id,
                created_at
            ) VALUES (
                :clinic_id,
                :appointment_id, :patient_id, :professional_id,
                :executed_at, :notes,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'appointment_id' => $appointmentId,
            'patient_id' => $patientId,
            'professional_id' => $professionalId,
            'executed_at' => $executedAt,
            'notes' => ($notes === '' ? null : $notes),
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $clinicId, int $id, string $executedAt, ?string $notes, int $professionalId): void
    {
        $sql = "
            UPDATE consultations
            SET executed_at = :executed_at,
                notes = :notes,
                professional_id = :professional_id,
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
            'executed_at' => $executedAt,
            'notes' => ($notes === '' ? null : $notes),
            'professional_id' => $professionalId,
        ]);
    }
}
