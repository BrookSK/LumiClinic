<?php

declare(strict_types=1);

namespace App\Services\Queue;

use App\Core\Container\Container;
use App\Repositories\BillingEventRepository;
use App\Repositories\QueueJobRepository;
use App\Services\Bi\BiService;
use App\Services\Billing\BillingEventProcessorService;
use App\Services\Observability\MetricsService;
use App\Services\Observability\AlertEngineService;
use App\Services\Observability\ObservabilityRetentionService;
use App\Services\Portal\PortalNotificationService;
use App\Services\Marketing\MarketingAutomationService;
use App\Services\Google\GoogleCalendarSyncService;
use App\Services\Whatsapp\WhatsappReminderReconcileService;
use App\Services\Whatsapp\WhatsappReminderSendService;
use App\Services\Mail\MailerService;
use App\Repositories\AppointmentRepository;
use App\Repositories\PatientRepository;
use App\Services\Anamnesis\AppointmentAnamnesisLinkService;

final class QueueService
{
    public function __construct(private readonly Container $container) {}

    public function enqueue(
        string $jobType,
        array $payload,
        ?int $clinicId = null,
        string $queue = 'default',
        ?string $runAt = null,
        int $maxAttempts = 10
    ): int {
        $repo = new QueueJobRepository($this->container->get(\PDO::class));
        return $repo->enqueue($clinicId, $jobType, $payload, $queue, $runAt, $maxAttempts);
    }

