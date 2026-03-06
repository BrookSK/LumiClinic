<?php

declare(strict_types=1);

namespace App\Services\Google;

use App\Core\Container\Container;
use App\Services\System\SystemSettingsService;

final class GoogleOAuthConfigService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{client_id:?string,client_secret_set:bool} */
    public function getConfig(): array
    {
        $settings = new SystemSettingsService($this->container);
        $id = trim((string)($settings->getText('google.oauth.client_id') ?? ''));
        $secret = trim((string)($settings->getText('google.oauth.client_secret') ?? ''));

        return [
            'client_id' => $id === '' ? null : $id,
            'client_secret_set' => $secret !== '',
        ];
    }

    public function getClientId(): ?string
    {
        $id = trim((string)((new SystemSettingsService($this->container))->getText('google.oauth.client_id') ?? ''));
        return $id === '' ? null : $id;
    }

    public function getClientSecret(): ?string
    {
        $secret = trim((string)((new SystemSettingsService($this->container))->getText('google.oauth.client_secret') ?? ''));
        return $secret === '' ? null : $secret;
    }

    public function setConfig(?string $clientId, ?string $clientSecret): void
    {
        $clientId = $clientId === null ? null : trim($clientId);
        $clientSecret = $clientSecret === null ? null : trim($clientSecret);

        $settings = new SystemSettingsService($this->container);
        $settings->setText('google.oauth.client_id', $clientId === '' ? null : $clientId);
        $settings->setText('google.oauth.client_secret', $clientSecret === '' ? null : $clientSecret);
    }
}
