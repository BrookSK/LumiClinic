<?php

declare(strict_types=1);

namespace App\Services\Scheduling;

use App\Core\Container\Container;
use App\Repositories\AppointmentConfirmationTokenRepository;
use App\Repositories\AppointmentRepository;

final class AppointmentConfirmationLinkService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{url:string,token:string,token_id:int} */
    public function createLink(int $clinicId, int $appointmentId): array
    {
        $pdo = $this->container->get(\PDO::class);

        $apptRepo = new AppointmentRepository($pdo);
        $ctx = $apptRepo->findById($clinicId, $appointmentId);
        if ($ctx === null) {
            throw new \RuntimeException('Agendamento inválido.');
        }

        $startAt = (string)($ctx['start_at'] ?? '');
        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startAt);
        if ($start === false) {
            $start = new \DateTimeImmutable('now');
        }

        $expiresAt = $start->modify('+2 days')->format('Y-m-d H:i:s');

        $repo = new AppointmentConfirmationTokenRepository($pdo);
        $rawToken = bin2hex(random_bytes(24));
        $tokenHash = hash('sha256', $rawToken);

        $tokenId = $repo->create($clinicId, $appointmentId, 'confirm', $tokenHash, $expiresAt);

        return [
            'url' => $this->buildUrl($rawToken),
            'token' => $rawToken,
            'token_id' => $tokenId,
        ];
    }

    public function buildUrl(string $token): string
    {
        $token = trim($token);
        $cfg = $this->container->has('config') ? $this->container->get('config') : [];
        $baseUrl = is_array($cfg) && isset($cfg['app']) && is_array($cfg['app'])
            ? (string)($cfg['app']['base_url'] ?? '')
            : '';
        $baseUrl = rtrim($baseUrl !== '' ? $baseUrl : (string)(getenv('APP_BASE_URL') ?: ''), '/');

        if ($baseUrl === '' && isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . (string)$_SERVER['HTTP_HOST'];
        }

        return $baseUrl . '/a/confirm?token=' . urlencode($token);
    }
}
