<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use App\Core\Container\Container;
use App\Services\Http\HttpClient;
use App\Services\System\SystemSettingsService;

final class EvolutionClient
{
    public function __construct(private readonly Container $container) {}

    private function isInstanceMissingResponse(int $status, string $body): bool
    {
        if ($status !== 404) {
            return false;
        }

        $body = trim($body);
        if ($body === '') {
            return false;
        }

        return stripos($body, 'instance does not exist') !== false;
    }

    /** @return array<string,mixed> */
    private function createInstance(string $instance, int $timeoutSeconds = 30): array
    {
        $instance = trim($instance);
        if ($instance === '') {
            throw new \RuntimeException('Instância inválida.');
        }

        $apiKey = $this->apiKey();
        $url = $this->baseUrl() . '/instance/create';

        $http = new HttpClient();
        $resp = $http->request('POST', $url, ['apikey' => $apiKey], [
            'instanceName' => $instance,
            'integration' => 'WHATSAPP-BAILEYS',
            'qrcode' => true,
        ], $timeoutSeconds);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            $body = isset($resp['body']) ? (string)$resp['body'] : '';
            $body = trim($body);
            if (strlen($body) > 500) {
                $body = substr($body, 0, 500) . '...';
            }
            $suffix = $body !== '' ? (' Resposta: ' . $body) : '';
            throw new \RuntimeException('Falha ao criar instância na Evolution API (HTTP ' . (int)$resp['status'] . ').' . $suffix);
        }

        $json = $resp['json'];
        if (!is_array($json)) {
            return ['raw' => $resp['body']];
        }

