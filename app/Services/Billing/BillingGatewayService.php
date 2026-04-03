<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Core\Container\Container;
use App\Repositories\ClinicRepository;
use App\Repositories\ClinicSubscriptionRepository;
use App\Repositories\SaasPlanRepository;
use App\Services\Billing\Gateways\AsaasClient;
use App\Services\Billing\Gateways\MercadoPagoClient;
use App\Services\System\SystemSettingsService;

final class BillingGatewayService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function listAsaasPaymentsBySubscription(string $asaasSubscriptionId, int $limit = 50): array
    {
        return (new AsaasClient($this->container))->listPaymentsBySubscription($asaasSubscriptionId, $limit);
    }

    /** @param array<string,string> $card */
    public function createAsaasCreditCardPaymentForUpgrade(
        int $clinicId,
        string $asaasCustomerId,
        float $amount,
        array $card,
        string $ip,
        ?string $userAgent = null
    ): array {
        if ($clinicId <= 0) {
            throw new \RuntimeException('Clínica inválida.');
        }
        $asaasCustomerId = trim($asaasCustomerId);
        if ($asaasCustomerId === '') {
            throw new \RuntimeException('Customer Asaas inválido.');
        }
        if ($amount <= 0) {
            throw new \RuntimeException('Valor inválido.');
        }

        $payload = [
            'customer' => $asaasCustomerId,
            'billingType' => 'CREDIT_CARD',
            'value' => $amount,
            'dueDate' => (new \DateTimeImmutable('now'))->format('Y-m-d'),
            'description' => 'Upgrade de plano (clínica #' . $clinicId . ')',
            'externalReference' => 'clinic:' . $clinicId . ':upgrade',
            'creditCard' => [
                'holderName' => trim((string)($card['cc_holder'] ?? '')),
                'number' => preg_replace('/\D+/', '', (string)($card['cc_number'] ?? '')),
                'expiryMonth' => preg_replace('/\D+/', '', (string)($card['cc_exp_month'] ?? '')),
                'expiryYear' => preg_replace('/\D+/', '', (string)($card['cc_exp_year'] ?? '')),
                'ccv' => preg_replace('/\D+/', '', (string)($card['cc_cvv'] ?? '')),
            ],
            'creditCardHolderInfo' => [
                'name' => trim((string)($card['cc_holder'] ?? '')),
                'cpfCnpj' => preg_replace('/\D+/', '', (string)($card['cpf'] ?? '')),
                'postalCode' => preg_replace('/\D+/', '', (string)($card['postal_code'] ?? '')),
                'addressNumber' => trim((string)($card['address_number'] ?? '')),
                'phone' => trim((string)($card['phone'] ?? '')) !== '' ? preg_replace('/\D+/', '', (string)($card['phone'] ?? '')) : null,
                'mobilePhone' => trim((string)($card['mobile'] ?? '')) !== '' ? preg_replace('/\D+/', '', (string)($card['mobile'] ?? '')) : null,
            ],
            'remoteIp' => $ip,
        ];

        return (new AsaasClient($this->container))->createPayment($payload);
    }

    /** @param array<string,string> $card */
    public function ensureAsaasSubscriptionCreditCard(int $clinicId, array $card, string $ip, ?string $userAgent = null): void
    {
        $pdo = $this->container->get(\PDO::class);
        $subsRepo = new ClinicSubscriptionRepository($pdo);
        $sub = $subsRepo->findByClinicId($clinicId);
        if ($sub === null) {
            return;
        }

        $provider = (string)($sub['gateway_provider'] ?? '');
        if ($provider !== 'asaas') {
            return;
        }

        $asaasSubId = trim((string)($sub['asaas_subscription_id'] ?? ''));
        if ($asaasSubId === '') {
            return;
        }

        $payload = [
            'creditCard' => [
                'holderName' => trim((string)($card['cc_holder'] ?? '')),
                'number' => preg_replace('/\D+/', '', (string)($card['cc_number'] ?? '')),
                'expiryMonth' => preg_replace('/\D+/', '', (string)($card['cc_exp_month'] ?? '')),
                'expiryYear' => preg_replace('/\D+/', '', (string)($card['cc_exp_year'] ?? '')),
                'ccv' => preg_replace('/\D+/', '', (string)($card['cc_cvv'] ?? '')),
            ],
            'creditCardHolderInfo' => [
                'name' => trim((string)($card['cc_holder'] ?? '')),
                'cpfCnpj' => preg_replace('/\D+/', '', (string)($card['cpf'] ?? '')),
                'postalCode' => preg_replace('/\D+/', '', (string)($card['postal_code'] ?? '')),
                'addressNumber' => trim((string)($card['address_number'] ?? '')),
                'phone' => trim((string)($card['phone'] ?? '')) !== '' ? preg_replace('/\D+/', '', (string)($card['phone'] ?? '')) : null,
                'mobilePhone' => trim((string)($card['mobile'] ?? '')) !== '' ? preg_replace('/\D+/', '', (string)($card['mobile'] ?? '')) : null,
            ],
            'remoteIp' => $ip,
        ];

        (new AsaasClient($this->container))->updateSubscriptionCreditCard($asaasSubId, $payload);
    }

    public function syncGatewaySubscriptionAmount(int $clinicId): void
    {
        $pdo = $this->container->get(\PDO::class);

        $subsRepo = new ClinicSubscriptionRepository($pdo);
        $sub = $subsRepo->findByClinicId($clinicId);
        if ($sub === null) {
            return;
        }

        $provider = (string)($sub['gateway_provider'] ?? '');
        if ($provider !== 'asaas') {
            return;
        }

        $asaasSubId = trim((string)($sub['asaas_subscription_id'] ?? ''));
        if ($asaasSubId === '') {
            return;
        }

        $planId = isset($sub['plan_id']) && $sub['plan_id'] !== null ? (int)$sub['plan_id'] : null;
        $plan = $planId !== null ? (new SaasPlanRepository($pdo))->findById($planId) : null;
        $priceCents = $plan !== null ? (int)($plan['price_cents'] ?? 0) : 0;
        $amount = max(0, $priceCents) / 100;

        (new AsaasClient($this->container))->updateSubscriptionValue($asaasSubId, $amount);
    }

    public function cancelGatewaySubscription(int $clinicId): void
    {
        $pdo = $this->container->get(\PDO::class);

        $subsRepo = new ClinicSubscriptionRepository($pdo);
        $sub = $subsRepo->findByClinicId($clinicId);
        if ($sub === null) {
            return;
        }

        $provider = (string)($sub['gateway_provider'] ?? '');
        if ($provider === 'asaas') {
            $asaasSubId = trim((string)($sub['asaas_subscription_id'] ?? ''));
            if ($asaasSubId !== '') {
                (new AsaasClient($this->container))->cancelSubscription($asaasSubId);
            }
            return;
        }

        if ($provider === 'mercadopago') {
            throw new \RuntimeException('Cancelamento via MercadoPago ainda não implementado.');
        }
    }

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
            $settings = new \App\Services\System\SystemSettingsService($this->container);
            $provider = trim((string)($settings->getText('billing.active_gateway') ?? ''));
        }
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
            // Get clinic email and cnpj for customer creation
            $clinicRow = (new ClinicRepository($pdo))->findById($clinicId);
            $email = isset($clinicRow['contact_email']) ? trim((string)$clinicRow['contact_email']) : null;
            $cnpj = isset($clinicRow['cnpj']) ? trim((string)$clinicRow['cnpj']) : null;

            $customer = $client->createCustomer($clinicName, $email ?: null, $cnpj ?: null);
            $customerId = isset($customer['id']) ? (string)$customer['id'] : '';
        }

        if ($subscriptionId === '') {
            $created = $client->createSubscription($customerId, $amount, 'CREDIT_CARD');
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
        $settings = new SystemSettingsService($this->container);
        $payerEmail = $settings->getText('billing.mercadopago.payer_email_default') ?? (string)($cfg['billing']['mercadopago']['payer_email_default'] ?? '');
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
