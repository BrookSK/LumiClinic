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

    /** @return list<array<string,mixed>> */
    public function listAll(): array
    {
        $stmt = $this->pdo->prepare("\n            SELECT id, code, name, price_cents, currency, interval_unit, interval_count, trial_days, limits_json, status, created_at, updated_at\n            FROM saas_plans\n            ORDER BY price_cents ASC, name ASC\n        ");
        $stmt->execute();

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function create(
        string $code,
        string $name,
        int $priceCents,
        string $currency,
        string $intervalUnit,
        int $intervalCount,
        int $trialDays,
        string $limitsJson,
        string $status
    ): int {
        $sql = "
            INSERT INTO saas_plans (
                code, name,
                price_cents, currency,
                interval_unit, interval_count,
                trial_days,
                limits_json,
                status,
                created_at
            ) VALUES (
                :code, :name,
                :price_cents, :currency,
                :interval_unit, :interval_count,
                :trial_days,
                CAST(:limits_json AS JSON),
                :status,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'code' => $code,
            'name' => $name,
            'price_cents' => $priceCents,
            'currency' => $currency,
            'interval_unit' => $intervalUnit,
            'interval_count' => $intervalCount,
            'trial_days' => $trialDays,
            'limits_json' => $limitsJson,
            'status' => $status,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(
        int $id,
        string $name,
        int $priceCents,
        string $currency,
        string $intervalUnit,
        int $intervalCount,
        int $trialDays,
        string $limitsJson
    ): void {
        $sql = "
            UPDATE saas_plans
            SET name = :name,
                price_cents = :price_cents,
                currency = :currency,
                interval_unit = :interval_unit,
                interval_count = :interval_count,
                trial_days = :trial_days,
                limits_json = CAST(:limits_json AS JSON),
                updated_at = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'price_cents' => $priceCents,
            'currency' => $currency,
            'interval_unit' => $intervalUnit,
            'interval_count' => $intervalCount,
            'trial_days' => $trialDays,
            'limits_json' => $limitsJson,
        ]);
    }

    public function setStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare("\n            UPDATE saas_plans
            SET status = :status,
                updated_at = NOW()
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'status' => $status]);
    }
}
