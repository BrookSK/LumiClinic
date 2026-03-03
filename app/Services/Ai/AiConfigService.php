<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\ClinicSettingsRepository;
use App\Services\Auth\AuthService;
use App\Services\Security\CryptoService;

final class AiConfigService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{openai_key_set:bool} */
    public function getAiSettings(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $row = (new ClinicSettingsRepository($this->container->get(\PDO::class)))->findByClinicId($clinicId);
        $enc = is_array($row) ? (string)($row['openai_api_key_encrypted'] ?? '') : '';

        return ['openai_key_set' => trim($enc) !== ''];
    }

    public function setOpenAiApiKey(?string $apiKeyPlain, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $apiKeyPlain = $apiKeyPlain === null ? null : trim($apiKeyPlain);

        $encrypted = null;
        if ($apiKeyPlain !== null && $apiKeyPlain !== '') {
            $encrypted = (new CryptoService($this->container))->encrypt($clinicId, $apiKeyPlain);
        }

        (new ClinicSettingsRepository($this->container->get(\PDO::class)))->updateOpenAiApiKeyEncrypted($clinicId, $encrypted);

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            $userId,
            $clinicId,
            'settings.ai_update',
            ['fields' => ['openai_api_key']],
            $ip
        );
    }

    public function getOpenAiApiKeyPlain(): ?string
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $row = (new ClinicSettingsRepository($this->container->get(\PDO::class)))->findByClinicId($clinicId);
        $enc = is_array($row) ? (string)($row['openai_api_key_encrypted'] ?? '') : '';
        $enc = trim($enc);
        if ($enc === '') {
            return null;
        }

        return (new CryptoService($this->container))->decrypt($clinicId, $enc);
    }
}
