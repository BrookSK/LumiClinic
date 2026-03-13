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

    /** @return array{evolution_instance:?string,evolution_apikey_set:bool} */
    public function getWhatsappSettings(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $row = (new ClinicSettingsRepository($this->container->get(\PDO::class)))->findByClinicId($clinicId);

        $instance = is_array($row) ? (string)($row['evolution_instance'] ?? '') : '';
        $enc = is_array($row) ? (string)($row['evolution_apikey_encrypted'] ?? '') : '';

        $instance = trim($instance);
        $enc = trim($enc);

        return [
            'evolution_instance' => $instance === '' ? null : $instance,
            'evolution_apikey_set' => $enc !== '',
        ];
    }

    public function setEvolutionConfig(?string $instance, ?string $apiKeyPlain, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $instance = $instance === null ? null : trim($instance);
        $apiKeyPlain = $apiKeyPlain === null ? null : trim($apiKeyPlain);

        $encrypted = null;
        if ($apiKeyPlain !== null && $apiKeyPlain !== '') {
            $encrypted = (new CryptoService($this->container))->encrypt($clinicId, $apiKeyPlain);
        }

        (new ClinicSettingsRepository($this->container->get(\PDO::class)))->updateEvolutionConfig($clinicId, $instance, $encrypted);

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            $userId,
            $clinicId,
            'settings.whatsapp_update',
            ['fields' => ['evolution_instance', 'evolution_apikey']],
            $ip
        );
    }

    public function clearEvolutionConfig(string $ip): void
    {
        $this->setEvolutionConfig(null, null, $ip);
    }

    public function setEvolutionInstance(?string $instance, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $instance = $instance === null ? null : trim($instance);

        (new ClinicSettingsRepository($this->container->get(\PDO::class)))->updateEvolutionConfig($clinicId, $instance, null);

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            $userId,
            $clinicId,
            'settings.whatsapp_update',
            ['fields' => ['evolution_instance']],
            $ip
        );
    }

    public function getEvolutionApiKeyPlain(): ?string
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $row = (new ClinicSettingsRepository($this->container->get(\PDO::class)))->findByClinicId($clinicId);
        $enc = is_array($row) ? (string)($row['evolution_apikey_encrypted'] ?? '') : '';
        $enc = trim($enc);
        if ($enc === '') {
            return null;
        }

        return (new CryptoService($this->container))->decrypt($clinicId, $enc);
    }
}
