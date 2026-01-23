<?php

declare(strict_types=1);

namespace App\Services\Observability;

use App\Core\Container\Container;

final class ObservabilityRetentionService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{event_logs:int,performance_logs:int,system_metrics:int} */
    public function purge(): array
    {
        $config = $this->container->get('config');
        $obs = is_array($config) && isset($config['observability']) && is_array($config['observability'])
            ? $config['observability']
            : [];

        $eventDays = (int)($obs['retention_days_event_logs'] ?? 90);
        $perfDays = (int)($obs['retention_days_performance_logs'] ?? 30);
        $metricDays = (int)($obs['retention_days_system_metrics'] ?? 365);

        $eventDays = max(1, min(3650, $eventDays));
        $perfDays = max(1, min(3650, $perfDays));
        $metricDays = max(1, min(3650, $metricDays));

        $pdo = $this->container->get(\PDO::class);

        $counts = [
            'event_logs' => $this->purgeTable($pdo, 'event_logs', $eventDays),
            'performance_logs' => $this->purgeTable($pdo, 'performance_logs', $perfDays),
            'system_metrics' => $this->purgeTable($pdo, 'system_metrics', $metricDays),
        ];

        return $counts;
    }

    private function purgeTable(\PDO $pdo, string $table, int $days): int
    {
        $allowed = ['event_logs', 'performance_logs', 'system_metrics'];
        if (!in_array($table, $allowed, true)) {
            throw new \RuntimeException('Tabela inválida para purge.');
        }

        $cutoff = (new \DateTimeImmutable('now'))
            ->modify('-' . $days . ' days')
            ->format('Y-m-d H:i:s');

        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE created_at < :cutoff");
        $stmt->execute(['cutoff' => $cutoff]);

        return $stmt->rowCount();
    }
}
