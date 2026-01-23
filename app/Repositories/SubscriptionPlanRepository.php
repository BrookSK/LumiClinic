<?php

declare(strict_types=1);

namespace App\Repositories;

final class SubscriptionPlanRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listActiveByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, clinic_id, name, interval_months, price, status
            FROM subscription_plans
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'active'
            ORDER BY name ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, name, interval_months, price, status
            FROM subscription_plans
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
