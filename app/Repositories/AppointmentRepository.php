<?php

declare(strict_types=1);

namespace App\Repositories;

final class AppointmentRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string, mixed>|null */
    public function findReminderContext(int $clinicId, int $appointmentId): ?array
    {
        $sql = "
            SELECT
                a.id,
                a.clinic_id,
                a.patient_id,
                a.start_at,
                a.end_at,
                a.status,
                COALESCE(pat.name, '') AS patient_name,
                pat.phone AS patient_phone,
                pat.whatsapp_opt_in
            FROM appointments a
            LEFT JOIN patients pat
                   ON pat.id = a.patient_id
                  AND pat.clinic_id = a.clinic_id
                  AND pat.deleted_at IS NULL
            WHERE a.id = :id
              AND a.clinic_id = :clinic_id
              AND a.deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $appointmentId, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findDetailedById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT
                a.id,
                a.clinic_id,
                a.professional_id,
                a.service_id,
                a.patient_id,
                a.patient_procedure_id,
                a.start_at,
                a.end_at,
                a.status,
                a.notes,
                a.created_by_user_id,
                COALESCE(pat.name, '') AS patient_name,
                COALESCE(s.name, '') AS service_name,
                COALESCE(pro.name, '') AS professional_name
            FROM appointments a
            LEFT JOIN patients pat
                   ON pat.id = a.patient_id
                  AND pat.clinic_id = a.clinic_id
                  AND pat.deleted_at IS NULL
            LEFT JOIN services s
                   ON s.id = a.service_id
                  AND s.clinic_id = a.clinic_id
                  AND s.deleted_at IS NULL
            LEFT JOIN professionals pro
                   ON pro.id = a.professional_id
                  AND pro.clinic_id = a.clinic_id
                  AND pro.deleted_at IS NULL
            WHERE a.id = :id
              AND a.clinic_id = :clinic_id
              AND a.deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function setCheckedInAt(int $clinicId, int $appointmentId, ?string $checkedInAt): void
    {
        $sql = "
            UPDATE appointments
               SET checked_in_at = :checked_in_at,
                   updated_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND deleted_at IS NULL
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $appointmentId,
            'clinic_id' => $clinicId,
            'checked_in_at' => $checkedInAt,
        ]);
    }

    public function setStartedAt(int $clinicId, int $appointmentId, ?string $startedAt): void
    {
        $sql = "
            UPDATE appointments
               SET started_at = :started_at,
                   updated_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND deleted_at IS NULL
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $appointmentId,
            'clinic_id' => $clinicId,
            'started_at' => $startedAt,
        ]);
    }

    /** @return list<array<string,mixed>> */
    public function listCheckedInQueueForProfessional(int $clinicId, int $professionalId, int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));

        $sql = "
            SELECT
                a.id,
                a.clinic_id,
                a.professional_id,
                a.service_id,
                a.patient_id,
                a.start_at,
                a.end_at,
                a.checked_in_at,
                a.started_at,
                a.status,
                COALESCE(pat.name, '') AS patient_name,
                COALESCE(s.name, '') AS service_name
            FROM appointments a
            LEFT JOIN patients pat
                   ON pat.id = a.patient_id
                  AND pat.clinic_id = a.clinic_id
                  AND pat.deleted_at IS NULL
            LEFT JOIN services s
                   ON s.id = a.service_id
                  AND s.clinic_id = a.clinic_id
                  AND s.deleted_at IS NULL
            WHERE a.clinic_id = :clinic_id
              AND a.professional_id = :professional_id
              AND a.deleted_at IS NULL
              AND a.checked_in_at IS NOT NULL
              AND a.checked_in_at <> ''
              AND (a.started_at IS NULL OR a.started_at = '')
              AND a.status IN ('scheduled','confirmed','in_progress')
            ORDER BY a.checked_in_at ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'professional_id' => $professionalId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function listUpcomingByPatient(int $clinicId, int $patientId, int $limit = 10, int $offset = 0): array
    {
        $offset = max(0, $offset);
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
            OFFSET " . (int)$offset . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /**
     * Lista agendamentos para opera??o com filtros (usa joins para nome/CPF/servi?o/profissional).
     *
     * @return list<array<string,mixed>>
     */
    public function listOpsFiltered(
        int $clinicId,
        string $dateYmd,
        string $category,
        ?int $professionalId,
        ?int $serviceId,
        ?int $serviceCategoryId,
        ?string $patientName,
        ?string $patientCpf,
        ?string $timeFromHm,
        ?string $timeToHm,
        int $limit = 500
    ): array {
        $dateYmd = trim($dateYmd);
        if ($dateYmd === '') {
            $dateYmd = date('Y-m-d');
        }

        $startAt = $dateYmd . ' 00:00:00';
        $endAt = $dateYmd . ' 23:59:59';

        $where = " a.clinic_id = :clinic_id AND a.deleted_at IS NULL AND a.start_at >= :start_at AND a.start_at <= :end_at ";
        $params = [
            'clinic_id' => $clinicId,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ];

        if ($professionalId !== null && $professionalId > 0) {
            $where .= " AND a.professional_id = :professional_id ";
            $params['professional_id'] = $professionalId;
        }

        if ($serviceId !== null && $serviceId > 0) {
            $where .= " AND a.service_id = :service_id ";
            $params['service_id'] = $serviceId;
        }

        if ($serviceCategoryId !== null && $serviceCategoryId > 0) {
            $where .= " AND s.category_id = :service_category_id ";
            $params['service_category_id'] = $serviceCategoryId;
        }

        $patientName = $patientName !== null ? trim($patientName) : '';
        if ($patientName !== '') {
            $where .= " AND pat.name LIKE :patient_name_like ";
            $params['patient_name_like'] = '%' . $patientName . '%';
        }

        $patientCpf = $patientCpf !== null ? trim($patientCpf) : '';
        if ($patientCpf !== '') {
            $where .= " AND pat.cpf LIKE :patient_cpf_like ";
            $params['patient_cpf_like'] = '%' . $patientCpf . '%';
        }

        $timeFromHm = $timeFromHm !== null ? trim($timeFromHm) : '';
        if ($timeFromHm !== '' && preg_match('/^\d{2}:\d{2}$/', $timeFromHm)) {
            $where .= " AND a.start_at >= :time_from ";
            $params['time_from'] = $dateYmd . ' ' . $timeFromHm . ':00';
        }

        $timeToHm = $timeToHm !== null ? trim($timeToHm) : '';
        if ($timeToHm !== '' && preg_match('/^\d{2}:\d{2}$/', $timeToHm)) {
            $where .= " AND a.start_at <= :time_to ";
            $params['time_to'] = $dateYmd . ' ' . $timeToHm . ':59';
        }

        $category = trim($category);
        if ($category === 'pending') {
            $where .= " AND a.status IN ('scheduled','confirmed','in_progress') ";
        } elseif ($category === 'finalized') {
            $where .= " AND a.status IN ('completed','cancelled','no_show') ";
        }

        $limit = max(1, min($limit, 2000));

        $sql = "
            SELECT
                a.id,
                a.clinic_id,
                a.professional_id,
                a.service_id,
                a.patient_id,
                a.start_at,
                a.end_at,
                a.checked_in_at,
                a.started_at,
                a.status,
                COALESCE(pat.name, '') AS patient_name,
                COALESCE(pat.cpf, '') AS patient_cpf,
                COALESCE(s.name, '') AS service_name,
                COALESCE(sc.name, '') AS service_category_name,
                COALESCE(pro.name, '') AS professional_name
            FROM appointments a
            LEFT JOIN patients pat
                   ON pat.id = a.patient_id
                  AND pat.clinic_id = a.clinic_id
                  AND pat.deleted_at IS NULL
            LEFT JOIN services s
                   ON s.id = a.service_id
                  AND s.clinic_id = a.clinic_id
                  AND s.deleted_at IS NULL
            LEFT JOIN service_categories sc
                   ON sc.id = s.category_id
                  AND sc.clinic_id = a.clinic_id
                  AND sc.deleted_at IS NULL
            LEFT JOIN professionals pro
                   ON pro.id = a.professional_id
                  AND pro.clinic_id = a.clinic_id
                  AND pro.deleted_at IS NULL
            WHERE " . $where . "
            ORDER BY a.start_at ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function setPatientProcedureId(int $clinicId, int $appointmentId, ?int $patientProcedureId): void
    {
        $sql = "
            UPDATE appointments
            SET patient_procedure_id = :patient_procedure_id,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $appointmentId,
            'clinic_id' => $clinicId,
            'patient_procedure_id' => $patientProcedureId,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function listByClinicPatientDetailed(
        int $clinicId,
        int $patientId,
        int $limit = 200,
        int $offset = 0,
        ?string $status = null,
        ?string $startDateYmd = null,
        ?string $endDateYmd = null
    ): array
    {
        $limit = max(1, min($limit, 500));
        $offset = max(0, $offset);

        $where = " a.clinic_id = :clinic_id AND a.patient_id = :patient_id AND a.deleted_at IS NULL ";
        $params = ['clinic_id' => $clinicId, 'patient_id' => $patientId];

        if ($status !== null && trim($status) !== '' && trim($status) !== 'all') {
            $allowed = ['scheduled', 'confirmed', 'in_progress', 'completed', 'no_show', 'cancelled'];
            $status = trim($status);
            if (in_array($status, $allowed, true)) {
                $where .= " AND a.status = :status ";
                $params['status'] = $status;
            }
        }

        if ($startDateYmd !== null && trim($startDateYmd) !== '') {
            $d = \DateTimeImmutable::createFromFormat('Y-m-d', trim($startDateYmd));
            if ($d !== false) {
                $where .= " AND a.start_at >= :start_at_from ";
                $params['start_at_from'] = $d->format('Y-m-d 00:00:00');
            }
        }

        if ($endDateYmd !== null && trim($endDateYmd) !== '') {
            $d = \DateTimeImmutable::createFromFormat('Y-m-d', trim($endDateYmd));
            if ($d !== false) {
                $where .= " AND a.start_at <= :start_at_to ";
                $params['start_at_to'] = $d->format('Y-m-d 23:59:59');
            }
        }

        $sql = "
            SELECT
                a.id,
                a.clinic_id,
                a.professional_id,
                a.service_id,
                a.patient_id,
                a.patient_procedure_id,
                a.start_at,
                a.end_at,
                a.status,
                a.origin,
                a.notes,
                pp.total_sessions AS plan_total_sessions,
                pp.used_sessions AS plan_used_sessions,
                pp.sale_id AS plan_sale_id,
                COALESCE(s.name, '') AS service_name,
                COALESCE(pro.name, '') AS professional_name
            FROM appointments a
            LEFT JOIN patient_procedures pp
                   ON pp.id = a.patient_procedure_id
                  AND pp.clinic_id = a.clinic_id
                  AND pp.deleted_at IS NULL
            LEFT JOIN services s
                   ON s.id = a.service_id
                  AND s.clinic_id = a.clinic_id
                  AND s.deleted_at IS NULL
            LEFT JOIN professionals pro
                   ON pro.id = a.professional_id
                  AND pro.clinic_id = a.clinic_id
                  AND pro.deleted_at IS NULL
            WHERE " . $where . "
            ORDER BY a.start_at DESC
            LIMIT " . (int)$limit . "
            OFFSET " . (int)$offset . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function listByClinicRangeDetailed(int $clinicId, string $startAt, string $endAt, ?int $professionalId = null): array
    {
        $sql = "
            SELECT
                a.id,
                a.clinic_id,
                a.professional_id,
                a.service_id,
                a.patient_id,
                a.start_at,
                a.end_at,
                a.buffer_before_minutes,
                a.buffer_after_minutes,
                a.checked_in_at,
                a.started_at,
                a.status,
                a.origin,
                a.notes,
                COALESCE(pat.name, '') AS patient_name,
                COALESCE(s.name, '') AS service_name,
                COALESCE(pro.name, '') AS professional_name
            FROM appointments a
            LEFT JOIN patients pat
                   ON pat.id = a.patient_id
                  AND pat.clinic_id = a.clinic_id
                  AND pat.deleted_at IS NULL
            LEFT JOIN services s
                   ON s.id = a.service_id
                  AND s.clinic_id = a.clinic_id
                  AND s.deleted_at IS NULL
            LEFT JOIN professionals pro
                   ON pro.id = a.professional_id
                  AND pro.clinic_id = a.clinic_id
                  AND pro.deleted_at IS NULL
            WHERE a.clinic_id = :clinic_id
              AND a.deleted_at IS NULL
              AND a.start_at >= :start_at
              AND a.start_at < :end_at
        ";

        $params = [
            'clinic_id' => $clinicId,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ];

        if ($professionalId !== null) {
            $sql .= " AND a.professional_id = :professional_id ";
            $params['professional_id'] = $professionalId;
        }

        $sql .= " ORDER BY a.start_at ASC ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findByIdForPatient(int $clinicId, int $patientId, int $appointmentId): ?array
    {
        $sql = "
            SELECT id, clinic_id, professional_id, service_id, patient_id, start_at, end_at, checked_in_at, started_at, status, origin, notes
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
            SELECT id, clinic_id, professional_id, service_id, patient_id, start_at, end_at, buffer_before_minutes, buffer_after_minutes, checked_in_at, started_at, status, origin, notes
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
    public function listByClinicRange(int $clinicId, string $startAt, string $endAt, ?int $professionalId = null, ?int $limit = null, int $offset = 0): array
    {
        $sql = "
            SELECT id, clinic_id, professional_id, service_id, patient_id, start_at, end_at, buffer_before_minutes, buffer_after_minutes, checked_in_at, started_at, status, origin, notes
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

        if ($limit !== null) {
            $limit = max(1, min($limit, 5000));
            $offset = max(0, $offset);
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset . " ";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, professional_id, service_id, patient_id, patient_procedure_id, patient_package_id, start_at, end_at, buffer_before_minutes, buffer_after_minutes, checked_in_at, started_at, status, origin, notes
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
        ?int $patientPackageId,
        string $startAt,
        string $endAt,
        int $bufferBeforeMinutes,
        int $bufferAfterMinutes,
        string $status,
        string $origin,
        ?int $funnelStageId,
        ?int $lostReasonId,
        ?string $notes,
        ?int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO appointments (
                clinic_id, professional_id, service_id, patient_id, patient_package_id, start_at, end_at,
                buffer_before_minutes, buffer_after_minutes,
                status, origin, funnel_stage_id, lost_reason_id, notes, created_by_user_id, created_at
            ) VALUES (
                :clinic_id, :professional_id, :service_id, :patient_id, :patient_package_id, :start_at, :end_at,
                :buffer_before_minutes, :buffer_after_minutes,
                :status, :origin, :funnel_stage_id, :lost_reason_id, :notes, :created_by_user_id, NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'professional_id' => $professionalId,
            'service_id' => $serviceId,
            'patient_id' => $patientId,
            'patient_package_id' => ($patientPackageId !== null && $patientPackageId > 0 ? $patientPackageId : null),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'buffer_before_minutes' => max(0, (int)$bufferBeforeMinutes),
            'buffer_after_minutes' => max(0, (int)$bufferAfterMinutes),
            'status' => $status,
            'origin' => $origin,
            'funnel_stage_id' => ($funnelStageId !== null && $funnelStageId > 0 ? $funnelStageId : null),
            'lost_reason_id' => ($lostReasonId !== null && $lostReasonId > 0 ? $lostReasonId : null),
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
