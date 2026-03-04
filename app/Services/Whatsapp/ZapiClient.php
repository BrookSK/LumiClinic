<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use App\Core\Container\Container;
use App\Services\Http\HttpClient;

final class ZapiClient
{
    public function __construct(private readonly Container $container) {}

    /** @return array<string,mixed> */
    public function sendText(string $phone, string $message, int $timeoutSeconds = 30): array
    {
        $settings = new WhatsappConfigService($this->container);
        $data = $settings->getWhatsappSettings();

        $instanceId = $data['zapi_instance_id'] ?? null;
        if ($instanceId === null || trim($instanceId) === '') {
            throw new \RuntimeException('WhatsApp (Z-API) não configurado: instance id.');
        }

        $token = $settings->getZapiTokenPlain();
        if ($token === null || trim($token) === '') {
            throw new \RuntimeException('WhatsApp (Z-API) não configurado: token.');
        }

        $phoneDigits = preg_replace('/\D+/', '', $phone);
        $phoneDigits = $phoneDigits === null ? '' : $phoneDigits;

        if ($phoneDigits === '') {
            throw new \RuntimeException('Telefone inválido.');
        }

        $url = 'https://api.z-api.io/instances/' . rawurlencode($instanceId) . '/token/' . rawurlencode($token) . '/send-text';

        $http = new HttpClient();
        $resp = $http->request('POST', $url, [], [
            'phone' => $phoneDigits,
            'message' => $message,
        ], $timeoutSeconds);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            throw new \RuntimeException('Falha ao enviar WhatsApp via Z-API.');
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

        $instanceId = $data['zapi_instance_id'] ?? null;
        if ($instanceId === null || trim($instanceId) === '') {
            throw new \RuntimeException('WhatsApp (Z-API) não configurado: instance id.');
        }

        $token = $settings->getZapiTokenPlain();
        if ($token === null || trim($token) === '') {
            throw new \RuntimeException('WhatsApp (Z-API) não configurado: token.');
        }

        $url = 'https://api.z-api.io/instances/' . rawurlencode($instanceId) . '/token/' . rawurlencode($token) . '/status';

        $http = new HttpClient();
        $resp = $http->request('GET', $url, [], null, $timeoutSeconds);

        return $resp['status'] >= 200 && $resp['status'] < 300;
    }
}
