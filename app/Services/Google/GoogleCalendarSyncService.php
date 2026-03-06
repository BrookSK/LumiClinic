<?php

declare(strict_types=1);

namespace App\Services\Google;

use App\Core\Container\Container;
use App\Repositories\AppointmentRepository;
use App\Repositories\ClinicSettingsRepository;
use App\Repositories\GoogleCalendarAppointmentEventRepository;
use App\Repositories\GoogleCalendarSyncLogRepository;
use App\Repositories\GoogleOAuthTokenRepository;
use App\Services\Security\CryptoService;

final class GoogleCalendarSyncService
{
    public function __construct(private readonly Container $container) {}

    public function isAvailable(): bool
    {
        return class_exists('Google\\Client') && class_exists('Google\\Service\\Calendar');
    }

    public function syncAppointment(int $clinicId, int $appointmentId): void
    {
        $pdo = $this->container->get(\PDO::class);

        $appt = (new AppointmentRepository($pdo))->findDetailedById($clinicId, $appointmentId);
        if ($appt === null) {
            return;
        }

        $userId = isset($appt['created_by_user_id']) && $appt['created_by_user_id'] !== null ? (int)$appt['created_by_user_id'] : null;
        if ($userId === null || $userId <= 0) {
            $this->log($clinicId, null, null, $appointmentId, 'sync', 'skipped', 'Agendamento sem created_by_user_id.', null);
            return;
        }

        $tokenRepo = new GoogleOAuthTokenRepository($pdo);
        $token = $tokenRepo->findActiveByClinicUser($clinicId, $userId);
        if ($token === null) {
            $this->log($clinicId, $userId, null, $appointmentId, 'sync', 'skipped', 'Usuário sem Google conectado.', null);
            return;
        }

        $tokenId = (int)$token['id'];
        $calendarId = trim((string)($token['calendar_id'] ?? ''));
        if ($calendarId === '') {
            $calendarId = 'primary';
        }

        if (!$this->isAvailable()) {
            $this->log($clinicId, $userId, $tokenId, $appointmentId, 'sync', 'failed', 'Dependência google/apiclient ausente.', null);
            return;
        }

        $refreshEnc = trim((string)($token['refresh_token_encrypted'] ?? ''));
        if ($refreshEnc === '') {
            $this->log($clinicId, $userId, $tokenId, $appointmentId, 'sync', 'failed', 'Refresh token ausente.', null);
            return;
        }

        $refresh = (new CryptoService($this->container))->decrypt($clinicId, $refreshEnc);

        $cfg = new GoogleOAuthConfigService($this->container);
        $clientId = $cfg->getClientId();
        $clientSecret = $cfg->getClientSecret();
        if ($clientId === null || $clientSecret === null) {
            $this->log($clinicId, $userId, $tokenId, $appointmentId, 'sync', 'failed', 'Google OAuth não configurado no sistema.', null);
            return;
        }

        /** @var \Google\Client $client */
        $client = new \Google\Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setAccessType('offline');
        $client->setScopes(['https://www.googleapis.com/auth/calendar']);
        $client->setIncludeGrantedScopes(true);
        $client->setPrompt('consent');

        $client->refreshToken($refresh);
        $accessToken = $client->getAccessToken();
        if (!is_array($accessToken) || !isset($accessToken['access_token'])) {
            $this->log($clinicId, $userId, $tokenId, $appointmentId, 'sync', 'failed', 'Falha ao obter access token.', ['access_token' => $accessToken]);
            return;
        }

        $tokenRepo->upsert(
            $clinicId,
            $userId,
            isset($token['scopes']) ? (string)$token['scopes'] : null,
            (string)($accessToken['access_token'] ?? ''),
            $refreshEnc,
            isset($accessToken['expires_in']) ? (new \DateTimeImmutable('now'))->modify('+' . (int)$accessToken['expires_in'] . ' seconds')->format('Y-m-d H:i:s') : null,
            $calendarId,
            null
        );

        /** @var \Google\Service\Calendar $cal */
        $cal = new \Google\Service\Calendar($client);

        $mapRepo = new GoogleCalendarAppointmentEventRepository($pdo);
        $mapped = $mapRepo->findByClinicAppointment($clinicId, $appointmentId);

        $status = (string)($appt['status'] ?? '');
        $shouldDelete = in_array($status, ['cancelled', 'no_show'], true);

        if ($shouldDelete) {
            if ($mapped !== null && isset($mapped['google_event_id']) && (string)$mapped['google_event_id'] !== '') {
                $eventId = (string)$mapped['google_event_id'];
                try {
                    $cal->events->delete($calendarId, $eventId);
                    $mapRepo->softDeleteByClinicAppointment($clinicId, $appointmentId);
                    $this->log($clinicId, $userId, $tokenId, $appointmentId, 'delete', 'ok', null, ['event_id' => $eventId]);
                } catch (\Throwable $e) {
                    $mapRepo->upsert($clinicId, $appointmentId, $tokenId, $eventId, $calendarId, null, $e->getMessage());
                    $this->log($clinicId, $userId, $tokenId, $appointmentId, 'delete', 'failed', $e->getMessage(), ['event_id' => $eventId]);
                    throw $e;
                }
            } else {
                $this->log($clinicId, $userId, $tokenId, $appointmentId, 'delete', 'skipped', 'Sem event_id mapeado.', null);
            }
            return;
        }

        $tz = 'UTC';
        $settings = (new ClinicSettingsRepository($pdo))->findByClinicId($clinicId);
        if (is_array($settings) && isset($settings['timezone']) && trim((string)$settings['timezone']) !== '') {
            $tz = (string)$settings['timezone'];
        }

        $startAt = (string)($appt['start_at'] ?? '');
        $endAt = (string)($appt['end_at'] ?? '');

        $summary = trim((string)($appt['patient_name'] ?? ''));
        $serviceName = trim((string)($appt['service_name'] ?? ''));
        if ($serviceName !== '') {
            $summary = $summary !== '' ? ($summary . ' — ' . $serviceName) : $serviceName;
        }
        if ($summary === '') {
            $summary = 'Agendamento';
        }

        $descriptionParts = [];
        $profName = trim((string)($appt['professional_name'] ?? ''));
        if ($profName !== '') {
            $descriptionParts[] = 'Profissional: ' . $profName;
        }
        $notes = trim((string)($appt['notes'] ?? ''));
        if ($notes !== '') {
            $descriptionParts[] = 'Obs: ' . $notes;
        }
        $description = $descriptionParts ? implode("\n", $descriptionParts) : null;

        $event = new \Google\Service\Calendar\Event([
            'summary' => $summary,
            'description' => $description,
            'start' => ['dateTime' => $this->toIsoLocal($startAt, $tz), 'timeZone' => $tz],
            'end' => ['dateTime' => $this->toIsoLocal($endAt, $tz), 'timeZone' => $tz],
        ]);

        $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        if ($mapped !== null && isset($mapped['google_event_id']) && (string)$mapped['google_event_id'] !== '') {
            $eventId = (string)$mapped['google_event_id'];
            try {
                $updated = $cal->events->update($calendarId, $eventId, $event);
                $newId = is_object($updated) && method_exists($updated, 'getId') ? (string)$updated->getId() : $eventId;
                $mapRepo->upsert($clinicId, $appointmentId, $tokenId, $newId, $calendarId, $now, null);
                $this->log($clinicId, $userId, $tokenId, $appointmentId, 'update', 'ok', null, ['event_id' => $newId]);
            } catch (\Throwable $e) {
                $mapRepo->upsert($clinicId, $appointmentId, $tokenId, $eventId, $calendarId, null, $e->getMessage());
                $this->log($clinicId, $userId, $tokenId, $appointmentId, 'update', 'failed', $e->getMessage(), ['event_id' => $eventId]);
                throw $e;
            }
            return;
        }

        try {
            $created = $cal->events->insert($calendarId, $event);
            $eventId = is_object($created) && method_exists($created, 'getId') ? (string)$created->getId() : null;
            if ($eventId === null || trim($eventId) === '') {
                throw new \RuntimeException('Falha ao obter ID do evento do Google.');
            }

            $mapRepo->upsert($clinicId, $appointmentId, $tokenId, $eventId, $calendarId, $now, null);
            $this->log($clinicId, $userId, $tokenId, $appointmentId, 'create', 'ok', null, ['event_id' => $eventId]);
        } catch (\Throwable $e) {
            $mapRepo->upsert($clinicId, $appointmentId, $tokenId, null, $calendarId, null, $e->getMessage());
            $this->log($clinicId, $userId, $tokenId, $appointmentId, 'create', 'failed', $e->getMessage(), null);
            throw $e;
        }
    }

    private function log(int $clinicId, ?int $userId, ?int $tokenId, ?int $appointmentId, string $action, string $status, ?string $message, ?array $meta): void
    {
        (new GoogleCalendarSyncLogRepository($this->container->get(\PDO::class)))->log(
            $clinicId,
            $userId,
            $tokenId,
            $appointmentId,
            $action,
            $status,
            $message,
            $meta
        );
    }

    private function toIsoLocal(string $dtUtc, string $timezone): string
    {
        $dtUtc = trim($dtUtc);
        if ($dtUtc === '') {
            return (new \DateTimeImmutable('now'))->format(\DateTimeInterface::ATOM);
        }

        try {
            $d = new \DateTimeImmutable($dtUtc, new \DateTimeZone('UTC'));
        } catch (\Throwable $e) {
            return (new \DateTimeImmutable('now'))->format(\DateTimeInterface::ATOM);
        }

        try {
            $d = $d->setTimezone(new \DateTimeZone($timezone));
        } catch (\Throwable $e) {
        }

        return $d->format(\DateTimeInterface::ATOM);
    }
}
