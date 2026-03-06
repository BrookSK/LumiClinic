<?php

declare(strict_types=1);

namespace App\Repositories;

final class DataExportQueryRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @param array{action:string,entity_type:string,entity_id:int,from:string,to:string} $filters */
    /** @return list<array<string, mixed>> */
    public function search(int $clinicId, array $filters, int $limit, int $offset = 0): array
    {
        $where = ['clinic_id = :clinic_id', 'deleted_at IS NULL'];
        $params = ['clinic_id' => $clinicId];

        $action = trim((string)($filters['action'] ?? ''));
        if ($action !== '') {
            $where[] = 'action = :action';
            $params['action'] = $action;
        }

        $entityType = trim((string)($filters['entity_type'] ?? ''));
        if ($entityType !== '') {
            $where[] = 'entity_type = :entity_type';
            $params['entity_type'] = $entityType;
        }

        $entityId = (int)($filters['entity_id'] ?? 0);
        if ($entityId > 0) {
            $where[] = 'entity_id = :entity_id';
            $params['entity_id'] = $entityId;
        }

        $from = trim((string)($filters['from'] ?? ''));
        if ($from !== '') {
            $where[] = 'created_at >= :from_dt';
            $params['from_dt'] = $from . ' 00:00:00';
        }

        $to = trim((string)($filters['to'] ?? ''));
        if ($to !== '') {
            $where[] = 'created_at <= :to_dt';
            $params['to_dt'] = $to . ' 23:59:59';
        }

        $limit = max(1, min($limit, 5000));
        $offset = max(0, $offset);

        $sql = "
            SELECT id, clinic_id, user_id, action, entity_type, entity_id, format, filename, meta_json, ip_address, user_agent, created_at
            FROM data_exports
            WHERE " . implode(' AND ', $where) . "
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
