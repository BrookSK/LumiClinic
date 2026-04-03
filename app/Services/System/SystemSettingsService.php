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

    /** @return array{webpush_public_key:string,webpush_private_key:string,webpush_subject:string} */
    public function getWebPushSettings(): array
    {
        return [
            'webpush_public_key' => (string)($this->getText('webpush.public_key') ?? ''),
            'webpush_private_key' => (string)($this->getText('webpush.private_key') ?? ''),
            'webpush_subject' => (string)($this->getText('webpush.subject') ?? ''),
        ];
    }

    /** @param array<string,mixed> $input */
    public function saveWebPushSettings(array $input): void
    {
        $map = [
            'webpush_public_key' => 'webpush.public_key',
            'webpush_private_key' => 'webpush.private_key',
            'webpush_subject' => 'webpush.subject',
        ];

        foreach ($map as $field => $key) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $val = trim((string)$input[$field]);
            $this->setText($key, $val === '' ? null : $val);
        }
    }

    /** @return array{evolution_base_url:string,evolution_token_set:bool} */
    public function getWhatsappSettings(): array
    {
        $token = trim((string)($this->getText('whatsapp.evolution.token') ?? ''));
        return [
            'evolution_base_url' => (string)($this->getText('whatsapp.evolution.base_url') ?? ''),
            'evolution_token_set' => $token !== '',
        ];
    }

    /** @param array<string,mixed> $input */
    public function saveWhatsappSettings(array $input): void
    {
        if (array_key_exists('evolution_base_url', $input)) {
            $val = trim((string)$input['evolution_base_url']);
            $this->setText('whatsapp.evolution.base_url', $val === '' ? null : $val);
        }

        if (array_key_exists('evolution_token', $input)) {
            $val = trim((string)$input['evolution_token']);
            $this->setText('whatsapp.evolution.token', $val === '' ? null : $val);
        }
    }

    /** @return array<string, string> */
    public function getBillingSettings(): array
    {
        $keys = [
            'asaas_env'                  => 'billing.asaas.env',
            'asaas_base_url'             => 'billing.asaas.base_url',
            'asaas_api_key'              => 'billing.asaas.api_key',
            'asaas_billing_type'         => 'billing.asaas.billing_type',
            'asaas_webhook_secret'       => 'billing.asaas.webhook_secret',
            'asaas_sandbox_base_url'     => 'billing.asaas.sandbox.base_url',
            'asaas_sandbox_api_key'      => 'billing.asaas.sandbox.api_key',
            'asaas_sandbox_webhook_secret' => 'billing.asaas.sandbox.webhook_secret',
            'asaas_prod_base_url'        => 'billing.asaas.prod.base_url',
            'asaas_prod_api_key'         => 'billing.asaas.prod.api_key',
            'asaas_prod_webhook_secret'  => 'billing.asaas.prod.webhook_secret',
            'mp_env'                     => 'billing.mercadopago.env',
            'mp_base_url'                => 'billing.mercadopago.base_url',
            'mp_access_token'            => 'billing.mercadopago.access_token',
            'mp_payer_email_default'     => 'billing.mercadopago.payer_email_default',
            'mp_webhook_secret'          => 'billing.mercadopago.webhook_secret',
            'mp_sandbox_base_url'        => 'billing.mercadopago.sandbox.base_url',
            'mp_sandbox_access_token'    => 'billing.mercadopago.sandbox.access_token',
            'mp_sandbox_webhook_secret'  => 'billing.mercadopago.sandbox.webhook_secret',
            'mp_prod_base_url'           => 'billing.mercadopago.prod.base_url',
            'mp_prod_access_token'       => 'billing.mercadopago.prod.access_token',
            'mp_prod_webhook_secret'     => 'billing.mercadopago.prod.webhook_secret',
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
            'asaas_env'                    => 'billing.asaas.env',
            'asaas_billing_type'           => 'billing.asaas.billing_type',
            'asaas_sandbox_base_url'       => 'billing.asaas.sandbox.base_url',
            'asaas_sandbox_api_key'        => 'billing.asaas.sandbox.api_key',
            'asaas_sandbox_webhook_secret' => 'billing.asaas.sandbox.webhook_secret',
            'asaas_prod_base_url'          => 'billing.asaas.prod.base_url',
            'asaas_prod_api_key'           => 'billing.asaas.prod.api_key',
            'asaas_prod_webhook_secret'    => 'billing.asaas.prod.webhook_secret',
            'mp_env'                       => 'billing.mercadopago.env',
            'mp_payer_email_default'       => 'billing.mercadopago.payer_email_default',
            'mp_sandbox_base_url'          => 'billing.mercadopago.sandbox.base_url',
            'mp_sandbox_access_token'      => 'billing.mercadopago.sandbox.access_token',
            'mp_sandbox_webhook_secret'    => 'billing.mercadopago.sandbox.webhook_secret',
            'mp_prod_base_url'             => 'billing.mercadopago.prod.base_url',
            'mp_prod_access_token'         => 'billing.mercadopago.prod.access_token',
            'mp_prod_webhook_secret'       => 'billing.mercadopago.prod.webhook_secret',
        ];

        foreach ($map as $field => $key) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $val = trim((string)$input[$field]);
            $this->setText($key, $val === '' ? null : $val);
        }

        // Sync active env keys to the legacy fields used by the gateway service
        $asaasEnv = trim((string)($input['asaas_env'] ?? $this->getText('billing.asaas.env') ?? 'sandbox'));
        if ($asaasEnv === 'production') {
            $this->setText('billing.asaas.base_url', $this->getText('billing.asaas.prod.base_url'));
            $this->setText('billing.asaas.api_key', $this->getText('billing.asaas.prod.api_key'));
            $this->setText('billing.asaas.webhook_secret', $this->getText('billing.asaas.prod.webhook_secret'));
        } else {
            $this->setText('billing.asaas.base_url', $this->getText('billing.asaas.sandbox.base_url'));
            $this->setText('billing.asaas.api_key', $this->getText('billing.asaas.sandbox.api_key'));
            $this->setText('billing.asaas.webhook_secret', $this->getText('billing.asaas.sandbox.webhook_secret'));
        }

        $mpEnv = trim((string)($input['mp_env'] ?? $this->getText('billing.mercadopago.env') ?? 'sandbox'));
        if ($mpEnv === 'production') {
            $this->setText('billing.mercadopago.base_url', $this->getText('billing.mercadopago.prod.base_url'));
            $this->setText('billing.mercadopago.access_token', $this->getText('billing.mercadopago.prod.access_token'));
            $this->setText('billing.mercadopago.webhook_secret', $this->getText('billing.mercadopago.prod.webhook_secret'));
        } else {
            $this->setText('billing.mercadopago.base_url', $this->getText('billing.mercadopago.sandbox.base_url'));
            $this->setText('billing.mercadopago.access_token', $this->getText('billing.mercadopago.sandbox.access_token'));
            $this->setText('billing.mercadopago.webhook_secret', $this->getText('billing.mercadopago.sandbox.webhook_secret'));
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
