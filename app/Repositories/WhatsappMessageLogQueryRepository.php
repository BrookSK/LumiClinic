<?php

declare(strict_types=1);

namespace App\Repositories;

final class WhatsappMessageLogQueryRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /**
     * @param array{status?:string,template_code?:string,from?:string,to?:string,appointment_id?:string,patient_id?:string} $filters
     * @return list<array<string,mixed>>
     */
    public function search(int $clinicId, array $filters, int $limit = 100, int $offset = 0): array
    {
        $limit = max(1, min($limit, 5000));
        $offset = max(0, $offset);

        $where = " l.clinic_id = :clinic_id ";
        $params = ['clinic_id' => $clinicId];

        $status = isset($filters['status']) ? trim((string)$filters['status']) : '';
        if ($status !== '' && $status !== 'all') {
            $allowed = ['pending', 'processing', 'sent', 'failed', 'skipped', 'cancelled'];
            if (in_array($status, $allowed, true)) {
                $where .= " AND l.status = :status ";
                $params['status'] = $status;
            }
        }

        $templateCode = isset($filters['template_code']) ? trim((string)$filters['template_code']) : '';
        if ($templateCode !== '' && $templateCode !== 'all') {
            $where .= " AND l.template_code = :template_code ";
            $params['template_code'] = $templateCode;
        }

        $appointmentId = isset($filters['appointment_id']) ? trim((string)$filters['appointment_id']) : '';
        if ($appointmentId !== '' && ctype_digit($appointmentId) && (int)$appointmentId > 0) {
            $where .= " AND l.appointment_id = :appointment_id ";
            $params['appointment_id'] = (int)$appointmentId;
        }

        $patientId = isset($filters['patient_id']) ? trim((string)$filters['patient_id']) : '';
        if ($patientId !== '' && ctype_digit($patientId) && (int)$patientId > 0) {
            $where .= " AND l.patient_id = :patient_id ";
            $params['patient_id'] = (int)$patientId;
        }

        $from = isset($filters['from']) ? trim((string)$filters['from']) : '';
        if ($from !== '') {
            $d = \DateTimeImmutable::createFromFormat('Y-m-d', $from);
            if ($d !== false) {
                $where .= " AND l.scheduled_for >= :from_dt ";
                $params['from_dt'] = $d->format('Y-m-d 00:00:00');
            }
        }

        $to = isset($filters['to']) ? trim((string)$filters['to']) : '';
        if ($to !== '') {
            $d = \DateTimeImmutable::createFromFormat('Y-m-d', $to);
            if ($d !== false) {
                $where .= " AND l.scheduled_for <= :to_dt ";
                $params['to_dt'] = $d->format('Y-m-d 23:59:59');
            }
        }

        $sql = "
            SELECT
                l.id,
                l.clinic_id,
                l.patient_id,
                l.appointment_id,
                l.template_code,
                l.scheduled_for,
                l.status,
                l.sent_at,
                l.provider_message_id,
                l.created_at,
                l.updated_at,
                COALESCE(p.name, '') AS patient_name
            FROM whatsapp_message_logs l
            LEFT JOIN patients p
                   ON p.id = l.patient_id
                  AND p.clinic_id = l.clinic_id
                  AND p.deleted_at IS NULL
            WHERE {$where}
            ORDER BY l.id DESC
            LIMIT {$limit}
            OFFSET {$offset}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findByIdDetailed(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT
                l.id,
                l.clinic_id,
                l.patient_id,
                l.appointment_id,
                l.template_code,
                l.scheduled_for,
                l.status,
                l.sent_at,
                l.provider_message_id,
                l.payload_json,
                l.response_json,
                l.error_message,
                l.created_at,
                l.updated_at,
                COALESCE(p.name, '') AS patient_name
            FROM whatsapp_message_logs l
            LEFT JOIN patients p
                   ON p.id = l.patient_id
                  AND p.clinic_id = l.clinic_id
                  AND p.deleted_at IS NULL
            WHERE l.id = :id
              AND l.clinic_id = :clinic_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
