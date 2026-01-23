<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientAppointmentRequestRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(
        int $clinicId,
        int $patientId,
        int $appointmentId,
        string $type,
        ?string $requestedStartAt,
        ?string $note
    ): int {
        $sql = "
            INSERT INTO patient_appointment_requests (
                clinic_id, patient_id, appointment_id,
                type, status,
                requested_start_at, note,
                created_at
            ) VALUES (
                :clinic_id, :patient_id, :appointment_id,
                :type, 'pending',
                :requested_start_at, :note,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'appointment_id' => $appointmentId,
            'type' => $type,
            'requested_start_at' => ($requestedStartAt === '' ? null : $requestedStartAt),
            'note' => ($note === '' ? null : $note),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function listPendingByPatient(int $clinicId, int $patientId, int $limit = 50): array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, appointment_id, type, status, requested_start_at, note, created_at
            FROM patient_appointment_requests
            WHERE clinic_id = :clinic_id
              AND patient_id = :patient_id
              AND deleted_at IS NULL
              AND status = 'pending'
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }
}