        return $json;
    }

    private function apiKey(): string
    {
        $settings = new SystemSettingsService($this->container);
        $global = trim((string)($settings->getText('whatsapp.evolution.token') ?? ''));
        if ($global !== '') {
            return $global;
        }

        $clinicSettings = new WhatsappConfigService($this->container);
        $apiKey = $clinicSettings->getEvolutionApiKeyPlain();
        $apiKey = $apiKey === null ? '' : trim($apiKey);
        if ($apiKey === '') {
            throw new \RuntimeException('WhatsApp (Evolution API) não configurado: token/apikey.');
        }

        return $apiKey;
    }

    private function baseUrl(): string
    {
        $settings = new SystemSettingsService($this->container);
        $global = trim((string)($settings->getText('whatsapp.evolution.base_url') ?? ''));
        if ($global !== '') {
            return rtrim($global, '/');
        }

        $config = $this->container->has('config') ? $this->container->get('config') : null;
        $baseUrl = is_array($config)
            && isset($config['whatsapp'])
            && is_array($config['whatsapp'])
            && isset($config['whatsapp']['evolution'])
            && is_array($config['whatsapp']['evolution'])
            ? (string)($config['whatsapp']['evolution']['base_url'] ?? '')
            : '';

        $baseUrl = rtrim(trim($baseUrl), '/');
        if ($baseUrl === '') {
            throw new \RuntimeException('WhatsApp (Evolution API) não configurado: base url.');
        }

        return $baseUrl;
    }

    /** @return array<string,mixed> */
    public function connectInstance(string $instance, ?string $phone = null, int $timeoutSeconds = 30): array
    {
        return $this->connectInstanceInternal($instance, $phone, $timeoutSeconds, 0);
    }

    /** @return array<string,mixed> */
    private function connectInstanceInternal(string $instance, ?string $phone, int $timeoutSeconds, int $attempt): array
    {
        $instance = trim($instance);
        if ($instance === '') {
            throw new \RuntimeException('Instância inválida.');
        }

        $apiKey = $this->apiKey();
        $url = $this->baseUrl() . '/instance/connect/' . rawurlencode($instance);
        if ($phone !== null) {
            $phone = trim($phone);
            if ($phone !== '') {
                $url .= '?phone=' . rawurlencode($phone);
            }
        }

        $http = new HttpClient();
        $resp = $http->request('GET', $url, ['apikey' => $apiKey], null, $timeoutSeconds);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            $body = isset($resp['body']) ? (string)$resp['body'] : '';

            if ($attempt === 0 && $this->isInstanceMissingResponse((int)$resp['status'], $body)) {
                $this->createInstance($instance, $timeoutSeconds);
                return $this->connectInstanceInternal($instance, $phone, $timeoutSeconds, 1);
            }

            $body = trim($body);
            if (strlen($body) > 500) {
                $body = substr($body, 0, 500) . '...';
            }
            $suffix = $body !== '' ? (' Resposta: ' . $body) : '';
            throw new \RuntimeException('Falha ao solicitar QR Code na Evolution API (HTTP ' . (int)$resp['status'] . ').' . $suffix);
        }

        $json = $resp['json'];
        if (!is_array($json)) {
            return ['raw' => $resp['body']];
        }

        return $json;
    }

    /** @return array<string,mixed> */
    public function sendText(string $phone, string $message, int $timeoutSeconds = 30): array
    {
        $settings = new WhatsappConfigService($this->container);
        $data = $settings->getWhatsappSettings();

        $instance = $data['evolution_instance'] ?? null;
        if ($instance === null || trim($instance) === '') {
            throw new \RuntimeException('WhatsApp (Evolution API) não configurado: instância.');
        }

        $apiKey = $this->apiKey();

        $phoneDigits = preg_replace('/\D+/', '', $phone);
        $phoneDigits = $phoneDigits === null ? '' : $phoneDigits;

        if ($phoneDigits === '') {
            throw new \RuntimeException('Telefone inválido.');
        }

        $url = $this->baseUrl() . '/message/sendText/' . rawurlencode(trim($instance));

        $http = new HttpClient();
        $resp = $http->request('POST', $url, ['apikey' => $apiKey], [
            'number' => $phoneDigits,
            'text' => $message,
        ], $timeoutSeconds);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            throw new \RuntimeException('Falha ao enviar WhatsApp via Evolution API.');
        }

        $json = $resp['json'];
        if (!is_array($json)) {
            return ['raw' => $resp['body']];
        }

        /** @var array<string,mixed> */
        return $json;
    }

    public function logoutInstance(string $instance, int $timeoutSeconds = 30): void
    {
        $instance = trim($instance);
        if ($instance === '') {
            throw new \RuntimeException('Instância inválida.');
        }

        $apiKey = $this->apiKey();
        $url = $this->baseUrl() . '/instance/logout/' . rawurlencode($instance);

        $http = new HttpClient();
        $resp = $http->request('POST', $url, ['apikey' => $apiKey], null, $timeoutSeconds);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            $body = isset($resp['body']) ? (string)$resp['body'] : '';
            $body = trim($body);
            if (strlen($body) > 500) {
                $body = substr($body, 0, 500) . '...';
            }
            $suffix = $body !== '' ? (' Resposta: ' . $body) : '';
            throw new \RuntimeException('Falha ao resetar sessão do WhatsApp (logout) na Evolution API (HTTP ' . (int)$resp['status'] . ').' . $suffix);
        }
    }

    public function instanceStatus(int $timeoutSeconds = 15): bool
    {
        $settings = new WhatsappConfigService($this->container);
        $data = $settings->getWhatsappSettings();

        $instance = $data['evolution_instance'] ?? null;
        if ($instance === null || trim($instance) === '') {
            throw new \RuntimeException('WhatsApp (Evolution API) não configurado: instância.');
        }

        $apiKey = $this->apiKey();

        $url = $this->baseUrl() . '/instance/connectionState/' . rawurlencode(trim($instance));

        $http = new HttpClient();
        $resp = $http->request('GET', $url, ['apikey' => $apiKey], null, $timeoutSeconds);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            return false;
        }

        $json = $resp['json'];
        if (!is_array($json)) {
            return true;
        }

        $state = null;
        if (isset($json['instance']) && is_array($json['instance'])) {
            $state = isset($json['instance']['state']) ? (string)$json['instance']['state'] : null;
        }

        $state = $state === null ? null : trim($state);
        if ($state === null || $state === '') {
            return true;
        }

        return $state === 'open';
    }
}
