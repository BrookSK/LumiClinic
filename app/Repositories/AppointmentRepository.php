<?php

declare(strict_types=1);

namespace App\Repositories;

final class AppointmentRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listUpcomingByPatient(int $clinicId, int $patientId, int $limit = 10): array
    {
        $sql = "
            SELECT
                a.id,
                a.start_at,
                a.end_at,
                a.status,
                s.name AS service_name,
                p.name AS professional_name
            FROM appointments a
            LEFT JOIN services s
                   ON s.id = a.service_id
                  AND s.clinic_id = a.clinic_id
                  AND s.deleted_at IS NULL
            LEFT JOIN professionals p
                   ON p.id = a.professional_id
                  AND p.clinic_id = a.clinic_id
                  AND p.deleted_at IS NULL
            WHERE a.clinic_id = :clinic_id
              AND a.patient_id = :patient_id
              AND a.deleted_at IS NULL
              AND a.status NOT IN ('cancelled')
              AND a.start_at >= NOW()
            ORDER BY a.start_at ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findByIdForPatient(int $clinicId, int $patientId, int $appointmentId): ?array
    {
        $sql = "
            SELECT id, clinic_id, professional_id, service_id, patient_id, start_at, end_at, status, origin, notes
            FROM appointments
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND patient_id = :patient_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $appointmentId, 'clinic_id' => $clinicId, 'patient_id' => $patientId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updateStatusForPatient(int $clinicId, int $patientId, int $appointmentId, string $status): void
    {
        $sql = "
            UPDATE appointments
               SET status = :status,
                   updated_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND patient_id = :patient_id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $appointmentId,
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'status' => $status,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function listByClinicDate(int $clinicId, string $dateYmd): array
    {
        $sql = "
            SELECT id, clinic_id, professional_id, service_id, patient_id, start_at, end_at, buffer_before_minutes, buffer_after_minutes, status, origin, notes
            FROM appointments
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND DATE(start_at) = :date
            ORDER BY start_at ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'date' => $dateYmd]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function listByClinicRange(int $clinicId, string $startAt, string $endAt, ?int $professionalId = null): array
    {
        $sql = "
            SELECT id, clinic_id, professional_id, service_id, patient_id, start_at, end_at, buffer_before_minutes, buffer_after_minutes, status, origin, notes
            FROM appointments
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND start_at >= :start_at
              AND start_at < :end_at
        ";

        $params = [
            'clinic_id' => $clinicId,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ];

        if ($professionalId !== null) {
            $sql .= " AND professional_id = :professional_id ";
            $params['professional_id'] = $professionalId;
        }

        $sql .= " ORDER BY start_at ASC ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, professional_id, service_id, patient_id, start_at, end_at, buffer_before_minutes, buffer_after_minutes, status, origin, notes
            FROM appointments
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

    /** @return list<array<string, mixed>> */
    public function listOverlapping(
        int $clinicId,
        int $professionalId,
        string $startAt,
        string $endAt
    ): array {
        $sql = "
            SELECT id, start_at, end_at, status
            FROM appointments
            WHERE clinic_id = :clinic_id
              AND professional_id = :professional_id
              AND deleted_at IS NULL
              AND status NOT IN ('cancelled')
              AND start_at < :end_at
              AND end_at > :start_at
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'professional_id' => $professionalId,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function listOverlappingExcludingAppointment(
        int $clinicId,
        int $professionalId,
        string $startAt,
        string $endAt,
        int $excludeAppointmentId
    ): array {
        $sql = "
            SELECT id, start_at, end_at, status
            FROM appointments
            WHERE clinic_id = :clinic_id
              AND professional_id = :professional_id
              AND deleted_at IS NULL
              AND status NOT IN ('cancelled')
              AND id <> :exclude_id
              AND start_at < :end_at
              AND end_at > :start_at
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'professional_id' => $professionalId,
            'exclude_id' => $excludeAppointmentId,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function listOverlappingForUpdate(
        int $clinicId,
        int $professionalId,
        string $startAt,
        string $endAt
    ): array {
        $sql = "
            SELECT id, start_at, end_at, status
            FROM appointments
            WHERE clinic_id = :clinic_id
              AND professional_id = :professional_id
              AND deleted_at IS NULL
              AND status NOT IN ('cancelled')
              AND start_at < :end_at
              AND end_at > :start_at
            FOR UPDATE
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'professional_id' => $professionalId,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function listOverlappingForUpdateExcludingAppointment(
        int $clinicId,
        int $professionalId,
        string $startAt,
        string $endAt,
        int $excludeAppointmentId
    ): array {
        $sql = "
            SELECT id, start_at, end_at, status
            FROM appointments
            WHERE clinic_id = :clinic_id
              AND professional_id = :professional_id
              AND deleted_at IS NULL
              AND status NOT IN ('cancelled')
              AND id <> :exclude_id
              AND start_at < :end_at
              AND end_at > :start_at
            FOR UPDATE
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'professional_id' => $professionalId,
            'exclude_id' => $excludeAppointmentId,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    public function create(
        int $clinicId,
        int $professionalId,
        int $serviceId,
        ?int $patientId,
        string $startAt,
        string $endAt,
        int $bufferBeforeMinutes,
        int $bufferAfterMinutes,
        string $status,
        string $origin,
        ?string $notes,
        ?int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO appointments (
                clinic_id, professional_id, service_id, patient_id, start_at, end_at,
                buffer_before_minutes, buffer_after_minutes,
                status, origin, notes, created_by_user_id, created_at
            ) VALUES (
                :clinic_id, :professional_id, :service_id, :patient_id, :start_at, :end_at,
                :buffer_before_minutes, :buffer_after_minutes,
                :status, :origin, :notes, :created_by_user_id, NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'professional_id' => $professionalId,
            'service_id' => $serviceId,
            'patient_id' => $patientId,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'buffer_before_minutes' => max(0, (int)$bufferBeforeMinutes),
            'buffer_after_minutes' => max(0, (int)$bufferAfterMinutes),
            'status' => $status,
            'origin' => $origin,
            'notes' => ($notes === '' ? null : $notes),
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $clinicId, int $appointmentId, string $status): void
    {
        $sql = "
            UPDATE appointments
               SET status = :status,
                   updated_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $appointmentId, 'clinic_id' => $clinicId, 'status' => $status]);
    }

    public function updateTimeAndProfessional(
        int $clinicId,
        int $appointmentId,
        int $professionalId,
        string $startAt,
        string $endAt
    ): void {
        $sql = "
            UPDATE appointments
               SET professional_id = :professional_id,
                   start_at = :start_at,
                   end_at = :end_at,
                   updated_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $appointmentId,
            'clinic_id' => $clinicId,
            'professional_id' => $professionalId,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);
    }

    public function updateTimeProfessionalAndService(
        int $clinicId,
        int $appointmentId,
        int $professionalId,
        int $serviceId,
        string $startAt,
        string $endAt,
        int $bufferBeforeMinutes,
        int $bufferAfterMinutes
    ): void {
        $sql = "
            UPDATE appointments
               SET professional_id = :professional_id,
                   service_id = :service_id,
                   start_at = :start_at,
                   end_at = :end_at,
                   buffer_before_minutes = :buffer_before_minutes,
                   buffer_after_minutes = :buffer_after_minutes,
                   updated_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $appointmentId,
            'clinic_id' => $clinicId,
            'professional_id' => $professionalId,
            'service_id' => $serviceId,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'buffer_before_minutes' => max(0, (int)$bufferBeforeMinutes),
            'buffer_after_minutes' => max(0, (int)$bufferAfterMinutes),
        ]);
    }
}
