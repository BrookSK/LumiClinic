<?php

declare(strict_types=1);

namespace App\Services\Google;

use App\Core\Container\Container;

final class GoogleCalendarOAuthService
{
    public function __construct(private readonly Container $container) {}

    public function isAvailable(): bool
    {
        return class_exists('Google\\Client');
    }

    public function buildAuthUrl(string $redirectUri, string $state, string $calendarId = 'primary'): string
    {
        $cfg = new GoogleOAuthConfigService($this->container);
        $clientId = $cfg->getClientId();
        $clientSecret = $cfg->getClientSecret();

        if ($clientId === null || $clientSecret === null) {
            throw new \RuntimeException('Google OAuth não configurado.');
        }

        /** @var \Google\Client $client */
        $client = new \Google\Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);

        $client->setAccessType('offline');
        $client->setPrompt('consent');

        $client->setState($state);

        $client->addScope('https://www.googleapis.com/auth/calendar');

        $client->setIncludeGrantedScopes(true);

        $client->setLoginHint('');

        $client->setApprovalPrompt('force');

        return $client->createAuthUrl() . '&' . http_build_query(['calendar_id' => $calendarId]);
    }

    /** @return array<string,mixed> */
    public function exchangeCode(string $redirectUri, string $code): array
    {
        $cfg = new GoogleOAuthConfigService($this->container);
        $clientId = $cfg->getClientId();
        $clientSecret = $cfg->getClientSecret();

        if ($clientId === null || $clientSecret === null) {
            throw new \RuntimeException('Google OAuth não configurado.');
        }

        /** @var \Google\Client $client */
        $client = new \Google\Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);

        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (!is_array($token) || isset($token['error'])) {
            $msg = is_array($token) ? (string)($token['error_description'] ?? $token['error'] ?? 'oauth_error') : 'oauth_error';
            throw new \RuntimeException('Falha no OAuth: ' . $msg);
        }

        return $token;
    }
}
