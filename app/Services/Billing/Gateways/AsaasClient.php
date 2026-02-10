<?php

declare(strict_types=1);

namespace App\Services\Billing\Gateways;

use App\Core\Container\Container;
use App\Services\System\SystemSettingsService;
use App\Services\Http\HttpClient;

final class AsaasClient
{
    public function __construct(private readonly Container $container) {}

    /** @return array<string,mixed> */
    public function createCustomer(string $name, ?string $email = null): array
    {
        $cfg = $this->container->get('config');
        $settings = new SystemSettingsService($this->container);
        $baseUrl = $settings->getText('billing.asaas.base_url') ?? (string)($cfg['billing']['asaas']['base_url'] ?? '');
        $apiKey = $settings->getText('billing.asaas.api_key') ?? (string)($cfg['billing']['asaas']['api_key'] ?? '');
        $baseUrl = rtrim((string)$baseUrl, '/');

        if ($baseUrl === '' || $apiKey === '') {
            throw new \RuntimeException('Asaas não configurado (ASAAS_BASE_URL/ASAAS_API_KEY).');
        }

        $payload = ['name' => $name];
        if ($email !== null && trim($email) !== '') {
            $payload['email'] = $email;
        }

        $http = new HttpClient();
        $resp = $http->request('POST', $baseUrl . '/customers', [
            'access_token' => $apiKey,
        ], $payload);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            throw new \RuntimeException('Falha ao criar customer no Asaas.');
        }

        $json = $resp['json'];
        if (!is_array($json)) {
            throw new \RuntimeException('Resposta inválida do Asaas.');
        }

        return $json;
    }

    /** @return array<string,mixed> */
    public function createSubscription(string $customerId, float $value, string $billingType = 'BOLETO'): array
    {
        $cfg = $this->container->get('config');
        $settings = new SystemSettingsService($this->container);
        $baseUrl = $settings->getText('billing.asaas.base_url') ?? (string)($cfg['billing']['asaas']['base_url'] ?? '');
        $apiKey = $settings->getText('billing.asaas.api_key') ?? (string)($cfg['billing']['asaas']['api_key'] ?? '');
        $baseUrl = rtrim((string)$baseUrl, '/');

        if ($baseUrl === '' || $apiKey === '') {
            throw new \RuntimeException('Asaas não configurado (ASAAS_BASE_URL/ASAAS_API_KEY).');
        }

        $payload = [
            'customer' => $customerId,
            'billingType' => $billingType,
            'value' => $value,
            'cycle' => 'MONTHLY',
        ];

        $http = new HttpClient();
        $resp = $http->request('POST', $baseUrl . '/subscriptions', [
            'access_token' => $apiKey,
        ], $payload);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            throw new \RuntimeException('Falha ao criar assinatura no Asaas.');
        }

        $json = $resp['json'];
        if (!is_array($json)) {
            throw new \RuntimeException('Resposta inválida do Asaas.');
        }

        return $json;
    }

    /** @return list<array<string,mixed>> */
    public function listPaymentsBySubscription(string $subscriptionId, int $limit = 50): array
    {
        $subscriptionId = trim($subscriptionId);
        if ($subscriptionId === '') {
            return [];
        }

        $cfg = $this->container->get('config');
        $settings = new SystemSettingsService($this->container);
        $baseUrl = $settings->getText('billing.asaas.base_url') ?? (string)($cfg['billing']['asaas']['base_url'] ?? '');
        $apiKey = $settings->getText('billing.asaas.api_key') ?? (string)($cfg['billing']['asaas']['api_key'] ?? '');
        $baseUrl = rtrim((string)$baseUrl, '/');

        if ($baseUrl === '' || $apiKey === '') {
            throw new \RuntimeException('Asaas não configurado (ASAAS_BASE_URL/ASAAS_API_KEY).');
        }

        $limit = max(1, min(100, $limit));
        $url = $baseUrl . '/payments?subscription=' . urlencode($subscriptionId) . '&limit=' . $limit;

        $http = new HttpClient();
        $resp = $http->request('GET', $url, [
            'access_token' => $apiKey,
        ]);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            return [];
        }

        $json = $resp['json'];
        if (!is_array($json)) {
            return [];
        }

        $data = $json['data'] ?? null;
        if (!is_array($data)) {
            return [];
        }

        /** @var list<array<string,mixed>> */
        return array_values(array_filter($data, 'is_array'));
    }

    /** @return array<string,mixed> */
    public function updateSubscriptionValue(string $subscriptionId, float $value): array
    {
        $subscriptionId = trim($subscriptionId);
        if ($subscriptionId === '') {
            throw new \RuntimeException('Assinatura inválida.');
        }

        $cfg = $this->container->get('config');
        $settings = new SystemSettingsService($this->container);
        $baseUrl = $settings->getText('billing.asaas.base_url') ?? (string)($cfg['billing']['asaas']['base_url'] ?? '');
        $apiKey = $settings->getText('billing.asaas.api_key') ?? (string)($cfg['billing']['asaas']['api_key'] ?? '');
        $baseUrl = rtrim((string)$baseUrl, '/');

        if ($baseUrl === '' || $apiKey === '') {
            throw new \RuntimeException('Asaas não configurado (ASAAS_BASE_URL/ASAAS_API_KEY).');
        }

        $http = new HttpClient();
        $resp = $http->request('PUT', $baseUrl . '/subscriptions/' . urlencode($subscriptionId), [
            'access_token' => $apiKey,
        ], [
            'value' => $value,
        ]);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            throw new \RuntimeException('Falha ao atualizar assinatura no Asaas.');
        }

        $json = $resp['json'];
        if (!is_array($json)) {
            throw new \RuntimeException('Resposta inválida do Asaas.');
        }

        return $json;
    }

    public function cancelSubscription(string $subscriptionId): void
    {
        $subscriptionId = trim($subscriptionId);
        if ($subscriptionId === '') {
            throw new \RuntimeException('Assinatura inválida.');
        }

        $cfg = $this->container->get('config');
        $settings = new SystemSettingsService($this->container);
        $baseUrl = $settings->getText('billing.asaas.base_url') ?? (string)($cfg['billing']['asaas']['base_url'] ?? '');
        $apiKey = $settings->getText('billing.asaas.api_key') ?? (string)($cfg['billing']['asaas']['api_key'] ?? '');
        $baseUrl = rtrim((string)$baseUrl, '/');

        if ($baseUrl === '' || $apiKey === '') {
            throw new \RuntimeException('Asaas não configurado (ASAAS_BASE_URL/ASAAS_API_KEY).');
        }

        $http = new HttpClient();
        $resp = $http->request('DELETE', $baseUrl . '/subscriptions/' . urlencode($subscriptionId), [
            'access_token' => $apiKey,
        ]);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            throw new \RuntimeException('Falha ao cancelar assinatura no Asaas.');
        }
    }
}
