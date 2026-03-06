<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use App\Core\Container\Container;
use App\Repositories\AuditLogQueryRepository;
use App\Repositories\DataExportQueryRepository;
use App\Services\Auth\AuthService;

final class LgpdAuditService
{
    public function __construct(private readonly Container $container) {}

    /**
     * @param array{from:string,to:string,patient_id:int,user_id:int} $filters
     * @return array{sensitive:list<array<string,mixed>>,exports:list<array<string,mixed>>}
     */
    public function list(array $filters, int $limit = 200, int $offset = 0): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $limit = max(1, min($limit, 1000));
        $offset = max(0, $offset);

        $from = trim((string)($filters['from'] ?? ''));
        $to = trim((string)($filters['to'] ?? ''));
        $patientId = (int)($filters['patient_id'] ?? 0);
        $userId = (int)($filters['user_id'] ?? 0);

        $pdo = $this->container->get(\PDO::class);

        // Sensitive access events live in audit_logs.
        // We query by action prefix (sensitive.*) plus key health modules (medical_records.*).
        $where = [
            'clinic_id = :clinic_id',
            'deleted_at IS NULL',
            '(action LIKE :sensitive_prefix OR action LIKE :medical_records_prefix)',
        ];
        $params = [
            'clinic_id' => $clinicId,
            'sensitive_prefix' => 'sensitive.%',
            'medical_records_prefix' => 'medical_records.%',
        ];

        if ($from !== '') {
            $where[] = 'created_at >= :from_dt';
            $params['from_dt'] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $where[] = 'created_at <= :to_dt';
            $params['to_dt'] = $to . ' 23:59:59';
        }
        if ($patientId > 0) {
            $where[] = '(entity_type = \'patient\' AND entity_id = :patient_id)';
            $params['patient_id'] = $patientId;
        }
        if ($userId > 0) {
            $where[] = 'user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $sql = "
            SELECT id, clinic_id, user_id, role_codes_json, action, entity_type, entity_id, meta_json, ip_address, user_agent, occurred_at, created_at
            FROM audit_logs
            WHERE " . implode(' AND ', $where) . "
            ORDER BY id DESC
            LIMIT {$limit}
            OFFSET {$offset}
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sensitiveRows = $stmt->fetchAll();
        if (!is_array($sensitiveRows)) {
            $sensitiveRows = [];
        }

        $exportsRepo = new DataExportQueryRepository($pdo);
        $exports = $exportsRepo->search($clinicId, [
            'action' => '',
            'entity_type' => $patientId > 0 ? 'patient' : '',
            'entity_id' => $patientId,
            'from' => $from,
            'to' => $to,
        ], $limit, $offset);

        if ($userId > 0) {
            $exports = array_values(array_filter($exports, fn ($r) => isset($r['user_id']) && (int)$r['user_id'] === $userId));
        }

        return [
            'sensitive' => $sensitiveRows,
            'exports' => $exports,
        ];
    }
}
