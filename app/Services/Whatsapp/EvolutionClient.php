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

        // Ensure country code 55 (Brazil) is present
        if (strlen($phoneDigits) <= 11 && !str_starts_with($phoneDigits, '55')) {
            $phoneDigits = '55' . $phoneDigits;
        }

        $url = $this->baseUrl() . '/message/sendText/' . rawurlencode(trim($instance));

        $http = new HttpClient();

        // Try v2 format first, fall back to v1 format
        $payload = [
            'number' => $phoneDigits,
            'text' => $message,
        ];

        $resp = $http->request('POST', $url, ['apikey' => $apiKey], $payload, $timeoutSeconds);

        // If v2 format fails with 400, try v1 format with textMessage
        if ($resp['status'] === 400) {
            $payloadV1 = [
                'number' => $phoneDigits,
                'textMessage' => ['text' => $message],
            ];
            $resp = $http->request('POST', $url, ['apikey' => $apiKey], $payloadV1, $timeoutSeconds);
        }

        // If both fail, try the /message/sendText endpoint without instance in URL (some versions)
        if ($resp['status'] === 400 || $resp['status'] === 404) {
            $urlAlt = $this->baseUrl() . '/message/sendText';
            $payloadAlt = [
                'number' => $phoneDigits,
                'text' => $message,
                'instanceName' => trim($instance),
            ];
            $resp = $http->request('POST', $urlAlt, ['apikey' => $apiKey], $payloadAlt, $timeoutSeconds);
        }

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            $body = trim((string)($resp['body'] ?? ''));
            $detail = '';
            if ($body !== '') {
                $decoded = json_decode($body, true);
                if (is_array($decoded) && isset($decoded['message'])) {
                    $detail = is_array($decoded['message']) ? implode('; ', $decoded['message']) : (string)$decoded['message'];
                } elseif (is_array($decoded) && isset($decoded['error'])) {
                    $detail = (string)$decoded['error'];
                } else {
                    $detail = mb_substr($body, 0, 200, 'UTF-8');
                }
            }
            $msg = 'Falha ao enviar WhatsApp via Evolution API (HTTP ' . $resp['status'] . ')';
            if ($detail !== '') {
                $msg .= ': ' . $detail;
            }
            error_log('[Evolution API Error] URL=' . $url . ' phone=' . $phoneDigits . ' status=' . $resp['status'] . ' body=' . mb_substr($body, 0, 500, 'UTF-8'));
            throw new \RuntimeException($msg);
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

    /** @return array{connected:bool,state:?string,raw:mixed} */
    public function connectionState(int $timeoutSeconds = 15): array
    {
        $settings = new WhatsappConfigService($this->container);
        $data = $settings->getWhatsappSettings();

        $instance = $data['evolution_instance'] ?? null;
        if ($instance === null || trim($instance) === '') {
            throw new \RuntimeException('WhatsApp (Evolution API) não configurado: instância.');
        }

        $apiKey = $this->apiKey();
        $url = $this->baseUrl() . '/instance/connectionState/' . rawurlencode(trim((string)$instance));

        $http = new HttpClient();
        $resp = $http->request('GET', $url, ['apikey' => $apiKey], null, $timeoutSeconds);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            return ['connected' => false, 'state' => null, 'raw' => $resp['body']];
        }

        $json = $resp['json'];
        if (!is_array($json)) {
            return ['connected' => true, 'state' => null, 'raw' => $resp['body']];
        }

        $state = null;
        if (isset($json['instance']) && is_array($json['instance'])) {
            $state = isset($json['instance']['state']) ? (string)$json['instance']['state'] : null;
        }

        $state = $state === null ? null : trim($state);
        $connected = $state === 'open';

        return ['connected' => $connected, 'state' => ($state === '' ? null : $state), 'raw' => $json];
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

    // ------------------------------------------------------------------
    // Métodos para a instância da PLATAFORMA (suporte LumiClinic).
    //
    // A Evolution API já era usada com QR Code por clínica (instância lc-<id>), mas não existia instância da
    // plataforma. Estes métodos usam SOMENTE o token e a URL globais de /sys/settings/whatsapp e NUNCA chamam
    // apiKey() (que cai no token da clínica lendo a sessão). Os métodos acima não foram alterados.
    // ------------------------------------------------------------------

    /** Token global da Evolution API. Nunca usa a chave de uma clínica. */
    public function globalOnlyApiKey(): string
    {
        $settings = new SystemSettingsService($this->container);
        $global = trim((string)($settings->getText('whatsapp.evolution.token') ?? ''));
        if ($global === '') {
            throw new \RuntimeException('Configure a Evolution API global em /sys/settings/whatsapp');
        }

        return $global;
    }

    /** URL base global (configuração do sistema ou EVOLUTION_BASE_URL). */
    public function globalOnlyBaseUrl(): string
    {
        try {
            return $this->baseUrl();
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('Configure a Evolution API global em /sys/settings/whatsapp');
        }
    }

    private function shortBody(mixed $body): string
    {
        $body = trim((string)($body ?? ''));
        if (strlen($body) > 500) {
            $body = substr($body, 0, 500) . '...';
        }

        return $body !== '' ? (' Resposta: ' . $body) : '';
    }

    /** @return array<string,mixed> */
    public function connectInstanceGlobal(string $instance, int $timeoutSeconds = 30): array
    {
        $instance = trim($instance);
        if ($instance === '') {
            throw new \RuntimeException('Instância inválida.');
        }

        $apiKey = $this->globalOnlyApiKey();
        $baseUrl = $this->globalOnlyBaseUrl();
        $http = new HttpClient();

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $resp = $http->request('GET', $baseUrl . '/instance/connect/' . rawurlencode($instance), ['apikey' => $apiKey], null, $timeoutSeconds);
            if ($resp['status'] >= 200 && $resp['status'] < 300) {
                return is_array($resp['json']) ? $resp['json'] : ['raw' => $resp['body']];
            }

            if ($attempt === 0 && $this->isInstanceMissingResponse((int)$resp['status'], (string)($resp['body'] ?? ''))) {
                $created = $http->request('POST', $baseUrl . '/instance/create', ['apikey' => $apiKey], [
                    'instanceName' => $instance,
                    'integration' => 'WHATSAPP-BAILEYS',
                    'qrcode' => true,
                ], $timeoutSeconds);
                if ($created['status'] < 200 || $created['status'] >= 300) {
                    throw new \RuntimeException('Falha ao criar instância na Evolution API (HTTP ' . (int)$created['status'] . ').' . $this->shortBody($created['body'] ?? ''));
                }
                continue;
            }

            throw new \RuntimeException('Falha ao solicitar QR Code na Evolution API (HTTP ' . (int)$resp['status'] . ').' . $this->shortBody($resp['body'] ?? ''));
        }

        throw new \RuntimeException('Falha ao solicitar QR Code na Evolution API.');
    }

    public function logoutInstanceGlobal(string $instance, int $timeoutSeconds = 30): void
    {
        $instance = trim($instance);
        if ($instance === '') {
            throw new \RuntimeException('Instância inválida.');
        }

        $apiKey = $this->globalOnlyApiKey();
        $url = $this->globalOnlyBaseUrl() . '/instance/logout/' . rawurlencode($instance);

        $resp = (new HttpClient())->request('POST', $url, ['apikey' => $apiKey], null, $timeoutSeconds);
        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            throw new \RuntimeException('Falha ao desconectar o WhatsApp na Evolution API (HTTP ' . (int)$resp['status'] . ').' . $this->shortBody($resp['body'] ?? ''));
        }
    }

    /**
     * Estado de uma instância explícita, com credenciais globais.
     * @return array{connected:bool,state:?string,raw:mixed}
     */
    public function connectionStateOf(string $instance, int $timeoutSeconds = 15): array
    {
        $instance = trim($instance);
        if ($instance === '') {
            throw new \RuntimeException('Instância inválida.');
        }

        $apiKey = $this->globalOnlyApiKey();
        $url = $this->globalOnlyBaseUrl() . '/instance/connectionState/' . rawurlencode($instance);

        $resp = (new HttpClient())->request('GET', $url, ['apikey' => $apiKey], null, $timeoutSeconds);
        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            return ['connected' => false, 'state' => null, 'raw' => $resp['body']];
        }

        $json = $resp['json'];
        if (!is_array($json)) {
            return ['connected' => false, 'state' => null, 'raw' => $resp['body']];
        }

        $state = null;
        if (isset($json['instance']) && is_array($json['instance']) && isset($json['instance']['state'])) {
            $state = trim((string)$json['instance']['state']);
        }

        return ['connected' => $state === 'open', 'state' => ($state === '' ? null : $state), 'raw' => $json];
    }

    /**
     * Envia texto por uma instância explícita, com credenciais globais (mesmo fallback v2/v1/sem instância do sendText).
     * @return array<string,mixed>
     */
    public function sendTextToInstance(string $instance, string $phone, string $message, int $timeoutSeconds = 15): array
    {
        $instance = trim($instance);
        if ($instance === '') {
            throw new \RuntimeException('Instância inválida.');
        }

        $apiKey = $this->globalOnlyApiKey();
        $baseUrl = $this->globalOnlyBaseUrl();

        $phoneDigits = preg_replace('/\D+/', '', $phone);
        $phoneDigits = $phoneDigits === null ? '' : $phoneDigits;
        if ($phoneDigits === '') {
            throw new \RuntimeException('Telefone inválido.');
        }
        if (strlen($phoneDigits) <= 11 && !str_starts_with($phoneDigits, '55')) {
            $phoneDigits = '55' . $phoneDigits;
        }

        $url = $baseUrl . '/message/sendText/' . rawurlencode($instance);
        $http = new HttpClient();

        $resp = $http->request('POST', $url, ['apikey' => $apiKey], ['number' => $phoneDigits, 'text' => $message], $timeoutSeconds);
        if ($resp['status'] === 400) {
            $resp = $http->request('POST', $url, ['apikey' => $apiKey], ['number' => $phoneDigits, 'textMessage' => ['text' => $message]], $timeoutSeconds);
        }
        if ($resp['status'] === 400 || $resp['status'] === 404) {
            $resp = $http->request('POST', $baseUrl . '/message/sendText', ['apikey' => $apiKey], [
                'number' => $phoneDigits,
                'text' => $message,
                'instanceName' => $instance,
            ], $timeoutSeconds);
        }

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            $body = trim((string)($resp['body'] ?? ''));
            $detail = '';
            if ($body !== '') {
                $decoded = json_decode($body, true);
                if (is_array($decoded) && isset($decoded['message'])) {
                    $detail = is_array($decoded['message']) ? json_encode($decoded['message'], JSON_UNESCAPED_UNICODE) : (string)$decoded['message'];
                } elseif (is_array($decoded) && isset($decoded['error'])) {
                    $detail = (string)$decoded['error'];
                } else {
                    $detail = mb_substr($body, 0, 200, 'UTF-8');
                }
            }
            $msg = 'Falha ao enviar WhatsApp via Evolution API (HTTP ' . (int)$resp['status'] . ')';
            if ($detail !== '') {
                $msg .= ': ' . mb_substr($detail, 0, 200, 'UTF-8');
            }
            throw new \RuntimeException($msg);
        }

        return is_array($resp['json']) ? $resp['json'] : ['raw' => $resp['body']];
    }
}
