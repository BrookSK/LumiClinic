<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientSubscriptionRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listActiveByPatient(int $clinicId, int $patientId, int $limit = 20): array
    {
        $sql = "
            SELECT
                ps.id,
                ps.plan_id,
                ps.status,
                ps.started_at,
                ps.ends_at,
                sp.name AS plan_name
            FROM patient_subscriptions ps
            LEFT JOIN subscription_plans sp
                   ON sp.id = ps.plan_id
                  AND sp.clinic_id = ps.clinic_id
                  AND sp.deleted_at IS NULL
            WHERE ps.clinic_id = :clinic_id
              AND ps.patient_id = :patient_id
              AND ps.deleted_at IS NULL
              AND ps.status = 'active'
            ORDER BY ps.id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function create(
        int $clinicId,
        int $patientId,
        int $planId,
        ?int $saleId,
        ?int $saleItemId,
        ?string $startedAt,
        ?string $endsAt
    ): int {
        $sql = "
            INSERT INTO patient_subscriptions (
                clinic_id, patient_id,
                plan_id, sale_id, sale_item_id,
                status, started_at, ends_at,
                created_at
            )
            VALUES (
                :clinic_id, :patient_id,
                :plan_id, :sale_id, :sale_item_id,
                'active', :started_at, :ends_at,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'plan_id' => $planId,
            'sale_id' => $saleId,
            'sale_item_id' => $saleItemId,
            'started_at' => ($startedAt === '' ? null : $startedAt),
            'ends_at' => ($endsAt === '' ? null : $endsAt),
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
