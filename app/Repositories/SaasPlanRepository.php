<?php

declare(strict_types=1);

namespace App\Repositories;

final class SaasPlanRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed>|null */
    public function findActiveByCode(string $code): ?array
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $sql = "
            SELECT id, code, name, price_cents, currency, interval_unit, interval_count, trial_days, limits_json, status
            FROM saas_plans
            WHERE code = :code
              AND status = 'active'
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT id, code, name, price_cents, currency, interval_unit, interval_count, trial_days, limits_json, status
            FROM saas_plans
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return list<array<string,mixed>> */
    public function listActive(): array
    {
        $stmt = $this->pdo->prepare("\n            SELECT id, code, name, price_cents, currency, interval_unit, interval_count, trial_days, limits_json, status\n            FROM saas_plans\n            WHERE status = 'active'\n            ORDER BY price_cents ASC, name ASC\n        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
