<?php

declare(strict_types=1);

namespace App\Repositories;

final class AuditLogQueryRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @param array{action:string,from:string,to:string} $filters */
    /** @return list<array<string, mixed>> */
    public function search(int $clinicId, array $filters, int $limit, int $offset = 0): array
    {
        $where = ['clinic_id = :clinic_id'];
        $params = ['clinic_id' => $clinicId];

        if ($filters['action'] !== '') {
            $where[] = 'action = :action';
            $params['action'] = $filters['action'];
        }

        if ($filters['from'] !== '') {
            $where[] = 'created_at >= :from_dt';
            $params['from_dt'] = $filters['from'] . ' 00:00:00';
        }

        if ($filters['to'] !== '') {
            $where[] = 'created_at <= :to_dt';
            $params['to_dt'] = $filters['to'] . ' 23:59:59';
        }

        $limit = max(1, min($limit, 5000));
        $offset = max(0, $offset);

        $sql = "
            SELECT id, clinic_id, user_id, role_codes_json, action, entity_type, entity_id, meta_json, ip_address, user_agent, occurred_at, created_at
            FROM audit_logs
            WHERE " . implode(' AND ', $where) . "
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT {$limit}
            OFFSET {$offset}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }
}
