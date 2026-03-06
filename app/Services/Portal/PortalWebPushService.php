<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\PatientWebpushSubscriptionRepository;
use App\Services\System\SystemSettingsService;

final class PortalWebPushService
{
    public function __construct(private readonly Container $container) {}

    /**
     * @return array{public_key:string,subject:string}
     */
    public function config(): array
    {
        $cfg = $this->container->has('config') ? $this->container->get('config') : [];
        $webpush = is_array($cfg) && isset($cfg['webpush']) && is_array($cfg['webpush']) ? $cfg['webpush'] : [];

        $sys = new SystemSettingsService($this->container);
        $db = $sys->getWebPushSettings();

        $publicKey = trim((string)($webpush['public_key'] ?? ''));
        if ($publicKey === '') {
            $publicKey = trim((string)($db['webpush_public_key'] ?? ''));
        }

        $subject = trim((string)($webpush['subject'] ?? ''));
        if ($subject === '') {
            $subject = trim((string)($db['webpush_subject'] ?? ''));
        }

        return [
            'public_key' => $publicKey,
            'subject' => $subject,
        ];
    }

    public function sendTest(int $clinicId, int $patientId, string $title, string $body): void
    {
        $this->sendToPatient($clinicId, $patientId, [
            'title' => $title,
            'body' => $body,
            'url' => '/portal/notificacoes',
        ]);
    }

    /**
     * @param array{title:string,body:string,url?:string} $payload
     */
    public function sendToPatient(int $clinicId, int $patientId, array $payload): void
    {
        $pdo = $this->container->get(\PDO::class);
        $subsRepo = new PatientWebpushSubscriptionRepository($pdo);
        $subs = $subsRepo->listActiveByPatient($clinicId, $patientId, 20);

        if ($subs === []) {
            throw new \RuntimeException('Nenhuma inscrição de push ativa para este paciente.');
        }

        // Real WebPush requires payload encryption + VAPID.
        // We rely on minishlink/web-push if available.
        if (!class_exists('Minishlink\\WebPush\\WebPush')) {
            throw new \RuntimeException('Envio de push não configurado: instale a dependência minishlink/web-push.');
        }

        $cfg = $this->container->has('config') ? $this->container->get('config') : [];
        $webpush = is_array($cfg) && isset($cfg['webpush']) && is_array($cfg['webpush']) ? $cfg['webpush'] : [];

        $sys = new SystemSettingsService($this->container);
        $db = $sys->getWebPushSettings();

        $publicKey = trim((string)($webpush['public_key'] ?? ''));
        $privateKey = trim((string)($webpush['private_key'] ?? ''));
        $subject = trim((string)($webpush['subject'] ?? ''));

        if ($publicKey === '') {
            $publicKey = trim((string)($db['webpush_public_key'] ?? ''));
        }
        if ($privateKey === '') {
            $privateKey = trim((string)($db['webpush_private_key'] ?? ''));
        }
        if ($subject === '') {
            $subject = trim((string)($db['webpush_subject'] ?? ''));
        }

        if ($subject === '') {
            $subject = 'mailto:admin@example.com';
        }

        if (trim($publicKey) === '' || trim($privateKey) === '') {
            throw new \RuntimeException('Chaves VAPID ausentes (webpush.public_key / webpush.private_key).');
        }

        $auth = [
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ];

        $webPush = new \Minishlink\WebPush\WebPush($auth);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        foreach ($subs as $s) {
            $subscription = \Minishlink\WebPush\Subscription::create([
                'endpoint' => (string)$s['endpoint'],
                'keys' => [
                    'p256dh' => (string)$s['p256dh'],
                    'auth' => (string)$s['auth'],
                ],
            ]);

            $webPush->queueNotification($subscription, $json ?: '{}');
        }

        foreach ($webPush->flush() as $report) {
            // If a subscription is gone, consider it invalid (cleanup is optional MVP).
            // We avoid deleting automatically here to keep behavior conservative.
            // $endpoint = $report->getRequest()->getUri()->__toString();
        }
    }
}
