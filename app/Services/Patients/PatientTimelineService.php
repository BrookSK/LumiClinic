<?php

declare(strict_types=1);

namespace App\Services\Patients;

use App\Core\Container\Container;
use App\Repositories\AppointmentRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\ConsentAcceptanceRepository;
use App\Repositories\MedicalImageRepository;
use App\Repositories\MedicalRecordRepository;
use App\Repositories\PatientRepository;
use App\Repositories\SignatureRepository;
use App\Services\Auth\AuthService;
use App\Services\Compliance\SensitiveDataAuditService;

final class PatientTimelineService
{
    public function __construct(private readonly Container $container) {}

    /** @param array{types?:?string,from?:?string,to?:?string,limit?:?int} $filters */
    public function list(int $patientId, array $filters, string $ip, ?string $userAgent = null): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $patient = (new PatientRepository($pdo))->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $types = $this->parseTypes(isset($filters['types']) ? (string)$filters['types'] : '');
        $from = isset($filters['from']) ? trim((string)$filters['from']) : '';
        $to = isset($filters['to']) ? trim((string)$filters['to']) : '';
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 200;
        $limit = max(50, min(2000, $limit));

        $items = [];

        if ($this->typeAllowed($types, 'consultation')) {
            foreach ($this->listConsultations($clinicId, $patientId, $from, $to, $limit) as $r) {
                $items[] = [
                    'type' => 'consultation',
                    'occurred_at' => (string)($r['executed_at'] ?? ''),
                    'title' => 'Consulta executada',
                    'description' => trim((string)($r['notes'] ?? '')),
                    'ref' => [
                        'consultation_id' => (int)($r['id'] ?? 0),
                        'appointment_id' => (int)($r['appointment_id'] ?? 0),
                    ],
                    'link' => '/patients/consultation?appointment_id=' . (int)($r['appointment_id'] ?? 0),
                ];
            }
        }

        if ($this->typeAllowed($types, 'consultation_attachment')) {
            foreach ($this->listConsultationAttachments($clinicId, $patientId, $from, $to, $limit) as $r) {
                $items[] = [
                    'type' => 'consultation_attachment',
                    'occurred_at' => (string)($r['created_at'] ?? ''),
                    'title' => 'Anexo de consulta',
                    'description' => trim((string)($r['note'] ?? '')),
                    'ref' => [
                        'consultation_attachment_id' => (int)($r['id'] ?? 0),
                        'consultation_id' => (int)($r['consultation_id'] ?? 0),
                        'appointment_id' => (int)($r['appointment_id'] ?? 0),
                    ],
                    'link' => '/patients/consultation/attachments/file?id=' . (int)($r['id'] ?? 0),
                ];
            }
        }

        if ($this->typeAllowed($types, 'appointment')) {
            $apptRepo = new AppointmentRepository($pdo);
            foreach ($apptRepo->listByClinicPatientDetailed($clinicId, $patientId, min($limit, 500), 0, null, $from, $to) as $a) {
                $items[] = [
                    'type' => 'appointment',
                    'occurred_at' => (string)($a['start_at'] ?? ''),
                    'title' => 'Agendamento',
                    'description' => trim((string)($a['service_name'] ?? '')),
                    'ref' => ['appointment_id' => (int)($a['id'] ?? 0), 'status' => (string)($a['status'] ?? '')],
                    'link' => '/schedule?view=week&date=' . urlencode(substr((string)($a['start_at'] ?? ''), 0, 10)),
                ];
            }
        }

