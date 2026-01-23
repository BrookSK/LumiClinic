<?php

declare(strict_types=1);

namespace App\Services\Billing\Gateways;

use App\Core\Container\Container;
use App\Services\Http\HttpClient;

final class AsaasClient
{
    public function __construct(private readonly Container $container) {}

    /** @return array<string,mixed> */
    public function createCustomer(string $name, ?string $email = null): array
    {
        $cfg = $this->container->get('config');
        $baseUrl = rtrim((string)($cfg['billing']['asaas']['base_url'] ?? ''), '/');
        $apiKey = (string)($cfg['billing']['asaas']['api_key'] ?? '');

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
        $baseUrl = rtrim((string)($cfg['billing']['asaas']['base_url'] ?? ''), '/');
        $apiKey = (string)($cfg['billing']['asaas']['api_key'] ?? '');

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
}
