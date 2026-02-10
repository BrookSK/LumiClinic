<?php

declare(strict_types=1);

namespace App\Repositories;

final class SystemErrorLogRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @param array<string,mixed> $data */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO system_error_logs (
                status_code,
                error_type,
                message,
                method,
                path,
                clinic_id,
                user_id,
                is_super_admin,
                ip,
                user_agent,
                trace_text,
                context_json,
                created_at
            ) VALUES (
                :status_code,
                :error_type,
                :message,
                :method,
                :path,
                :clinic_id,
                :user_id,
                :is_super_admin,
                :ip,
                :user_agent,
                :trace_text,
                :context_json,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'status_code' => (int)($data['status_code'] ?? 500),
            'error_type' => (string)($data['error_type'] ?? 'exception'),
            'message' => (string)($data['message'] ?? ''),
            'method' => (string)($data['method'] ?? 'GET'),
            'path' => (string)($data['path'] ?? '/'),
            'clinic_id' => ($data['clinic_id'] ?? null),
            'user_id' => ($data['user_id'] ?? null),
            'is_super_admin' => (int)($data['is_super_admin'] ?? 0),
            'ip' => ($data['ip'] ?? null),
            'user_agent' => ($data['user_agent'] ?? null),
            'trace_text' => ($data['trace_text'] ?? null),
            'context_json' => ($data['context_json'] ?? null),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function listLatest(int $limit = 200, int $offset = 0, ?int $statusCode = null): array
    {
        $limit = max(1, min($limit, 500));
        $offset = max(0, $offset);

        $where = '';
        $params = [];
        if ($statusCode !== null) {
            $where = 'WHERE status_code = :status_code';
            $params['status_code'] = $statusCode;
        }

        $stmt = $this->pdo->prepare("\n            SELECT id, status_code, error_type, message, method, path, clinic_id, user_id, is_super_admin, ip, created_at\n            FROM system_error_logs\n            {$where}\n            ORDER BY id DESC\n            LIMIT {$limit}\n            OFFSET {$offset}\n        ");
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("\n            SELECT *
            FROM system_error_logs
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