        if ($this->typeAllowed($types, 'medical_record')) {
            $repo = new MedicalRecordRepository($pdo);
            $mrFilters = [];
            if ($from !== '') {
                $mrFilters['date_from'] = $from . ' 00:00:00';
            }
            if ($to !== '') {
                $mrFilters['date_to'] = $to . ' 23:59:59';
            }

            $records = $mrFilters !== []
                ? $repo->listByPatientFiltered($clinicId, $patientId, $mrFilters, $limit)
                : $repo->listByPatient($clinicId, $patientId, $limit);

            foreach ($records as $r) {
                $items[] = [
                    'type' => 'medical_record',
                    'occurred_at' => (string)($r['attended_at'] ?? ''),
                    'title' => 'Prontuário',
                    'description' => trim((string)($r['procedure_type'] ?? '')),
                    'ref' => ['medical_record_id' => (int)($r['id'] ?? 0)],
                    'link' => '/medical-records?patient_id=' . $patientId . '#mr-' . (int)($r['id'] ?? 0),
                ];
            }
        }

        if ($this->typeAllowed($types, 'medical_image')) {
            $repo = new MedicalImageRepository($pdo);
            foreach ($repo->listByPatient($clinicId, $patientId, $limit) as $r) {
                $at = (string)(($r['taken_at'] ?? '') !== '' ? $r['taken_at'] : ($r['created_at'] ?? ''));
                if (!$this->dateAllowed($at, $from, $to)) {
                    continue;
                }

                $items[] = [
                    'type' => 'medical_image',
                    'occurred_at' => $at,
                    'title' => 'Imagem clínica',
                    'description' => trim((string)($r['procedure_type'] ?? '')),
                    'ref' => ['medical_image_id' => (int)($r['id'] ?? 0), 'kind' => (string)($r['kind'] ?? '')],
                    'link' => '/medical-images/file?id=' . (int)($r['id'] ?? 0),
                ];
            }
        }

        if ($this->typeAllowed($types, 'consent_acceptance')) {
            $repo = new ConsentAcceptanceRepository($pdo);
            foreach ($repo->listByPatient($clinicId, $patientId, $limit) as $r) {
                $at = (string)($r['accepted_at'] ?? '');
                if (!$this->dateAllowed($at, $from, $to)) {
                    continue;
                }

                $items[] = [
                    'type' => 'consent_acceptance',
                    'occurred_at' => $at,
                    'title' => 'Aceite de termo',
                    'description' => trim((string)($r['term_title_snapshot'] ?? '')),
                    'ref' => ['acceptance_id' => (int)($r['id'] ?? 0), 'term_id' => (int)($r['term_id'] ?? 0)],
                    'link' => '/consent/export?id=' . (int)($r['id'] ?? 0),
                ];
            }
        }

        if ($this->typeAllowed($types, 'signature')) {
            $repo = new SignatureRepository($pdo);
            foreach ($repo->listByPatient($clinicId, $patientId, $limit) as $r) {
                $at = (string)($r['created_at'] ?? '');
                if (!$this->dateAllowed($at, $from, $to)) {
                    continue;
                }

                $items[] = [
                    'type' => 'signature',
                    'occurred_at' => $at,
                    'title' => 'Assinatura',
                    'description' => (string)((int)($r['term_acceptance_id'] ?? 0) > 0 ? ('Aceite #' . (int)$r['term_acceptance_id']) : 'Documento'),
                    'ref' => ['signature_id' => (int)($r['id'] ?? 0)],
                    'link' => '/signatures/file?id=' . (int)($r['id'] ?? 0),
                ];
            }
        }

        usort($items, function (array $a, array $b): int {
            $da = (string)($a['occurred_at'] ?? '');
            $db = (string)($b['occurred_at'] ?? '');
            if ($da === $db) {
                return 0;
            }
            return $da < $db ? 1 : -1;
        });

        if (count($items) > $limit) {
            $items = array_slice($items, 0, $limit);
        }

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log(
            $actorId,
            $clinicId,
            'patients.timeline.view',
            ['patient_id' => $patientId, 'filters' => ['types' => array_values($types), 'from' => $from, 'to' => $to]],
            $ip,
            $roleCodes,
            'patient',
            $patientId,
            $userAgent
        );

