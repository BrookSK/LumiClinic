<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\ClinicSubscriptionRepository;
use App\Repositories\SaasPlanRepository;
use App\Services\Auth\AuthService;

final class ClinicSubscriptionSelfService
{
    public function __construct(private readonly Container $container) {}

    /**
     * @return array{
     *   subscription:array<string,mixed>,
     *   plan:?array<string,mixed>,
     *   plans:list<array<string,mixed>>,
     *   payments:list<array<string,mixed>>
     * }
     */
    public function getDashboard(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $billing = new BillingService($this->container);
        $data = $billing->getOrCreateClinicSubscription($clinicId);

        $plans = (new SaasPlanRepository($pdo))->listActive();

        $payments = [];
        $sub = $data['subscription'];
        $provider = (string)($sub['gateway_provider'] ?? '');
        if ($provider === 'asaas') {
            $asaasSubId = trim((string)($sub['asaas_subscription_id'] ?? ''));
            if ($asaasSubId !== '') {
                $payments = (new BillingGatewayService($this->container))->listAsaasPaymentsBySubscription($asaasSubId);
            }
        }

        return [
            'subscription' => $data['subscription'],
            'plan' => $data['plan'],
            'plans' => $plans,
            'payments' => $payments,
        ];
    }

    public function ensureGateway(string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        (new BillingGatewayService($this->container))->ensureGatewaySubscription($clinicId);

        (new AuditLogRepository($this->container->get(\PDO::class)))->log($userId, $clinicId, 'billing.self_service.ensure_gateway', [], $ip);
    }

    public function changePlan(int $planId, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($planId <= 0) {
            throw new \RuntimeException('Plano inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $plansRepo = new SaasPlanRepository($pdo);
        $plan = $plansRepo->findById($planId);
        if ($plan === null || (string)($plan['status'] ?? '') !== 'active') {
            throw new \RuntimeException('Plano inválido.');
        }

        $subsRepo = new ClinicSubscriptionRepository($pdo);
        $sub = $subsRepo->findByClinicId($clinicId);
        if ($sub === null) {
            (new BillingService($this->container))->getOrCreateClinicSubscription($clinicId);
            $sub = $subsRepo->findByClinicId($clinicId);
        }
        if ($sub === null) {
            throw new \RuntimeException('Assinatura não encontrada.');
        }

        $currentPlanId = (int)($sub['plan_id'] ?? 0);
        if ($currentPlanId === $planId) {
            return;
        }

        $stmt = $pdo->prepare("\n            UPDATE clinic_subscriptions\n            SET plan_id = :plan_id,\n                updated_at = NOW()\n            WHERE clinic_id = :clinic_id\n            LIMIT 1\n        ");
        $stmt->execute(['plan_id' => $planId, 'clinic_id' => $clinicId]);

        try {
            (new BillingGatewayService($this->container))->syncGatewaySubscriptionAmount($clinicId);
        } catch (\RuntimeException $e) {
            // Não bloqueia a troca de plano no banco; pode ser sincronizado depois.
        }

        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'billing.self_service.change_plan', ['from_plan_id' => $currentPlanId, 'to_plan_id' => $planId], $ip);
    }

    public function cancel(string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        (new BillingGatewayService($this->container))->cancelGatewaySubscription($clinicId);

        $pdo = $this->container->get(\PDO::class);
        (new ClinicSubscriptionRepository($pdo))->updateStatus($clinicId, 'canceled', null);
        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'billing.self_service.cancel', [], $ip);
    }
}