    public function handle(string $jobType, array $payload, ?int $clinicId): void
    {
        $jobType = trim($jobType);

        if ($jobType === 'noop') {
            return;
        }

        if ($jobType === 'portal.notify_anamnesis_request') {
            if ($clinicId === null) {
                throw new \RuntimeException('clinic_id obrigatório para portal.notify_anamnesis_request.');
            }

            $patientId = (int)($payload['patient_id'] ?? 0);
            $appointmentId = (int)($payload['appointment_id'] ?? 0);
            if ($patientId <= 0 || $appointmentId <= 0) {
                throw new \RuntimeException('Payload inválido para portal.notify_anamnesis_request.');
            }

            $link = (new AppointmentAnamnesisLinkService($this->container))->ensureLinkForAppointment($clinicId, $appointmentId, null);
            $url = (string)($link['url'] ?? '');
            $requestId = (int)($link['request_id'] ?? 0);
            (new PortalNotificationService($this->container))->notifyAnamnesisRequest($clinicId, $patientId, $appointmentId, $url, $requestId);
            return;
        }

        if ($jobType === 'test.noop') {
            return;
        }

        if ($jobType === 'test.throw') {
            throw new \RuntimeException('test.throw');
        }

        if ($jobType === 'portal.notify_appointment_confirmed') {
            if ($clinicId === null) {
                throw new \RuntimeException('clinic_id obrigatório para portal.notify_appointment_confirmed.');
            }

            $patientId = (int)($payload['patient_id'] ?? 0);
            $appointmentId = (int)($payload['appointment_id'] ?? 0);
            if ($patientId <= 0 || $appointmentId <= 0) {
                throw new \RuntimeException('Payload inválido para portal.notify_appointment_confirmed.');
            }

            (new PortalNotificationService($this->container))->notifyAppointmentConfirmed($clinicId, $patientId, $appointmentId);
            return;
        }

        if ($jobType === 'whatsapp.send_reminder') {
            if ($clinicId === null) {
                throw new \RuntimeException('clinic_id obrigatório para whatsapp.send_reminder.');
            }

            $appointmentId = (int)($payload['appointment_id'] ?? 0);
            $templateCode = trim((string)($payload['template_code'] ?? ''));
            $logId = (int)($payload['log_id'] ?? 0);

            if ($appointmentId <= 0 || $templateCode === '' || $logId <= 0) {
                throw new \RuntimeException('Payload inválido para whatsapp.send_reminder.');
            }

            (new WhatsappReminderSendService($this->container))->sendReminder($clinicId, $appointmentId, $templateCode, $logId);
            return;
        }

        if ($jobType === 'mail.send_anamnesis_request') {
            if ($clinicId === null) {
                throw new \RuntimeException('clinic_id obrigatório para mail.send_anamnesis_request.');
            }

            $appointmentId = (int)($payload['appointment_id'] ?? 0);
            if ($appointmentId <= 0) {
                throw new \RuntimeException('Payload inválido para mail.send_anamnesis_request.');
            }

            $pdo = $this->container->get(\PDO::class);
            $appt = (new AppointmentRepository($pdo))->findById($clinicId, $appointmentId);
            if ($appt === null) {
                return;
            }

            $patientId = (int)($appt['patient_id'] ?? 0);
            if ($patientId <= 0) {
                return;
            }

            $patient = (new PatientRepository($pdo))->findById($clinicId, $patientId);
            if ($patient === null) {
                return;
            }

            $email = trim((string)($patient['email'] ?? ''));
            if ($email === '') {
                return;
            }

            $name = trim((string)($patient['name'] ?? ''));

            $link = (new AppointmentAnamnesisLinkService($this->container))->ensureLinkForAppointment($clinicId, $appointmentId, null);
            $url = trim((string)($link['url'] ?? ''));
            if ($url === '') {
                return;
            }

            $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $safeName = htmlspecialchars($name !== '' ? $name : $email, ENT_QUOTES, 'UTF-8');

            $subject = 'Anamnese pré-consulta';
            $html = '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.6;color:#111827">'
                . '<p>Olá, ' . $safeName . '.</p>'
                . '<p>Para agilizar seu atendimento, preencha a <strong>anamnese</strong> antes da consulta:</p>'
                . '<p><a href="' . $safeUrl . '">Preencher anamnese</a></p>'
                . '<p style="color:rgba(17,24,39,0.65);font-size:12px;">Se você não reconhece este agendamento, ignore este e-mail.</p>'
                . '</div>';

            (new MailerService($this->container))->send($email, $name !== '' ? $name : $email, $subject, $html);
            return;
        }

        if ($jobType === 'whatsapp.reminders.reconcile') {
            if ($clinicId === null) {
                throw new \RuntimeException('clinic_id obrigatório para whatsapp.reminders.reconcile.');
            }

            (new WhatsappReminderReconcileService($this->container))->reconcile($clinicId);

            $runAt = (new \DateTimeImmutable('now'))->modify('+10 minutes')->format('Y-m-d H:i:s');
            $this->enqueue('whatsapp.reminders.reconcile', ['seed' => 1], $clinicId, 'notifications', $runAt, 10);
            return;
        }

        if ($jobType === 'marketing.run_campaign') {
            if ($clinicId === null) {
                throw new \RuntimeException('clinic_id obrigatório para marketing.run_campaign.');
            }

            $campaignId = (int)($payload['campaign_id'] ?? 0);
            $ip = (string)($payload['ip'] ?? '');
            if ($campaignId <= 0) {
                throw new \RuntimeException('Payload inválido para marketing.run_campaign.');
            }

            (new MarketingAutomationService($this->container))->runCampaign($clinicId, $campaignId, $ip);
            return;
        }

        if ($jobType === 'marketing.send_message') {
            if ($clinicId === null) {
                throw new \RuntimeException('clinic_id obrigatório para marketing.send_message.');
            }

            $messageId = (int)($payload['message_id'] ?? 0);
            if ($messageId <= 0) {
                throw new \RuntimeException('Payload inválido para marketing.send_message.');
            }

            (new MarketingAutomationService($this->container))->sendMessage($clinicId, $messageId);
            return;
        }

        if ($jobType === 'marketing.process_event') {
            if ($clinicId === null) {
                throw new \RuntimeException('clinic_id obrigatório para marketing.process_event.');
            }

            $event = trim((string)($payload['event'] ?? ''));
            if ($event === '') {
                throw new \RuntimeException('Payload inválido para marketing.process_event.');
            }

            (new MarketingAutomationService($this->container))->processEvent($clinicId, $event, $payload);
            return;
        }

        if ($jobType === 'bi.refresh_executive') {
            if ($clinicId === null) {
                throw new \RuntimeException('clinic_id obrigatório para bi.refresh_executive.');
            }

            $from = trim((string)($payload['from'] ?? ''));
            $to = trim((string)($payload['to'] ?? ''));
            if ($from === '' || $to === '') {
                throw new \RuntimeException('Payload inválido para bi.refresh_executive.');
            }

            $ip = (string)($payload['ip'] ?? '');
            $userAgent = $payload['user_agent'] ?? null;
            $userAgent = $userAgent === null ? null : (string)$userAgent;

            (new BiService($this->container))->refreshExecutiveSnapshot($from, $to, $ip, $userAgent);
            return;
        }

        if ($jobType === 'billing.process_event') {
            $eventId = (int)($payload['billing_event_id'] ?? 0);
            if ($eventId <= 0) {
                throw new \RuntimeException('Payload inválido para billing.process_event.');
            }

            $repo = new BillingEventRepository($this->container->get(\PDO::class));
            $event = $repo->findById($eventId);
            if ($event === null) {
                return;
            }

            if ($event['processed_at'] !== null) {
                return;
            }

            (new BillingEventProcessorService($this->container))->process($event);
            $repo->markProcessed($eventId);
            return;
        }

        if ($jobType === 'gcal.sync_appointment') {
            if ($clinicId === null) {
                throw new \RuntimeException('clinic_id obrigatório para gcal.sync_appointment.');
            }

            $appointmentId = (int)($payload['appointment_id'] ?? 0);
            if ($appointmentId <= 0) {
                throw new \RuntimeException('Payload inválido para gcal.sync_appointment.');
            }

            (new GoogleCalendarSyncService($this->container))->syncAppointment($clinicId, $appointmentId);
            return;
        }

        if ($jobType === 'metrics.daily') {
            if ($clinicId === null) {
                throw new \RuntimeException('clinic_id obrigatório para metrics.daily.');
            }

            $ref = trim((string)($payload['reference_date'] ?? ''));
            if ($ref === '') {
                $ref = (new \DateTimeImmutable('today'))->format('Y-m-d');
            }

            (new MetricsService($this->container))->computeDailyClinicMetrics($clinicId, $ref);
            return;
        }

        if ($jobType === 'alerts.evaluate') {
            $ref = trim((string)($payload['reference_date'] ?? ''));
            if ($ref === '') {
                $ref = (new \DateTimeImmutable('today'))->format('Y-m-d');
            }

            (new AlertEngineService($this->container))->evaluate($ref);
            return;
        }

        if ($jobType === 'observability.purge') {
            (new ObservabilityRetentionService($this->container))->purge();
            return;
        }

        if ($jobType === 'metrics.weekly' || $jobType === 'metrics.monthly') {
            if ($clinicId === null) {
                throw new \RuntimeException('clinic_id obrigatório para ' . $jobType . '.');
            }

            $ref = trim((string)($payload['reference_date'] ?? ''));
            if ($ref === '') {
                $ref = (new \DateTimeImmutable('today'))->format('Y-m-d');
            }

            (new MetricsService($this->container))->computeDailyClinicMetrics($clinicId, $ref);
            return;
        }

        throw new \RuntimeException('Job handler não registrado: ' . $jobType);
    }
}
