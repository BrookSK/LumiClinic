<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Core\Container\Container;
use App\Repositories\SystemSettingsRepository;

final class SystemSettingsService
{
    public function __construct(private readonly Container $container) {}

    public function getText(string $key): ?string
    {
        return (new SystemSettingsRepository($this->container->get(\PDO::class)))->getText($key);
    }

    public function setText(string $key, ?string $value): void
    {
        (new SystemSettingsRepository($this->container->get(\PDO::class)))->upsertText($key, $value);
    }

    /** @return array<string, string> */
    public function getBillingSettings(): array
    {
        $keys = [
            'asaas_base_url' => 'billing.asaas.base_url',
            'asaas_api_key' => 'billing.asaas.api_key',
            'asaas_billing_type' => 'billing.asaas.billing_type',
            'asaas_webhook_secret' => 'billing.asaas.webhook_secret',
            'mp_base_url' => 'billing.mercadopago.base_url',
            'mp_access_token' => 'billing.mercadopago.access_token',
            'mp_payer_email_default' => 'billing.mercadopago.payer_email_default',
            'mp_webhook_secret' => 'billing.mercadopago.webhook_secret',
        ];

        $out = [];
        foreach ($keys as $field => $k) {
            $v = $this->getText($k);
            $out[$field] = $v === null ? '' : $v;
        }

        return $out;
    }

    /** @param array<string,mixed> $input */
    public function saveBillingSettings(array $input): void
    {
        $map = [
            'asaas_base_url' => 'billing.asaas.base_url',
            'asaas_api_key' => 'billing.asaas.api_key',
            'asaas_billing_type' => 'billing.asaas.billing_type',
            'asaas_webhook_secret' => 'billing.asaas.webhook_secret',
            'mp_base_url' => 'billing.mercadopago.base_url',
            'mp_access_token' => 'billing.mercadopago.access_token',
            'mp_payer_email_default' => 'billing.mercadopago.payer_email_default',
            'mp_webhook_secret' => 'billing.mercadopago.webhook_secret',
        ];

        foreach ($map as $field => $key) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $val = trim((string)$input[$field]);
            $this->setText($key, $val === '' ? null : $val);
        }
    }
}
