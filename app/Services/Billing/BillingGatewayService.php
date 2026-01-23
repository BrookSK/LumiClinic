<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Core\Container\Container;
use App\Repositories\ClinicRepository;
use App\Repositories\ClinicSubscriptionRepository;
use App\Repositories\SaasPlanRepository;
use App\Services\Billing\Gateways\AsaasClient;
use App\Services\Billing\Gateways\MercadoPagoClient;

final class BillingGatewayService
{
    public function __construct(private readonly Container $container) {}

    /**
     * Cria (se necessário) customer + subscription no gateway e grava IDs em clinic_subscriptions.
     * Por padrão usa Asaas (cobrança centralizada por clínica).
     */
    public function ensureGatewaySubscription(int $clinicId): void
    {
        $pdo = $this->container->get(\PDO::class);

        $clinic = (new ClinicRepository($pdo))->findById($clinicId);
        if ($clinic === null) {
            throw new \RuntimeException('Clínica inválida.');
        }

        $subsRepo = new ClinicSubscriptionRepository($pdo);
        $billing = new BillingService($this->container);
        $data = $billing->getOrCreateClinicSubscription($clinicId);
        $sub = $data['subscription'];

        $planId = isset($sub['plan_id']) && $sub['plan_id'] !== null ? (int)$sub['plan_id'] : null;
        $plan = $planId !== null ? (new SaasPlanRepository($pdo))->findById($planId) : null;

        $priceCents = $plan !== null ? (int)($plan['price_cents'] ?? 0) : 0;
        $amount = max(0, $priceCents) / 100;

        $provider = (string)($sub['gateway_provider'] ?? '');
        if ($provider === '') {
            $provider = 'asaas';
        }

        if ($provider === 'asaas') {
            $this->ensureAsaas($clinicId, (string)$clinic['name'], $amount, $sub);
            return;
        }

        if ($provider === 'mercadopago') {
            $this->ensureMp($clinicId, (string)$clinic['name'], $amount, $sub);
            return;
        }

        throw new \RuntimeException('Gateway inválido.');
    }

    /** @param array<string,mixed> $sub */
    private function ensureAsaas(int $clinicId, string $clinicName, float $amount, array $sub): void
    {
        $pdo = $this->container->get(\PDO::class);
        $subsRepo = new ClinicSubscriptionRepository($pdo);

        $customerId = isset($sub['asaas_customer_id']) ? (string)$sub['asaas_customer_id'] : '';
        $subscriptionId = isset($sub['asaas_subscription_id']) ? (string)$sub['asaas_subscription_id'] : '';

        $client = new AsaasClient($this->container);

        if ($customerId === '') {
            $customer = $client->createCustomer($clinicName, null);
            $customerId = isset($customer['id']) ? (string)$customer['id'] : '';
        }

        if ($subscriptionId === '') {
            $cfg = $this->container->get('config');
            $billingType = (string)($cfg['billing']['asaas']['billing_type'] ?? 'BOLETO');
            $created = $client->createSubscription($customerId, $amount, $billingType);
            $subscriptionId = isset($created['id']) ? (string)$created['id'] : '';
        }

        if ($customerId !== '' || $subscriptionId !== '') {
            $subsRepo->updateGatewayIds($clinicId, 'asaas', $customerId !== '' ? $customerId : null, $subscriptionId !== '' ? $subscriptionId : null, null);
        }
    }

    /** @param array<string,mixed> $sub */
    private function ensureMp(int $clinicId, string $clinicName, float $amount, array $sub): void
    {
        $pdo = $this->container->get(\PDO::class);
        $subsRepo = new ClinicSubscriptionRepository($pdo);

        $preapprovalId = isset($sub['mp_preapproval_id']) ? (string)$sub['mp_preapproval_id'] : '';
        if ($preapprovalId !== '') {
            return;
        }

        $cfg = $this->container->get('config');
        $payerEmail = (string)($cfg['billing']['mercadopago']['payer_email_default'] ?? '');
        if (trim($payerEmail) === '') {
            throw new \RuntimeException('MercadoPago requer MP_PAYER_EMAIL_DEFAULT.');
        }

        $client = new MercadoPagoClient($this->container);
        $created = $client->createPreapproval('Assinatura ' . $clinicName, $amount, $payerEmail);
        $preapprovalId = isset($created['id']) ? (string)$created['id'] : '';

        if ($preapprovalId !== '') {
            $subsRepo->updateGatewayIds($clinicId, 'mercadopago', null, null, $preapprovalId);
        }
    }
}
