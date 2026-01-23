<?php

declare(strict_types=1);

namespace App\Repositories;

final class AlertRuleRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listEnabled(): array
    {
        $stmt = $this->pdo->query("\n            SELECT id, scope, clinic_id, user_id, metric, operator, threshold, window_days, action, channel, enabled
            FROM alert_rules
            WHERE enabled = 1
            ORDER BY id ASC
        ");

        return $stmt->fetchAll();
    }
}
