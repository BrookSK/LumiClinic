<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\ClinicSettingsRepository;
use App\Services\Auth\AuthService;
use App\Services\Security\CryptoService;

final class WhatsappConfigService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{zapi_instance_id:?string,zapi_token_set:bool} */
    public function getWhatsappSettings(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $row = (new ClinicSettingsRepository($this->container->get(\PDO::class)))->findByClinicId($clinicId);

        $instanceId = is_array($row) ? (string)($row['zapi_instance_id'] ?? '') : '';
        $enc = is_array($row) ? (string)($row['zapi_token_encrypted'] ?? '') : '';

        $instanceId = trim($instanceId);
        $enc = trim($enc);

        return [
            'zapi_instance_id' => $instanceId === '' ? null : $instanceId,
            'zapi_token_set' => $enc !== '',
        ];
    }

    public function setZapiConfig(?string $instanceId, ?string $tokenPlain, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $instanceId = $instanceId === null ? null : trim($instanceId);
        $tokenPlain = $tokenPlain === null ? null : trim($tokenPlain);

        $encrypted = null;
        if ($tokenPlain !== null && $tokenPlain !== '') {
            $encrypted = (new CryptoService($this->container))->encrypt($clinicId, $tokenPlain);
        }

        (new ClinicSettingsRepository($this->container->get(\PDO::class)))->updateZapiConfig($clinicId, $instanceId, $encrypted);

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            $userId,
            $clinicId,
            'settings.whatsapp_update',
            ['fields' => ['zapi_instance_id', 'zapi_token']],
            $ip
        );
    }

    public function clearZapiConfig(string $ip): void
    {
        $this->setZapiConfig(null, null, $ip);
    }

    public function getZapiTokenPlain(): ?string
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $row = (new ClinicSettingsRepository($this->container->get(\PDO::class)))->findByClinicId($clinicId);
        $enc = is_array($row) ? (string)($row['zapi_token_encrypted'] ?? '') : '';
        $enc = trim($enc);
        if ($enc === '') {
            return null;
        }

        return (new CryptoService($this->container))->decrypt($clinicId, $enc);
    }
}
