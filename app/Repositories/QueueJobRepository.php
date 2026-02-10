<?php

declare(strict_types=1);

namespace App\Repositories;

final class QueueJobRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listLatest(?string $status = null, int $limit = 200): array
    {
        $limit = max(1, min($limit, 500));

        $allowed = ['pending', 'processing', 'done', 'dead'];
        $status = $status !== null ? trim($status) : null;
        if ($status === '') {
            $status = null;
        }
        if ($status !== null && !in_array($status, $allowed, true)) {
            $status = null;
        }

        if ($status === null) {
            $stmt = $this->pdo->prepare("\n                SELECT q.id,
                       q.clinic_id,
                       c.name AS clinic_name,
                       q.queue, q.job_type, q.payload_json, q.status,
                       q.attempts, q.max_attempts,
                       q.run_at,
                       q.locked_at, q.locked_by,
                       q.last_error,
                       q.created_at, q.updated_at
                FROM queue_jobs q
                LEFT JOIN clinics c ON c.id = q.clinic_id
                ORDER BY q.id DESC
                LIMIT :limit
            ");
            $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->prepare("\n            SELECT q.id,
                   q.clinic_id,
                   c.name AS clinic_name,
                   q.queue, q.job_type, q.payload_json, q.status,
                   q.attempts, q.max_attempts,
                   q.run_at,
                   q.locked_at, q.locked_by,
                   q.last_error,
                   q.created_at, q.updated_at
            FROM queue_jobs q
            LEFT JOIN clinics c ON c.id = q.clinic_id
            WHERE q.status = :status
            ORDER BY q.id DESC
            LIMIT :limit
        ");
        $stmt->bindValue('status', $status);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function retryDead(int $jobId): void
    {
        $sql = "\n            UPDATE queue_jobs\n            SET status = 'pending',\n                run_at = NOW(),\n                locked_at = NULL,\n                locked_by = NULL,\n                updated_at = NOW()\n            WHERE id = :id\n              AND status = 'dead'\n            LIMIT 1\n        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $jobId]);
    }

    public function enqueue(
        ?int $clinicId,
        string $jobType,
        array $payload,
        string $queue = 'default',
        ?string $runAt = null,
        int $maxAttempts = 10
    ): int {
        $queue = trim($queue);
        if ($queue === '') {
            $queue = 'default';
        }

        $jobType = trim($jobType);
        if ($jobType === '') {
            throw new \RuntimeException('job_type inválido.');
        }

        $maxAttempts = max(1, min($maxAttempts, 50));

        $runAt = $runAt === null || trim($runAt) === ''
            ? (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s')
            : $runAt;

        $sql = "
            INSERT INTO queue_jobs (
                clinic_id,
                queue, job_type, payload_json,
                status, attempts, max_attempts,
                run_at,
                created_at
            ) VALUES (
                :clinic_id,
                :queue, :job_type, :payload_json,
                'pending', 0, :max_attempts,
                :run_at,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'queue' => $queue,
            'job_type' => $jobType,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'max_attempts' => $maxAttempts,
            'run_at' => $runAt,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public function reserveNext(string $queue, string $workerId, int $lockSeconds = 60): ?array
    {
        $queue = trim($queue);
        if ($queue === '') {
            $queue = 'default';
        }

        $workerId = trim($workerId);
        if ($workerId === '') {
            throw new \RuntimeException('workerId inválido.');
        }

        $lockSeconds = max(10, min($lockSeconds, 3600));

        $this->pdo->beginTransaction();
        try {
            $sql = "
                SELECT id, clinic_id, queue, job_type, payload_json, status, attempts, max_attempts, run_at, locked_at, locked_by, last_error, created_at
                FROM queue_jobs
                WHERE queue = :queue
                  AND status = 'pending'
                  AND run_at <= NOW()
                  AND (locked_at IS NULL OR locked_at < (NOW() - INTERVAL {$lockSeconds} SECOND))
                ORDER BY run_at ASC, id ASC
                LIMIT 1
                FOR UPDATE
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['queue' => $queue]);
            $row = $stmt->fetch();
            if (!$row) {
                $this->pdo->commit();
                return null;
            }

            $id = (int)$row['id'];

            $upd = "
                UPDATE queue_jobs
                SET status = 'processing',
                    locked_at = NOW(),
                    locked_by = :locked_by,
                    updated_at = NOW()
                WHERE id = :id
                  AND status = 'pending'
            ";

            $u = $this->pdo->prepare($upd);
            $u->execute(['id' => $id, 'locked_by' => $workerId]);

            $stmt2 = $this->pdo->prepare("
                SELECT id, clinic_id, queue, job_type, payload_json, status, attempts, max_attempts, run_at, locked_at, locked_by, last_error, created_at
                FROM queue_jobs
                WHERE id = :id
                LIMIT 1
            ");
            $stmt2->execute(['id' => $id]);
            $job = $stmt2->fetch();

            $this->pdo->commit();
            return $job ?: null;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function ack(int $jobId): void
    {
        $sql = "
            UPDATE queue_jobs
            SET status = 'done',
                locked_at = NULL,
                locked_by = NULL,
                updated_at = NOW()
            WHERE id = :id
              AND status = 'processing'
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $jobId]);
    }

    public function release(int $jobId, int $delaySeconds, ?string $errorMessage): void
    {
        $delaySeconds = max(0, min($delaySeconds, 86400));

        $sql = "
            UPDATE queue_jobs
            SET status = 'pending',
                attempts = attempts + 1,
                run_at = (NOW() + INTERVAL {$delaySeconds} SECOND),
                locked_at = NULL,
                locked_by = NULL,
                last_error = :last_error,
                updated_at = NOW()
            WHERE id = :id
              AND status = 'processing'
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $jobId,
            'last_error' => $errorMessage,
        ]);
    }

    public function markDead(int $jobId, ?string $errorMessage): void
    {
        $sql = "
            UPDATE queue_jobs
            SET status = 'dead',
                attempts = attempts + 1,
                locked_at = NULL,
                locked_by = NULL,
                last_error = :last_error,
                updated_at = NOW()
            WHERE id = :id
              AND status = 'processing'
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $jobId,
            'last_error' => $errorMessage,
        ]);
    }

    public function shouldDeadLetter(array $job): bool
    {
        $attempts = (int)($job['attempts'] ?? 0);
        $max = (int)($job['max_attempts'] ?? 10);
        return ($attempts + 1) >= $max;
    }
}
