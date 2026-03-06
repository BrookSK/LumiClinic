<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use App\Core\Container\Container;
use App\Repositories\AppointmentRepository;
use App\Repositories\MarketingCampaignMessageRepository;
use App\Repositories\MarketingCampaignRepository;
use App\Repositories\MarketingSegmentRepository;
use App\Repositories\PatientRepository;
use App\Services\Auth\AuthService;
use App\Services\Mail\MailerService;
use App\Services\Queue\QueueService;
use App\Services\Whatsapp\WhatsappTemplateRenderer;
use App\Services\Whatsapp\ZapiClient;
use App\Repositories\WhatsappTemplateRepository;

final class MarketingAutomationService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{patient_ids:list<int>} */
    private function resolveAudience(int $clinicId, ?int $segmentId, int $limit = 500): array
    {
        if ($segmentId === null || $segmentId <= 0) {
            return ['patient_ids' => []];
        }

        $pdo = $this->container->get(\PDO::class);
        $segRepo = new MarketingSegmentRepository($pdo);
        $seg = $segRepo->findById($clinicId, $segmentId);
        if ($seg === null) {
            return ['patient_ids' => []];
        }

        $rulesJson = (string)($seg['rules_json'] ?? '');
        $rules = json_decode($rulesJson, true);
        $rules = is_array($rules) ? $rules : [];

        // MVP rules:
        // - whatsapp_opt_in: 1
        // - has_phone: 1
        // - status: active
        $where = [
            'p.clinic_id = :clinic_id',
            'p.deleted_at IS NULL',
        ];
        $params = ['clinic_id' => $clinicId];

        $status = isset($rules['status']) ? trim((string)$rules['status']) : 'active';
        if ($status !== '') {
            $where[] = 'p.status = :status';
            $params['status'] = $status;
        }

        $waOptIn = isset($rules['whatsapp_opt_in']) ? (int)$rules['whatsapp_opt_in'] : 1;
        if ($waOptIn === 1) {
            $where[] = 'p.whatsapp_opt_in = 1';
        }

        $hasPhone = isset($rules['has_phone']) ? (int)$rules['has_phone'] : 1;
        if ($hasPhone === 1) {
            $where[] = 'p.phone IS NOT NULL';
            $where[] = 'TRIM(p.phone) <> \'\'';
        }

        $sql = "
            SELECT p.id
            FROM patients p
            WHERE " . implode("\n              AND ", $where) . "
            ORDER BY p.id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $ids = [];
        while ($row = $stmt->fetch()) {
            $pid = (int)($row['id'] ?? 0);
            if ($pid > 0) {
                $ids[] = $pid;
            }
        }

        return ['patient_ids' => $ids];
    }

    /** @param array<string,mixed> $rules */
    private function patientMatchesRules(array $patient, array $rules): bool
    {
        $status = isset($rules['status']) ? trim((string)$rules['status']) : 'active';
        if ($status !== '') {
            $pStatus = trim((string)($patient['status'] ?? ''));
            if ($pStatus !== $status) {
                return false;
            }
        }

        $waOptIn = isset($rules['whatsapp_opt_in']) ? (int)$rules['whatsapp_opt_in'] : null;
        if ($waOptIn === 1) {
            if ((int)($patient['whatsapp_opt_in'] ?? 1) !== 1) {
                return false;
            }
        }

        $hasPhone = isset($rules['has_phone']) ? (int)$rules['has_phone'] : null;
        if ($hasPhone === 1) {
            $phone = (string)($patient['phone'] ?? '');
            if (trim($phone) === '') {
                return false;
            }
        }

        $hasEmail = isset($rules['has_email']) ? (int)$rules['has_email'] : null;
        if ($hasEmail === 1) {
            $email = (string)($patient['email'] ?? '');
            if (trim($email) === '') {
                return false;
            }
        }

        return true;
    }

    /** @return array<string,mixed>|null */
    private function loadSegmentRules(int $clinicId, ?int $segmentId): ?array
    {
        if ($segmentId === null || $segmentId <= 0) {
            return null;
        }

        $pdo = $this->container->get(\PDO::class);
        $seg = (new MarketingSegmentRepository($pdo))->findById($clinicId, $segmentId);
        if ($seg === null) {
            return null;
        }

        $rulesJson = (string)($seg['rules_json'] ?? '');
        $rules = json_decode($rulesJson, true);
        return is_array($rules) ? $rules : null;
    }

    private function appBaseUrl(): string
    {
        $config = $this->container->has('config') ? $this->container->get('config') : null;
        $url = is_array($config) && isset($config['app']['base_url']) ? (string)$config['app']['base_url'] : '';
        $url = rtrim(trim($url), '/');
        return $url;
    }

    private function buildClickUrl(string $token): ?string
    {
        $base = $this->appBaseUrl();
        if ($base === '') {
            return null;
        }
        return $base . '/m/click?token=' . rawurlencode($token);
    }

    private function randomToken(int $bytes = 16): string
    {
        try {
            return bin2hex(random_bytes($bytes));
        } catch (\Throwable $e) {
            return bin2hex((string)microtime(true));
        }
    }

    public function runCampaign(int $clinicId, int $campaignId, string $ip): void
    {
        $pdo = $this->container->get(\PDO::class);
        $campRepo = new MarketingCampaignRepository($pdo);
        $msgRepo = new MarketingCampaignMessageRepository($pdo);

        $campaign = $campRepo->findById($clinicId, $campaignId);
        if ($campaign === null) {
            throw new \RuntimeException('Campanha inválida.');
        }

        $targetClickUrl = null;
        if (isset($campaign['click_url']) && $campaign['click_url'] !== null) {
            $targetClickUrl = trim((string)$campaign['click_url']);
            if ($targetClickUrl === '') {
                $targetClickUrl = null;
            }
        }

        $channel = trim((string)($campaign['channel'] ?? ''));
        if ($channel === '') {
            throw new \RuntimeException('Canal inválido.');
        }

        $segmentId = isset($campaign['segment_id']) && $campaign['segment_id'] !== null ? (int)$campaign['segment_id'] : null;
        $aud = $this->resolveAudience($clinicId, $segmentId, 500);
        $patientIds = $aud['patient_ids'];

        $queue = new QueueService($this->container);

        $scheduledFor = isset($campaign['scheduled_for']) ? (string)$campaign['scheduled_for'] : null;
        $scheduledFor = $scheduledFor !== null && trim($scheduledFor) !== '' ? $scheduledFor : null;

        foreach ($patientIds as $patientId) {
            $token = null;
            if ($targetClickUrl !== null) {
                $tmpToken = $this->randomToken(16);
                if ($this->buildClickUrl($tmpToken) !== null) {
                    $token = $tmpToken;
                }
            }
            $clickUrlSnapshot = $targetClickUrl;

            $messageId = $msgRepo->upsertQueued(
                $clinicId,
                $campaignId,
                $patientId,
                $channel,
                $scheduledFor,
                $token,
                $clickUrlSnapshot
            );

            if ($messageId <= 0) {
                continue;
            }

            $queue->enqueue(
                'marketing.send_message',
                ['message_id' => $messageId],
                $clinicId,
                'notifications',
                $scheduledFor,
                10
            );
        }

        $campRepo->markLastRun($clinicId, $campaignId, 'running');
    }

    public function sendMessage(int $clinicId, int $messageId): void
    {
        $pdo = $this->container->get(\PDO::class);

        $msgRepo = new MarketingCampaignMessageRepository($pdo);
        $msg = $msgRepo->findById($clinicId, $messageId);
        if ($msg === null) {
            return;
        }

        $status = (string)($msg['status'] ?? '');
        if (!in_array($status, ['queued', 'failed', 'processing'], true)) {
            return;
        }

        $campaignId = (int)($msg['campaign_id'] ?? 0);
        $patientId = (int)($msg['patient_id'] ?? 0);
        if ($campaignId <= 0 || $patientId <= 0) {
            return;
        }

        $campRepo = new MarketingCampaignRepository($pdo);
        $campaign = $campRepo->findById($clinicId, $campaignId);
        if ($campaign === null) {
            $msgRepo->markFailed($clinicId, $messageId, 'Campanha inválida.');
            return;
        }

        $channel = (string)($campaign['channel'] ?? '');

        $patient = (new PatientRepository($pdo))->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            $msgRepo->markFailed($clinicId, $messageId, 'Paciente inválido.');
            return;
        }

        $segmentId = isset($campaign['segment_id']) && $campaign['segment_id'] !== null ? (int)$campaign['segment_id'] : null;
        $rules = $this->loadSegmentRules($clinicId, $segmentId);
        if ($rules !== null && !$this->patientMatchesRules($patient, $rules)) {
            $msgRepo->markFailed($clinicId, $messageId, 'Paciente fora do segmento.');
            return;
        }

        $clickUrl = null;
        if (isset($msg['click_token']) && $msg['click_token'] !== null && trim((string)$msg['click_token']) !== '') {
            $clickUrl = $this->buildClickUrl((string)$msg['click_token']);
        }

        $vars = [
            'patient_name' => (string)($patient['name'] ?? ''),
            'click_url' => $clickUrl,
        ];

        if ($channel === 'whatsapp') {
            $waOptIn = (int)($patient['whatsapp_opt_in'] ?? 1);
            if ($waOptIn !== 1) {
                $msgRepo->markFailed($clinicId, $messageId, 'Paciente sem opt-in para WhatsApp.');
                return;
            }

            $phone = (string)($patient['phone'] ?? '');
            if (trim($phone) === '') {
                $msgRepo->markFailed($clinicId, $messageId, 'Telefone não informado.');
                return;
            }

            $templateCode = trim((string)($campaign['whatsapp_template_code'] ?? ''));
            if ($templateCode === '') {
                $msgRepo->markFailed($clinicId, $messageId, 'Template WhatsApp não informado.');
                return;
            }

            $tpl = (new WhatsappTemplateRepository($pdo))->findByCode($clinicId, $templateCode);
            if ($tpl === null) {
                $msgRepo->markFailed($clinicId, $messageId, 'Template não encontrado.');
                return;
            }
            if ((string)($tpl['status'] ?? 'active') !== 'active') {
                $msgRepo->markFailed($clinicId, $messageId, 'Template desativado.');
                return;
            }

            $body = (string)($tpl['body'] ?? '');
            $text = (new WhatsappTemplateRenderer())->render($body, $vars);

            $targetClick = trim((string)($campaign['click_url'] ?? ''));
            if ($targetClick !== '' && $clickUrl !== null) {
                $text .= "\n\n" . $clickUrl;
            }

            $payloadSnapshot = [
                'phone' => $phone,
                'message' => $text,
                'template_code' => $templateCode,
                'campaign_id' => $campaignId,
                'message_id' => $messageId,
                'patient_id' => $patientId,
                'vars' => $vars,
            ];

            $msgRepo->markProcessingSnapshot($clinicId, $messageId, $payloadSnapshot, null);

            try {
                $resp = (new ZapiClient($this->container))->sendText($phone, $text);
                $providerId = null;
                if (is_array($resp)) {
                    $providerId = $resp['messageId'] ?? ($resp['id'] ?? null);
                    $providerId = $providerId === null ? null : (string)$providerId;
                }
                $msgRepo->markSent($clinicId, $messageId, is_array($resp) ? $resp : ['raw' => $resp], $providerId);
            } catch (\Throwable $e) {
                $msgRepo->markFailed($clinicId, $messageId, 'Falha ao enviar.');
            }
            return;
        }

        if ($channel === 'email') {
            $email = (string)($patient['email'] ?? '');
            if (trim($email) === '') {
                $msgRepo->markFailed($clinicId, $messageId, 'E-mail não informado.');
                return;
            }

            $subject = trim((string)($campaign['email_subject'] ?? ''));
            if ($subject === '') {
                $subject = (string)($campaign['name'] ?? '');
            }
            $emailBody = (string)($campaign['email_body'] ?? '');
            if (trim($emailBody) === '') {
                $msgRepo->markFailed($clinicId, $messageId, 'Corpo do e-mail não informado.');
                return;
            }

            $html = (new WhatsappTemplateRenderer())->render($emailBody, $vars);

            $payloadSnapshot = [
                'email' => $email,
                'subject' => $subject,
                'html' => $html,
                'campaign_id' => $campaignId,
                'message_id' => $messageId,
                'patient_id' => $patientId,
                'vars' => $vars,
            ];

            $msgRepo->markProcessingSnapshot($clinicId, $messageId, $payloadSnapshot, null);

            try {
                (new MailerService($this->container))->send($email, (string)($patient['name'] ?? $email), $subject, $html);
                $msgRepo->markSent($clinicId, $messageId, ['ok' => true], null);
            } catch (\Throwable $e) {
                $msgRepo->markFailed($clinicId, $messageId, 'Falha ao enviar e-mail.');
            }
            return;
        }

        $msgRepo->markFailed($clinicId, $messageId, 'Canal não suportado.');
    }

    /** @param array<string,mixed> $payload */
    public function processEvent(int $clinicId, string $event, array $payload): void
    {
        $event = trim($event);
        if ($clinicId <= 0 || $event === '') {
            return;
        }

        $pdo = $this->container->get(\PDO::class);
        $campRepo = new MarketingCampaignRepository($pdo);
        $msgRepo = new MarketingCampaignMessageRepository($pdo);
        $queue = new QueueService($this->container);

        $campaigns = $campRepo->listActiveByTriggerEvent($clinicId, $event, 200);
        if ($campaigns === []) {
            return;
        }

        $patientId = null;
        if (isset($payload['patient_id'])) {
            $pid = (int)$payload['patient_id'];
            $patientId = $pid > 0 ? $pid : null;
        }

        if ($patientId === null && isset($payload['appointment_id'])) {
            $appointmentId = (int)$payload['appointment_id'];
            if ($appointmentId > 0) {
                $appt = (new AppointmentRepository($pdo))->findById($clinicId, $appointmentId);
                if ($appt !== null) {
                    $pid = (int)($appt['patient_id'] ?? 0);
                    $patientId = $pid > 0 ? $pid : null;
                }
            }
        }

        if ($patientId === null) {
            return;
        }

        foreach ($campaigns as $camp) {
            $campaignId = (int)($camp['id'] ?? 0);
            if ($campaignId <= 0) {
                continue;
            }

            $targetClickUrl = isset($camp['click_url']) && $camp['click_url'] !== null ? trim((string)$camp['click_url']) : '';
            $targetClickUrl = $targetClickUrl === '' ? null : $targetClickUrl;

            $token = null;
            if ($targetClickUrl !== null) {
                $tmpToken = $this->randomToken(16);
                if ($this->buildClickUrl($tmpToken) !== null) {
                    $token = $tmpToken;
                }
            }

            $delay = isset($camp['trigger_delay_minutes']) && $camp['trigger_delay_minutes'] !== null
                ? (int)$camp['trigger_delay_minutes']
                : 0;
            $delay = max(0, min($delay, 60 * 24 * 30));
            $runAt = $delay > 0
                ? (new \DateTimeImmutable('now'))->modify('+' . $delay . ' minutes')->format('Y-m-d H:i:s')
                : null;

            $messageId = $msgRepo->upsertQueued(
                $clinicId,
                $campaignId,
                $patientId,
                (string)($camp['channel'] ?? 'whatsapp'),
                $runAt,
                $token,
                $targetClickUrl
            );

            if ($messageId <= 0) {
                continue;
            }

            $queue->enqueue('marketing.send_message', ['message_id' => $messageId], $clinicId, 'notifications', $runAt, 10);
        }
    }

    public function click(string $token): ?string
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new MarketingCampaignMessageRepository($pdo);
        $row = $repo->findByClickToken($token);
        if ($row === null) {
            return null;
        }

        $clinicId = (int)($row['clinic_id'] ?? 0);
        $id = (int)($row['id'] ?? 0);
        if ($clinicId <= 0 || $id <= 0) {
            return null;
        }

        $repo->markClicked($clinicId, $id);

        $target = (string)($row['click_url_snapshot'] ?? '');
        $target = trim($target);
        if ($target === '') {
            return null;
        }

        return $target;
    }

    public function updateProviderStatus(int $clinicId, string $providerMessageId, string $status): void
    {
        $providerMessageId = trim($providerMessageId);
        $status = trim($status);
        if ($clinicId <= 0 || $providerMessageId === '' || $status === '') {
            return;
        }

        (new MarketingCampaignMessageRepository($this->container->get(\PDO::class)))->markProviderStatus($clinicId, $providerMessageId, $status);
    }
}
