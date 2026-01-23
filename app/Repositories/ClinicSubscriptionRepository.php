<?php

declare(strict_types=1);

namespace App\Repositories;

final class ClinicSubscriptionRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed>|null */
    public function findByClinicId(int $clinicId): ?array
    {
        $sql = "
            SELECT
                cs.id,
                cs.clinic_id,
                cs.plan_id,
                cs.status,
                cs.trial_ends_at,
                cs.current_period_start,
                cs.current_period_end,
                cs.cancel_at_period_end,
                cs.past_due_since,
                cs.gateway_provider,
                cs.asaas_customer_id,
                cs.asaas_subscription_id,
                cs.mp_preapproval_id,
                cs.created_at,
                cs.updated_at
            FROM clinic_subscriptions cs
            WHERE cs.clinic_id = :clinic_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createTrial(int $clinicId, ?int $planId, int $trialDays): int
    {
        $trialDays = max(0, min($trialDays, 365));
        $trialEndsAt = null;
        if ($trialDays > 0) {
            $trialEndsAt = (new \DateTimeImmutable('now'))->modify('+' . $trialDays . ' days')->format('Y-m-d H:i:s');
        }

        $sql = "
            INSERT INTO clinic_subscriptions (
                clinic_id,
                plan_id,
                status,
                trial_ends_at,
                created_at
            ) VALUES (
                :clinic_id,
                :plan_id,
                'trial',
                :trial_ends_at,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'plan_id' => $planId,
            'trial_ends_at' => $trialEndsAt,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $clinicId, string $status, ?string $pastDueSince = null): void
    {
        $status = trim($status);
        if ($status === '') {
            throw new \RuntimeException('Status inválido.');
        }

        $sql = "
            UPDATE clinic_subscriptions
            SET status = :status,
                past_due_since = :past_due_since,
                updated_at = NOW()
            WHERE clinic_id = :clinic_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'status' => $status,
            'past_due_since' => $pastDueSince,
        ]);
    }

    /** @return array<string,mixed>|null */
    public function findByAsaasSubscriptionId(string $asaasSubscriptionId): ?array
    {
        $asaasSubscriptionId = trim($asaasSubscriptionId);
        if ($asaasSubscriptionId === '') {
            return null;
        }

        $stmt = $this->pdo->prepare("\n            SELECT *\n            FROM clinic_subscriptions\n            WHERE asaas_subscription_id = :id\n            LIMIT 1\n        ");
        $stmt->execute(['id' => $asaasSubscriptionId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findByMpPreapprovalId(string $mpPreapprovalId): ?array
    {
        $mpPreapprovalId = trim($mpPreapprovalId);
        if ($mpPreapprovalId === '') {
            return null;
        }

        $stmt = $this->pdo->prepare("\n            SELECT *\n            FROM clinic_subscriptions\n            WHERE mp_preapproval_id = :id\n            LIMIT 1\n        ");
        $stmt->execute(['id' => $mpPreapprovalId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updateGatewayIds(
        int $clinicId,
        ?string $gatewayProvider,
        ?string $asaasCustomerId,
        ?string $asaasSubscriptionId,
        ?string $mpPreapprovalId
    ): void {
        $stmt = $this->pdo->prepare("\n            UPDATE clinic_subscriptions\n            SET gateway_provider = :gateway_provider,\n                asaas_customer_id = :asaas_customer_id,\n                asaas_subscription_id = :asaas_subscription_id,\n                mp_preapproval_id = :mp_preapproval_id,\n                updated_at = NOW()\n            WHERE clinic_id = :clinic_id\n            LIMIT 1\n        ");

        $stmt->execute([
            'clinic_id' => $clinicId,
            'gateway_provider' => $gatewayProvider,
            'asaas_customer_id' => $asaasCustomerId,
            'asaas_subscription_id' => $asaasSubscriptionId,
            'mp_preapproval_id' => $mpPreapprovalId,
        ]);
    }
}
