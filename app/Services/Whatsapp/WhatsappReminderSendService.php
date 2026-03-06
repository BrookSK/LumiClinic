<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use App\Core\Container\Container;
use App\Repositories\AppointmentRepository;
use App\Repositories\ClinicRepository;
use App\Repositories\ClinicSettingsRepository;
use App\Repositories\WhatsappMessageLogRepository;
use App\Repositories\WhatsappTemplateRepository;
use App\Services\Scheduling\AppointmentConfirmationLinkService;
use App\Services\Anamnesis\AppointmentAnamnesisLinkService;
use App\Services\Whatsapp\EvolutionClient;

final class WhatsappReminderSendService
{
    public function __construct(private readonly Container $container) {}

    public function sendReminder(int $clinicId, int $appointmentId, string $templateCode, int $logId): void
    {
        $pdo = $this->container->get(\PDO::class);

        $logs = new WhatsappMessageLogRepository($pdo);
        $log = $logs->findById($clinicId, $logId);
        if ($log === null) {
            return;
        }

        $logStatus = (string)($log['status'] ?? 'pending');
        if ($logStatus !== 'pending') {
            return;
        }

        $apptRepo = new AppointmentRepository($pdo);
        $ctx = $apptRepo->findReminderContext($clinicId, $appointmentId);
        if ($ctx === null) {
            $logs->markFailed($clinicId, $logId, 'Agendamento inválido.');
            return;
        }

        $status = (string)($ctx['status'] ?? '');
        if (in_array($status, ['cancelled', 'no_show', 'completed'], true)) {
            $logs->markSkipped($clinicId, $logId, 'Agendamento não elegível (status).');
            return;
        }

        if ($templateCode === 'confirm_request' && $status === 'in_progress') {
            $logs->markSkipped($clinicId, $logId, 'Agendamento já iniciou.');
            return;
        }

        if ($templateCode === 'confirm_request' && $status === 'confirmed') {
            $logs->markSkipped($clinicId, $logId, 'Agendamento já confirmado.');
            return;
        }

        if ($templateCode === 'anamnesis_request' && in_array($status, ['cancelled', 'no_show', 'completed'], true)) {
            $logs->markSkipped($clinicId, $logId, 'Agendamento não elegível (status).');
            return;
        }

        $waOptIn = (int)($ctx['whatsapp_opt_in'] ?? 1);
        if ($waOptIn !== 1) {
            $logs->markSkipped($clinicId, $logId, 'Paciente sem opt-in para WhatsApp.');
            return;
        }

        $phone = (string)($ctx['patient_phone'] ?? '');
        $patientName = (string)($ctx['patient_name'] ?? '');
        $startAt = (string)($ctx['start_at'] ?? '');

        if (trim($phone) === '') {
            $logs->markSkipped($clinicId, $logId, 'Telefone não informado.');
            return;
        }

        $tplRepo = new WhatsappTemplateRepository($pdo);
        $tpl = $tplRepo->findByCode($clinicId, $templateCode);
        if ($tpl === null) {
            $logs->markFailed($clinicId, $logId, 'Template não encontrado.');
            return;
        }

        if ((string)($tpl['status'] ?? 'active') !== 'active') {
            $logs->markSkipped($clinicId, $logId, 'Template desativado.');
            return;
        }

        $clinic = (new ClinicRepository($pdo))->findById($clinicId);
        $clinicName = $clinic !== null ? (string)($clinic['name'] ?? '') : '';

        $tz = 'America/Sao_Paulo';
        $settings = (new ClinicSettingsRepository($pdo))->findByClinicId($clinicId);
        if ($settings !== null && isset($settings['timezone']) && trim((string)$settings['timezone']) !== '') {
            $tz = (string)$settings['timezone'];
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startAt);
        if ($dt === false) {
            $logs->markFailed($clinicId, $logId, 'Data/hora inválida.');
            return;
        }

        try {
            $dtLocal = $dt->setTimezone(new \DateTimeZone($tz));
        } catch (\Throwable $e) {
            $dtLocal = $dt;
        }

        $vars = [
            'patient_name' => $patientName,
            'clinic_name' => $clinicName,
            'date' => $dtLocal->format('d/m/Y'),
            'time' => $dtLocal->format('H:i'),
        ];

        if ($templateCode === 'confirm_request') {
            try {
                $link = (new AppointmentConfirmationLinkService($this->container))->createLink($clinicId, $appointmentId);
                $vars['confirm_url'] = (string)$link['url'];
            } catch (\Throwable $e) {
                $logs->markFailed($clinicId, $logId, 'Falha ao gerar link de confirmação.');
                return;
            }
        }

        if ($templateCode === 'anamnesis_request') {
            try {
                $link = (new AppointmentAnamnesisLinkService($this->container))->ensureLinkForAppointment($clinicId, $appointmentId, null);
                $vars['anamnesis_url'] = (string)($link['url'] ?? '');
            } catch (\Throwable $e) {
                $logs->markFailed($clinicId, $logId, 'Falha ao gerar link de anamnese.');
                return;
            }
        }

        $body = (string)($tpl['body'] ?? '');
        $message = (new WhatsappTemplateRenderer())->render($body, $vars);

        $payloadSnapshot = [
            'phone' => $phone,
            'message' => $message,
            'template_code' => $templateCode,
            'appointment_id' => $appointmentId,
            'vars' => $vars,
        ];

        $logs->markSendingSnapshot($clinicId, $logId, $payloadSnapshot, null);

        $resp = (new EvolutionClient($this->container))->sendText($phone, $message);

        $providerId = null;
        if (is_array($resp)) {
            if (isset($resp['key']) && is_array($resp['key'])) {
                $providerId = $resp['key']['id'] ?? null;
            }
            if ($providerId === null) {
                $providerId = $resp['messageId'] ?? ($resp['id'] ?? null);
            }
            $providerId = $providerId === null ? null : (string)$providerId;
        }

        $logs->markSent($clinicId, $logId, is_array($resp) ? $resp : ['raw' => $resp], $providerId);
    }
}
