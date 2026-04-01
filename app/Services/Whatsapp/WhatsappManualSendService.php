<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\ClinicRepository;
use App\Repositories\PatientRepository;
use App\Repositories\WhatsappMessageLogRepository;
use App\Repositories\WhatsappTemplateRepository;
use App\Services\Auth\AuthService;

/**
 * Envio manual de WhatsApp para pacientes (aniversariantes, follow-up, etc.)
 * Variáveis disponíveis no template: {patient_name}, {clinic_name}
 */
final class WhatsappManualSendService
{
    public function __construct(private readonly Container $container) {}

    public function send(int $patientId, string $templateCode, string $ip, ?string $userAgent = null): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        // Buscar paciente
        $patient = (new PatientRepository($pdo))->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $phone = trim((string)($patient['phone'] ?? ''));
        if ($phone === '') {
            throw new \RuntimeException('Paciente sem telefone cadastrado.');
        }

        $waOptIn = (int)($patient['whatsapp_opt_in'] ?? 0);
        if ($waOptIn !== 1) {
            throw new \RuntimeException('Paciente sem opt-in para WhatsApp.');
        }

        // Buscar template
        $tplRepo = new WhatsappTemplateRepository($pdo);
        $tpl = $tplRepo->findByCode($clinicId, $templateCode);
        if ($tpl === null) {
            throw new \RuntimeException('Template "' . $templateCode . '" não encontrado.');
        }
        if ((string)($tpl['status'] ?? 'active') !== 'active') {
            throw new \RuntimeException('Template desativado.');
        }

        // Buscar nome da clínica
        $clinic = (new ClinicRepository($pdo))->findById($clinicId);
        $clinicName = $clinic !== null ? (string)($clinic['name'] ?? '') : '';

        // Renderizar mensagem
        $vars = [
            'patient_name' => (string)($patient['name'] ?? ''),
            'clinic_name'  => $clinicName,
        ];
        $message = (new WhatsappTemplateRenderer())->render((string)($tpl['body'] ?? ''), $vars);

        // Registrar log antes de enviar
        $logRepo = new WhatsappMessageLogRepository($pdo);
        $logSql = "
            INSERT INTO whatsapp_message_logs (
                clinic_id, patient_id, appointment_id,
                template_code, scheduled_for, status, created_at
            ) VALUES (
                :clinic_id, :patient_id, NULL,
                :template_code, NOW(), 'pending', NOW()
            )
        ";
        $stmt = $pdo->prepare($logSql);
        $stmt->execute([
            'clinic_id'     => $clinicId,
            'patient_id'    => $patientId,
            'template_code' => $templateCode,
        ]);
        $logId = (int)$pdo->lastInsertId();

        // Enviar
        $payload = ['phone' => $phone, 'message' => $message, 'template_code' => $templateCode, 'vars' => $vars];
        $logRepo->markSendingSnapshot($clinicId, $logId, $payload, null);

        try {
            $resp = (new EvolutionClient($this->container))->sendText($phone, $message);
        } catch (\Throwable $e) {
            $logRepo->markFailed($clinicId, $logId, $e->getMessage());
            throw new \RuntimeException('Falha ao enviar: ' . $e->getMessage());
        }

        $providerId = null;
        if (is_array($resp)) {
            $providerId = $resp['key']['id'] ?? $resp['messageId'] ?? $resp['id'] ?? null;
            $providerId = $providerId === null ? null : (string)$providerId;
        }
        $logRepo->markSent($clinicId, $logId, is_array($resp) ? $resp : [], $providerId);

        // Auditoria
        (new AuditLogRepository($pdo))->log(
            $actorId, $clinicId,
            'whatsapp.manual_send',
            ['patient_id' => $patientId, 'template_code' => $templateCode, 'log_id' => $logId],
            $ip, null, 'patient', $patientId, $userAgent
        );

        return ['ok' => true, 'log_id' => $logId];
    }
}
