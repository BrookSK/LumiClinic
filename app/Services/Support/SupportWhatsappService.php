<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Core\Container\Container;
use App\Services\System\SystemSettingsService;
use App\Services\Whatsapp\EvolutionClient;

/**
 * WhatsApp da PLATAFORMA para o suporte (instância própria, separada das instâncias das clínicas).
 * Usa somente o token/URL globais de /sys/settings/whatsapp (EvolutionClient::*Global/*Of); nunca o token de uma clínica.
 */
final class SupportWhatsappService
{
    public const DEFAULT_INSTANCE = 'lumiclinic-support';

    public function __construct(private readonly Container $container) {}

    /** Nome da instância (setting support.wa.instance), validado por /^[A-Za-z0-9_-]{3,40}$/. */
    public function instanceName(): string
    {
        $name = trim((string)((new SystemSettingsService($this->container))->getText('support.wa.instance') ?? ''));
        if ($name !== '' && preg_match('/^[A-Za-z0-9_-]{3,40}$/', $name) === 1) {
            return $name;
        }

        return self::DEFAULT_INSTANCE;
    }

    /** True quando token e URL globais da Evolution API existem. */
    public function isGlobalConfigured(): bool
    {
        try {
            $client = new EvolutionClient($this->container);
            $client->globalOnlyApiKey();
            $client->globalOnlyBaseUrl();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Normaliza telefone: só dígitos, prefixo 55 quando tem 10 ou 11 dígitos, exige 12 a 13 dígitos.
     * Exemplos: "(11) 91234-5678" => "5511912345678"; "+55 11 3456-7890" => "551134567890"; "1234" => null.
     */
    public static function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw);
        $digits = $digits === null ? '' : $digits;
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 10 || strlen($digits) === 11) {
            $digits = '55' . $digits;
        }
        $len = strlen($digits);

        return ($len >= 12 && $len <= 13) ? $digits : null;
    }

    /**
     * @return array{configured:bool,connected:bool,state:?string,label:string,error:?string}
     */
    public function status(): array
    {
        if (!$this->isGlobalConfigured()) {
            return ['configured' => false, 'connected' => false, 'state' => null, 'label' => 'Não configurado', 'error' => null];
        }

        try {
            $st = (new EvolutionClient($this->container))->connectionStateOf($this->instanceName());
        } catch (\Throwable $e) {
            return ['configured' => true, 'connected' => false, 'state' => null, 'label' => 'Erro ao consultar', 'error' => mb_substr($e->getMessage(), 0, 300)];
        }

        $state = $st['state'];
        $labels = ['open' => 'Conectado', 'connecting' => 'Aguardando leitura do QR Code', 'close' => 'Desconectado'];
        $label = $st['connected'] ? 'Conectado' : ($state !== null ? ($labels[$state] ?? $state) : 'Desconectado');

        return ['configured' => true, 'connected' => (bool)$st['connected'], 'state' => $state, 'label' => $label, 'error' => null];
    }

    /**
     * Solicita o QR Code (cria a instância se não existir).
     * Aceita as chaves 'base64' e 'code' (na raiz ou dentro de 'qrcode') e 'pairingCode', como o fluxo das clínicas.
     * O conteúdo do QR nunca é gravado em log.
     *
     * @return array{image:?string,code:string,pairing:string}
     */
    public function connect(): array
    {
        $data = (new EvolutionClient($this->container))->connectInstanceGlobal($this->instanceName());

        $pairing = isset($data['pairingCode']) ? trim((string)$data['pairingCode']) : '';
        $code = '';
        if (isset($data['qrcode']) && is_array($data['qrcode'])) {
            $code = isset($data['qrcode']['base64']) ? trim((string)$data['qrcode']['base64']) : '';
            if ($code === '') {
                $code = isset($data['qrcode']['code']) ? trim((string)$data['qrcode']['code']) : '';
            }
        }
        if ($code === '') {
            $code = isset($data['base64']) ? trim((string)$data['base64']) : '';
        }
        if ($code === '') {
            $code = isset($data['code']) ? trim((string)$data['code']) : '';
        }

        $image = null;
        if ($code !== '') {
            if (stripos($code, 'data:image/') === 0 && preg_match('#^data:image/(png|jpeg|jpg|gif|webp);base64,[A-Za-z0-9+/=\r\n]+$#i', $code) === 1) {
                $image = $code;
            } elseif (preg_match('/^[A-Za-z0-9+\/\r\n]+=*$/', $code) === 1 && strlen($code) > 120) {
                $image = 'data:image/png;base64,' . $code;
            }
        }

        return ['image' => $image, 'code' => $image === null ? mb_substr($code, 0, 600) : '', 'pairing' => mb_substr($pairing, 0, 40)];
    }

    public function disconnect(): void
    {
        (new EvolutionClient($this->container))->logoutInstanceGlobal($this->instanceName());
    }

    /** @throws \RuntimeException telefone inválido ou falha de envio */
    public function sendToNumber(string $phone, string $message, int $timeoutSeconds = 15): void
    {
        $normalized = self::normalizePhone($phone);
        if ($normalized === null) {
            throw new \RuntimeException('Telefone inválido.');
        }

        (new EvolutionClient($this->container))->sendTextToInstance($this->instanceName(), $normalized, $message, $timeoutSeconds);
    }
}
