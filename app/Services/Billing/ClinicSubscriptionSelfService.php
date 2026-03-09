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

        $sub = $data['subscription'];
        $pendingPlanId = isset($sub['pending_plan_id']) && $sub['pending_plan_id'] !== null ? (int)$sub['pending_plan_id'] : null;
        $pendingEffectiveAt = isset($sub['pending_plan_effective_at']) ? (string)($sub['pending_plan_effective_at'] ?? '') : '';
        if ($pendingPlanId !== null && $pendingPlanId > 0 && trim($pendingEffectiveAt) !== '') {
            try {
                $eff = new \DateTimeImmutable($pendingEffectiveAt);
                if ($eff <= new \DateTimeImmutable('now')) {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("\n                        UPDATE clinic_subscriptions\n                        SET plan_id = :plan_id,\n                            pending_plan_id = NULL,\n                            pending_plan_effective_at = NULL,\n                            updated_at = NOW()\n                        WHERE clinic_id = :clinic_id\n                        LIMIT 1\n                    ");
                    $stmt->execute(['clinic_id' => $clinicId, 'plan_id' => $pendingPlanId]);

                    $gw = new BillingGatewayService($this->container);
                    $gw->ensureGatewaySubscription($clinicId);
                    $gw->syncGatewaySubscriptionAmount($clinicId);

                    $pdo->commit();

                    $data = $billing->getOrCreateClinicSubscription($clinicId);
                }
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            }
        }

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

    /** @return array{type:string,checkout_url?:string} */
    public function beginPlanChange(int $planId, string $ip): array
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
            return ['type' => 'noop'];
        }

        $plansRepo = new SaasPlanRepository($pdo);
        $currentPlan = $currentPlanId > 0 ? $plansRepo->findById($currentPlanId) : null;
        $currentPrice = $currentPlan !== null ? (int)($currentPlan['price_cents'] ?? 0) : 0;
        $newPrice = (int)($plan['price_cents'] ?? 0);

        if ($newPrice <= $currentPrice) {
            $effectiveAt = (string)($sub['current_period_end'] ?? '');
            if (trim($effectiveAt) === '') {
                $effectiveAt = (new \DateTimeImmutable('now'))->modify('+1 month')->format('Y-m-d H:i:s');
            }
            $subsRepo->scheduleDowngrade($clinicId, $planId, $effectiveAt);
            (new AuditLogRepository($pdo))->log($userId, $clinicId, 'billing.self_service.schedule_downgrade', ['from_plan_id' => $currentPlanId, 'to_plan_id' => $planId, 'effective_at' => $effectiveAt], $ip);
            return ['type' => 'downgrade_scheduled'];
        }

        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'billing.self_service.begin_upgrade', ['from_plan_id' => $currentPlanId, 'to_plan_id' => $planId], $ip);
        return ['type' => 'upgrade_checkout', 'checkout_url' => '/billing/subscription/checkout?plan_id=' . $planId];
    }

    /** @return array{subscription:array<string,mixed>,plan:array<string,mixed>} */
    public function getCheckoutData(int $planId): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($planId <= 0) {
            throw new \RuntimeException('Plano inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $plan = (new SaasPlanRepository($pdo))->findById($planId);
        if ($plan === null || (string)($plan['status'] ?? '') !== 'active') {
            throw new \RuntimeException('Plano inválido.');
        }

        $sub = (new BillingService($this->container))->getOrCreateClinicSubscription($clinicId)['subscription'];
        return ['subscription' => $sub, 'plan' => $plan];
    }

    /** @param array<string,string> $card */
    public function payUpgradeAndApply(int $planId, array $card, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
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
        $currentPlan = $currentPlanId > 0 ? $plansRepo->findById($currentPlanId) : null;
        $currentPrice = $currentPlan !== null ? (int)($currentPlan['price_cents'] ?? 0) : 0;
        $newPrice = (int)($plan['price_cents'] ?? 0);
        if ($newPrice <= $currentPrice) {
            throw new \RuntimeException('Este fluxo é apenas para upgrade.');
        }

        $gw = new BillingGatewayService($this->container);
        $gw->ensureGatewaySubscription($clinicId);

        $sub = $subsRepo->findByClinicId($clinicId);
        if ($sub === null) {
            throw new \RuntimeException('Assinatura não encontrada.');
        }

        $provider = (string)($sub['gateway_provider'] ?? '');
        if ($provider !== 'asaas') {
            throw new \RuntimeException('Upgrade com cartão está disponível apenas via Asaas no momento.');
        }

        $customerId = trim((string)($sub['asaas_customer_id'] ?? ''));
        if ($customerId === '') {
            throw new \RuntimeException('Customer Asaas não encontrado.');
        }

        $amount = max(0, $newPrice) / 100;
        $payment = $gw->createAsaasCreditCardPaymentForUpgrade($clinicId, $customerId, $amount, $card, $ip, $userAgent);

        $paymentId = isset($payment['id']) ? (string)$payment['id'] : '';
        if (trim($paymentId) === '') {
            throw new \RuntimeException('Falha ao criar cobrança no Asaas.');
        }

        $status = strtoupper((string)($payment['status'] ?? ''));
        if (!in_array($status, ['CONFIRMED', 'RECEIVED'], true)) {
            $subsRepo->setPendingUpgrade($clinicId, $planId, $paymentId);
            (new AuditLogRepository($pdo))->log($userId, $clinicId, 'billing.self_service.upgrade_payment_pending', ['payment_id' => $paymentId, 'to_plan_id' => $planId, 'status' => $status], $ip);
            throw new \RuntimeException('Pagamento não aprovado. Status: ' . ($status !== '' ? $status : 'desconhecido') . '.');
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("\n                UPDATE clinic_subscriptions\n                SET plan_id = :plan_id,\n                    pending_upgrade_plan_id = NULL,\n                    pending_upgrade_payment_id = NULL,\n                    pending_plan_id = NULL,\n                    pending_plan_effective_at = NULL,\n                    updated_at = NOW()\n                WHERE clinic_id = :clinic_id\n                LIMIT 1\n            ");
            $stmt->execute(['clinic_id' => $clinicId, 'plan_id' => $planId]);

            $gw->syncGatewaySubscriptionAmount($clinicId);
            $gw->ensureAsaasSubscriptionCreditCard($clinicId, $card, $ip, $userAgent);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'billing.self_service.upgrade_success', ['payment_id' => $paymentId, 'from_plan_id' => $currentPlanId, 'to_plan_id' => $planId], $ip);
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
