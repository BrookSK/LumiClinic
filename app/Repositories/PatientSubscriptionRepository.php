<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientSubscriptionRepository
{
    public function __construct(private readonly \PDO $pdo) {}

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
