<?php

declare(strict_types=1);

namespace App\Services\Observability;

use App\Core\Container\Container;
use App\Repositories\AlertRuleRepository;

final class AlertEngineService
{
    public function __construct(private readonly Container $container) {}

    public function evaluate(string $referenceDateYmd): void
    {
        $pdo = $this->container->get(\PDO::class);
        $rules = (new AlertRuleRepository($pdo))->listEnabled();

        foreach ($rules as $rule) {
            $this->evaluateRule($rule, $referenceDateYmd);
        }
    }

    /** @param array<string,mixed> $rule */
    private function evaluateRule(array $rule, string $referenceDateYmd): void
    {
        $scope = (string)($rule['scope'] ?? 'clinic');
        $metric = (string)($rule['metric'] ?? '');
        $operator = (string)($rule['operator'] ?? '>');
        $threshold = (float)($rule['threshold'] ?? 0);
        $windowDays = (int)($rule['window_days'] ?? 7);
        $windowDays = max(1, min(90, $windowDays));

        if ($metric === '') {
            return;
        }

        $clinicId = $rule['clinic_id'] !== null ? (int)$rule['clinic_id'] : null;

        $end = new \DateTimeImmutable($referenceDateYmd);
        $start = $end->modify('-' . ($windowDays - 1) . ' days');

        $pdo = $this->container->get(\PDO::class);

        $sql = "
            SELECT AVG(value) AS avg_value
            FROM system_metrics
            WHERE metric = :metric
              AND reference_date BETWEEN :start AND :end
              AND clinic_id " . ($clinicId === null ? "IS NULL" : "= :clinic_id") . "
        ";

        $stmt = $pdo->prepare($sql);
        $params = [
            'metric' => $metric,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ];
        if ($clinicId !== null) {
            $params['clinic_id'] = $clinicId;
        }

        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];
        $avg = (float)($row['avg_value'] ?? 0);

        if (!$this->compare($avg, $operator, $threshold)) {
            return;
        }

        SystemEvent::dispatch($this->container, 'alert.triggered', [
            'rule_id' => (int)$rule['id'],
            'scope' => $scope,
            'clinic_id' => $clinicId,
            'metric' => $metric,
            'operator' => $operator,
            'threshold' => $threshold,
            'window_days' => $windowDays,
            'avg_value' => $avg,
            'action' => (string)($rule['action'] ?? ''),
            'channel' => (string)($rule['channel'] ?? ''),
            'reference_date' => $referenceDateYmd,
        ], 'alert_rule', (int)$rule['id'], null, null);
    }

    private function compare(float $value, string $operator, float $threshold): bool
    {
        return match ($operator) {
            '>' => $value > $threshold,
            '>=' => $value >= $threshold,
            '<' => $value < $threshold,
            '<=' => $value <= $threshold,
            '==' => $value == $threshold,
            '!=' => $value != $threshold,
            default => false,
        };
    }
}
