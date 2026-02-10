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

    /** @return array<string,string> */
    public function getSupportSettings(): array
    {
        $keys = [
            'support_whatsapp_number' => 'support.whatsapp_number',
            'support_email' => 'support.email',
        ];

        $out = [];
        foreach ($keys as $field => $k) {
            $v = $this->getText($k);
            $out[$field] = $v === null ? '' : $v;
        }

        return $out;
    }

    /** @param array<string,mixed> $input */
    public function saveSupportSettings(array $input): void
    {
        $map = [
            'support_whatsapp_number' => 'support.whatsapp_number',
            'support_email' => 'support.email',
        ];

        foreach ($map as $field => $key) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $val = trim((string)$input[$field]);
            $this->setText($key, $val === '' ? null : $val);
        }
    }

    /** @return array<string,string> */
    public function getSeoSettings(): array
    {
        $keys = [
            'site_name' => 'seo.site_name',
            'default_title' => 'seo.default_title',
            'meta_description' => 'seo.meta_description',
            'og_image_url' => 'seo.og_image_url',
            'favicon_url' => 'seo.favicon_url',
        ];

        $out = [];
        foreach ($keys as $field => $k) {
            $v = $this->getText($k);
            $out[$field] = $v === null ? '' : $v;
        }

        return $out;
    }

    /** @param array<string,mixed> $input */
    public function saveSeoSettings(array $input): void
    {
        $map = [
            'site_name' => 'seo.site_name',
            'default_title' => 'seo.default_title',
            'meta_description' => 'seo.meta_description',
            'og_image_url' => 'seo.og_image_url',
            'favicon_url' => 'seo.favicon_url',
        ];

        foreach ($map as $field => $key) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $val = trim((string)$input[$field]);
            $this->setText($key, $val === '' ? null : $val);
        }
    }

    /** @return array<string,string> */
    public function getMailSettings(): array
    {
        $keys = [
            'smtp_host' => 'mail.smtp.host',
            'smtp_port' => 'mail.smtp.port',
            'smtp_username' => 'mail.smtp.username',
            'smtp_encryption' => 'mail.smtp.encryption',
            'from_address' => 'mail.from_address',
            'from_name' => 'mail.from_name',
        ];

        $out = [];
        foreach ($keys as $field => $k) {
            $v = $this->getText($k);
            $out[$field] = $v === null ? '' : $v;
        }

        $out['smtp_password_set'] = $this->getText('mail.smtp.password') !== null ? '1' : '';

        return $out;
    }

    /** @param array<string,mixed> $input */
    public function saveMailSettings(array $input): void
    {
        $map = [
            'smtp_host' => 'mail.smtp.host',
            'smtp_port' => 'mail.smtp.port',
            'smtp_username' => 'mail.smtp.username',
            'smtp_encryption' => 'mail.smtp.encryption',
            'from_address' => 'mail.from_address',
            'from_name' => 'mail.from_name',
        ];

        foreach ($map as $field => $key) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $val = trim((string)$input[$field]);
            $this->setText($key, $val === '' ? null : $val);
        }

        if (array_key_exists('smtp_password', $input)) {
            $pw = (string)$input['smtp_password'];
            $pw = trim($pw);
            if ($pw !== '') {
                $this->setText('mail.smtp.password', $pw);
            }
        }
    }
}
