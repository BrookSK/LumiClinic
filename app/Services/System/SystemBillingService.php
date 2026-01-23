<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\SaasPlanRepository;
use App\Repositories\SystemBillingRepository;
use App\Services\Billing\BillingGatewayService;
use App\Services\Billing\BillingService;

final class SystemBillingService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function listClinicsWithBilling(): array
    {
        return (new SystemBillingRepository($this->container->get(\PDO::class)))->listClinicsWithBilling();
    }

    /** @return list<array<string,mixed>> */
    public function listActivePlans(): array
    {
        return (new SaasPlanRepository($this->container->get(\PDO::class)))->listActive();
    }

    public function setPlan(int $clinicId, int $planId, string $ip): void
    {
        if ($clinicId <= 0 || $planId <= 0) {
            throw new \RuntimeException('Parâmetros inválidos.');
        }

        $pdo = $this->container->get(\PDO::class);

        $billing = new BillingService($this->container);
        $billing->getOrCreateClinicSubscription($clinicId);

        (new SystemBillingRepository($pdo))->updateSubscriptionPlan($clinicId, $planId);

        (new AuditLogRepository($pdo))->log((int)($_SESSION['user_id'] ?? null), null, 'system.billing.set_plan', ['clinic_id' => $clinicId, 'plan_id' => $planId], $ip);
    }

    public function setStatus(int $clinicId, string $status, string $ip): void
    {
        $allowed = ['trial', 'active', 'past_due', 'canceled', 'suspended'];
        $status = trim($status);
        if ($clinicId <= 0 || !in_array($status, $allowed, true)) {
            throw new \RuntimeException('Parâmetros inválidos.');
        }

        $billing = new BillingService($this->container);
        $data = $billing->getOrCreateClinicSubscription($clinicId);

        $pdo = $this->container->get(\PDO::class);
        $subsRepo = new \App\Repositories\ClinicSubscriptionRepository($pdo);

        if ($status === 'past_due') {
            $subsRepo->updateStatus($clinicId, 'past_due', (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s'));
        } else {
            $subsRepo->updateStatus($clinicId, $status, null);
        }

        (new AuditLogRepository($pdo))->log((int)($_SESSION['user_id'] ?? null), null, 'system.billing.set_status', ['clinic_id' => $clinicId, 'from' => (string)($data['subscription']['status'] ?? ''), 'to' => $status], $ip);
    }

    public function setGatewayProvider(int $clinicId, string $provider, string $ip): void
    {
        if ($clinicId <= 0) {
            throw new \RuntimeException('Parâmetros inválidos.');
        }

        $pdo = $this->container->get(\PDO::class);

        $billing = new BillingService($this->container);
        $billing->getOrCreateClinicSubscription($clinicId);

        (new SystemBillingRepository($pdo))->updateSubscriptionGatewayProvider($clinicId, $provider);

        (new AuditLogRepository($pdo))->log((int)($_SESSION['user_id'] ?? null), null, 'system.billing.set_gateway', ['clinic_id' => $clinicId, 'gateway_provider' => $provider], $ip);
    }

    public function ensureGateway(int $clinicId, string $ip): void
    {
        if ($clinicId <= 0) {
            throw new \RuntimeException('Parâmetros inválidos.');
        }

        (new BillingGatewayService($this->container))->ensureGatewaySubscription($clinicId);

        $pdo = $this->container->get(\PDO::class);
        (new AuditLogRepository($pdo))->log((int)($_SESSION['user_id'] ?? null), null, 'system.billing.ensure_gateway', ['clinic_id' => $clinicId], $ip);
    }
}
