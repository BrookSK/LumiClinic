<?php

declare(strict_types=1);

namespace App\Repositories;

final class SystemBillingRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listClinicsWithBilling(): array
    {
        $sql = "
            SELECT
                c.id,
                c.name,
                c.tenant_key,
                c.status AS clinic_status,
                c.created_at,
                (
                    SELECT d.domain
                    FROM clinic_domains d
                    WHERE d.clinic_id = c.id
                      AND d.is_primary = 1
                    ORDER BY d.id DESC
                    LIMIT 1
                ) AS primary_domain,

                cs.id AS subscription_id,
                cs.plan_id,
                cs.status AS subscription_status,
                cs.gateway_provider,
                cs.trial_ends_at,
                cs.current_period_start,
                cs.current_period_end,
                cs.cancel_at_period_end,
                cs.past_due_since,
                cs.asaas_customer_id,
                cs.asaas_subscription_id,
                cs.mp_preapproval_id,

                sp.code AS plan_code,
                sp.name AS plan_name,
                sp.price_cents AS plan_price_cents
            FROM clinics c
            LEFT JOIN clinic_subscriptions cs
                ON cs.clinic_id = c.id
            LEFT JOIN saas_plans sp
                ON sp.id = cs.plan_id
            WHERE c.deleted_at IS NULL
            ORDER BY c.id DESC
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function updateSubscriptionPlan(int $clinicId, int $planId): void
    {
        $stmt = $this->pdo->prepare("\n            UPDATE clinic_subscriptions\n            SET plan_id = :plan_id,\n                updated_at = NOW()\n            WHERE clinic_id = :clinic_id\n            LIMIT 1\n        ");
        $stmt->execute(['clinic_id' => $clinicId, 'plan_id' => $planId]);
    }

    public function updateSubscriptionGatewayProvider(int $clinicId, string $provider): void
    {
        $provider = trim($provider);
        if (!in_array($provider, ['asaas', 'mercadopago'], true)) {
            throw new \RuntimeException('Gateway inválido.');
        }

        $stmt = $this->pdo->prepare("\n            UPDATE clinic_subscriptions\n            SET gateway_provider = :gateway_provider,\n                updated_at = NOW()\n            WHERE clinic_id = :clinic_id\n            LIMIT 1\n        ");
        $stmt->execute(['clinic_id' => $clinicId, 'gateway_provider' => $provider]);
    }
}