        (new SensitiveDataAuditService($this->container))->access(
            'sensitive.access',
            'patient',
            $patientId,
            ['module' => 'patient_timeline', 'action' => 'view', 'patient_id' => $patientId, 'filters' => ['types' => array_values($types), 'from' => $from, 'to' => $to]],
            $ip,
            $userAgent
        );

        return [
            'patient' => $patient,
            'items' => $items,
            'filters' => ['types' => array_values($types), 'from' => $from, 'to' => $to, 'limit' => $limit],
        ];
    }

    private function typeAllowed(array $types, string $type): bool
    {
        if ($types === []) {
            return true;
        }
        return in_array($type, $types, true);
    }

    /** @return list<string> */
    private function parseTypes(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        $parts = array_values(array_filter(array_map('trim', explode(',', $raw)), fn ($v) => is_string($v) && $v !== ''));
        $allowed = ['appointment', 'consultation', 'consultation_attachment', 'medical_record', 'medical_image', 'consent_acceptance', 'signature'];
        $out = [];
        foreach ($parts as $p) {
            if (in_array($p, $allowed, true) && !in_array($p, $out, true)) {
                $out[] = $p;
            }
        }
        return $out;
    }

    private function dateAllowed(string $dt, string $fromYmd, string $toYmd): bool
    {
        $dt = trim($dt);
        if ($dt === '') {
            return false;
        }

        $ymd = substr($dt, 0, 10);
        if ($fromYmd !== '' && $ymd < $fromYmd) {
            return false;
        }
        if ($toYmd !== '' && $ymd > $toYmd) {
            return false;
        }
        return true;
    }

    /** @return list<array<string,mixed>> */
    private function listConsultations(int $clinicId, int $patientId, string $fromYmd, string $toYmd, int $limit): array
    {
        $limit = max(1, min(2000, $limit));

        $where = [
            'c.clinic_id = :clinic_id',
            'c.patient_id = :patient_id',
            'c.deleted_at IS NULL',
        ];
        $params = ['clinic_id' => $clinicId, 'patient_id' => $patientId];

        if ($fromYmd !== '') {
            $where[] = 'c.executed_at >= :from_dt';
            $params['from_dt'] = $fromYmd . ' 00:00:00';
        }
        if ($toYmd !== '') {
            $where[] = 'c.executed_at <= :to_dt';
            $params['to_dt'] = $toYmd . ' 23:59:59';
        }

        $sql = "
            SELECT c.id, c.appointment_id, c.executed_at, c.notes
            FROM consultations c
            WHERE " . implode(' AND ', $where) . "
            ORDER BY c.executed_at DESC, c.id DESC
            LIMIT {$limit}
        ";

        $pdo = $this->container->get(\PDO::class);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    private function listConsultationAttachments(int $clinicId, int $patientId, string $fromYmd, string $toYmd, int $limit): array
    {
        $limit = max(1, min(2000, $limit));

        $where = [
            'a.clinic_id = :clinic_id',
            'a.patient_id = :patient_id',
            'a.deleted_at IS NULL',
        ];
        $params = ['clinic_id' => $clinicId, 'patient_id' => $patientId];

        if ($fromYmd !== '') {
            $where[] = 'a.created_at >= :from_dt';
            $params['from_dt'] = $fromYmd . ' 00:00:00';
        }
        if ($toYmd !== '') {
            $where[] = 'a.created_at <= :to_dt';
            $params['to_dt'] = $toYmd . ' 23:59:59';
        }

        $sql = "
            SELECT a.id, a.consultation_id, a.patient_id, a.note, a.created_at,
                   c.appointment_id
            FROM consultation_attachments a
            LEFT JOIN consultations c
                   ON c.id = a.consultation_id
                  AND c.clinic_id = a.clinic_id
                  AND c.deleted_at IS NULL
            WHERE " . implode(' AND ', $where) . "
            ORDER BY a.created_at DESC, a.id DESC
            LIMIT {$limit}
        ";

        $pdo = $this->container->get(\PDO::class);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }
}
