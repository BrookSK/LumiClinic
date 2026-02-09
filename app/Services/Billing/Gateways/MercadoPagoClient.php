<?php

declare(strict_types=1);

namespace App\Services\Billing\Gateways;

use App\Core\Container\Container;
use App\Services\System\SystemSettingsService;
use App\Services\Http\HttpClient;

final class MercadoPagoClient
{
    public function __construct(private readonly Container $container) {}

    /** @return array<string,mixed> */
    public function createPreapproval(string $reason, float $autoRecurringAmount, string $payerEmail): array
    {
        $cfg = $this->container->get('config');
        $settings = new SystemSettingsService($this->container);
        $baseUrl = $settings->getText('billing.mercadopago.base_url') ?? (string)($cfg['billing']['mercadopago']['base_url'] ?? '');
        $token = $settings->getText('billing.mercadopago.access_token') ?? (string)($cfg['billing']['mercadopago']['access_token'] ?? '');
        $baseUrl = rtrim((string)$baseUrl, '/');

        if ($baseUrl === '' || $token === '') {
            throw new \RuntimeException('MercadoPago não configurado (MP_BASE_URL/MP_ACCESS_TOKEN).');
        }

        $payload = [
            'reason' => $reason,
            'payer_email' => $payerEmail,
            'auto_recurring' => [
                'frequency' => 1,
                'frequency_type' => 'months',
                'transaction_amount' => $autoRecurringAmount,
                'currency_id' => 'BRL',
            ],
            'status' => 'pending',
        ];

        $http = new HttpClient();
        $resp = $http->request('POST', $baseUrl . '/preapproval', [
            'Authorization' => 'Bearer ' . $token,
        ], $payload);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            throw new \RuntimeException('Falha ao criar preapproval no MercadoPago.');
        }

        $json = $resp['json'];
        if (!is_array($json)) {
            throw new \RuntimeException('Resposta inválida do MercadoPago.');
        }

        return $json;
    }
}
