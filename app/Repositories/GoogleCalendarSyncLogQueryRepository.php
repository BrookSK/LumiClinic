<?php

declare(strict_types=1);

namespace App\Repositories;

final class GoogleCalendarSyncLogQueryRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /**
     * @param int $clinicId
     * @param array{status?:string,action?:string,from?:string,to?:string,appointment_id?:string,user_id?:string} $filters
     * @return list<array<string,mixed>>
     */
    public function listByClinic(int $clinicId, array $filters, int $limit = 200, int $offset = 0): array
    {
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);

        $where = ['l.clinic_id = :clinic_id'];
        $params = ['clinic_id' => $clinicId];

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = 'l.status = :status';
            $params['status'] = $status;
        }

        $action = trim((string)($filters['action'] ?? ''));
        if ($action !== '') {
            $where[] = 'l.action = :action';
            $params['action'] = $action;
        }

        $appointmentId = (int)($filters['appointment_id'] ?? 0);
        if ($appointmentId > 0) {
            $where[] = 'l.appointment_id = :appointment_id';
            $params['appointment_id'] = $appointmentId;
        }

        $userId = (int)($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $where[] = 'l.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $from = trim((string)($filters['from'] ?? ''));
        if ($from !== '') {
            $d = \DateTimeImmutable::createFromFormat('Y-m-d', $from);
            if ($d !== false) {
                $where[] = 'l.created_at >= :from_dt';
                $params['from_dt'] = $d->format('Y-m-d 00:00:00');
            }
        }

        $to = trim((string)($filters['to'] ?? ''));
        if ($to !== '') {
            $d = \DateTimeImmutable::createFromFormat('Y-m-d', $to);
            if ($d !== false) {
                $where[] = 'l.created_at <= :to_dt';
                $params['to_dt'] = $d->format('Y-m-d 23:59:59');
            }
        }

        $sql = "
            SELECT
                l.id,
                l.clinic_id,
                l.user_id,
                u.name AS user_name,
                l.token_id,
                l.appointment_id,
                l.action,
                l.status,
                l.message,
                l.meta_json,
                l.created_at
            FROM google_calendar_sync_logs l
            LEFT JOIN users u ON u.id = l.user_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY l.id DESC
            LIMIT {$limit}
            OFFSET {$offset}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }
}
