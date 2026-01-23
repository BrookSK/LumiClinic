<?php

declare(strict_types=1);

namespace App\Repositories;

final class PerformanceLogRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function log(string $endpoint, string $method, int $responseTimeMs, int $statusCode, ?int $clinicId): void
    {
        $stmt = $this->pdo->prepare("\n            INSERT INTO performance_logs (
                endpoint, method, response_time_ms, status_code, clinic_id, created_at
            ) VALUES (
                :endpoint, :method, :response_time_ms, :status_code, :clinic_id, NOW()
            )
        ");

        $stmt->execute([
            'endpoint' => $endpoint,
            'method' => $method,
            'response_time_ms' => max(0, $responseTimeMs),
            'status_code' => max(0, $statusCode),
            'clinic_id' => $clinicId,
        ]);
    }
}
