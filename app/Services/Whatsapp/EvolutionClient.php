<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use App\Core\Container\Container;
use App\Services\Http\HttpClient;
use App\Services\System\SystemSettingsService;

final class EvolutionClient
{
    public function __construct(private readonly Container $container) {}

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
    public function sendText(string $phone, string $message, int $timeoutSeconds = 30): array
    {
        $settings = new WhatsappConfigService($this->container);
        $data = $settings->getWhatsappSettings();

        $instance = $data['evolution_instance'] ?? null;
        if ($instance === null || trim($instance) === '') {
            throw new \RuntimeException('WhatsApp (Evolution API) não configurado: instância.');
        }

        $apiKey = $settings->getEvolutionApiKeyPlain();
        if ($apiKey === null || trim($apiKey) === '') {
            throw new \RuntimeException('WhatsApp (Evolution API) não configurado: apikey.');
        }

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

    public function instanceStatus(int $timeoutSeconds = 15): bool
    {
        $settings = new WhatsappConfigService($this->container);
        $data = $settings->getWhatsappSettings();

        $instance = $data['evolution_instance'] ?? null;
        if ($instance === null || trim($instance) === '') {
            throw new \RuntimeException('WhatsApp (Evolution API) não configurado: instância.');
        }

        $apiKey = $settings->getEvolutionApiKeyPlain();
        if ($apiKey === null || trim($apiKey) === '') {
            throw new \RuntimeException('WhatsApp (Evolution API) não configurado: apikey.');
        }

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
