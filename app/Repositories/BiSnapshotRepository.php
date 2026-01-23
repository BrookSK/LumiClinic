<?php

declare(strict_types=1);

namespace App\Repositories;

final class BiSnapshotRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /**
     * @param array<string,mixed> $data
     */
    public function upsert(
        int $clinicId,
        string $metricKey,
        string $periodStartYmd,
        string $periodEndYmd,
        array $data,
        ?int $computedByUserId
    ): void {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Falha ao serializar BI snapshot.');
        }

        $sql = "
            INSERT INTO bi_snapshots (
                clinic_id, metric_key, period_start, period_end,
                data_json,
                computed_by_user_id, computed_at, created_at
            ) VALUES (
                :clinic_id, :metric_key, :period_start, :period_end,
                CAST(:data_json AS JSON),
                :computed_by_user_id, NOW(), NOW()
            )
            ON DUPLICATE KEY UPDATE
                data_json = VALUES(data_json),
                computed_by_user_id = VALUES(computed_by_user_id),
                computed_at = NOW()
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'metric_key' => $metricKey,
            'period_start' => $periodStartYmd,
            'period_end' => $periodEndYmd,
            'data_json' => $json,
            'computed_by_user_id' => $computedByUserId,
        ]);
    }

    /** @return array<string,mixed>|null */
    public function latestByMetric(int $clinicId, string $metricKey): ?array
    {
        $sql = "
            SELECT metric_key, period_start, period_end, data_json, computed_at
            FROM bi_snapshots
            WHERE clinic_id = :clinic_id
              AND metric_key = :metric_key
            ORDER BY computed_at DESC, id DESC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'metric_key' => $metricKey]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $data = [];
        if (isset($row['data_json'])) {
            $decoded = json_decode((string)$row['data_json'], true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        return [
            'metric_key' => (string)$row['metric_key'],
            'period_start' => (string)$row['period_start'],
            'period_end' => (string)$row['period_end'],
            'data' => $data,
            'computed_at' => (string)$row['computed_at'],
        ];
    }
}
