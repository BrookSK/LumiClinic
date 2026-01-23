<?php

declare(strict_types=1);

namespace App\Repositories;

final class SystemMetricRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function record(?int $clinicId, string $metric, float $value, string $referenceDateYmd): void
    {
        $metric = trim($metric);
        if ($metric === '') {
            throw new \RuntimeException('Metric inválida.');
        }

        $stmt = $this->pdo->prepare("\n            INSERT INTO system_metrics (clinic_id, metric, value, reference_date, created_at)
            VALUES (:clinic_id, :metric, :value, :reference_date, NOW())
        ");

        $stmt->execute([
            'clinic_id' => $clinicId,
            'metric' => $metric,
            'value' => $value,
            'reference_date' => $referenceDateYmd,
        ]);
    }

    /** @return list<array<string,mixed>> */
    public function latestByClinic(?int $clinicId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));

        $sql = "
            SELECT metric, value, reference_date, created_at
            FROM system_metrics
            WHERE clinic_id " . ($clinicId === null ? "IS NULL" : "= :clinic_id") . "
            ORDER BY reference_date DESC, id DESC
            LIMIT {$limit}
        ";

        $stmt = $this->pdo->prepare($sql);
        $params = $clinicId === null ? [] : ['clinic_id' => $clinicId];
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
