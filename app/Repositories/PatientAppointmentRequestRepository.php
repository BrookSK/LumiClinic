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
    public function listPendingByPatient(int $clinicId, int $patientId, int $limit = 50, int $offset = 0): array
    {
        $offset = max(0, $offset);
        $sql = "
            SELECT id, clinic_id, patient_id, appointment_id, type, status, requested_start_at, note, created_at
            FROM patient_appointment_requests
            WHERE clinic_id = :clinic_id
              AND patient_id = :patient_id
              AND deleted_at IS NULL
              AND status = 'pending'
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
            OFFSET " . (int)$offset . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listPendingByClinicDetailed(int $clinicId, int $limit = 50, int $offset = 0, ?int $professionalId = null): array
    {
        $limit = max(1, min($limit, 200));
        $offset = max(0, $offset);

        $sql = "
            SELECT
                r.id,
                r.clinic_id,
                r.patient_id,
                r.appointment_id,
                r.type,
                r.status,
                r.requested_start_at,
                r.note,
                r.created_at,
                a.start_at AS appointment_start_at,
                a.end_at AS appointment_end_at,
                a.professional_id,
                a.service_id,
                COALESCE(pat.name, '') AS patient_name,
                COALESCE(s.name, '') AS service_name,
                COALESCE(pro.name, '') AS professional_name
            FROM patient_appointment_requests r
            LEFT JOIN appointments a
                   ON a.id = r.appointment_id
                  AND a.clinic_id = r.clinic_id
                  AND a.deleted_at IS NULL
            LEFT JOIN patients pat
                   ON pat.id = r.patient_id
                  AND pat.clinic_id = r.clinic_id
                  AND pat.deleted_at IS NULL
            LEFT JOIN services s
                   ON s.id = a.service_id
                  AND s.clinic_id = a.clinic_id
                  AND s.deleted_at IS NULL
            LEFT JOIN professionals pro
                   ON pro.id = a.professional_id
                  AND pro.clinic_id = a.clinic_id
                  AND pro.deleted_at IS NULL
            WHERE r.clinic_id = :clinic_id
              AND r.deleted_at IS NULL
              AND r.status = 'pending'
        ";

        $params = [
            'clinic_id' => $clinicId,
        ];

        if ($professionalId !== null) {
            $sql .= " AND a.professional_id = :professional_id ";
            $params['professional_id'] = $professionalId;
        }

        $sql .= " ORDER BY r.id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset . " ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }
}
