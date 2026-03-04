<?php

declare(strict_types=1);

namespace App\Repositories;

final class WhatsappMessageLogRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, appointment_id, template_code, scheduled_for,
                   status, sent_at, provider_message_id,
                   payload_json, response_json, error_message,
                   created_at, updated_at
            FROM whatsapp_message_logs
            WHERE id = :id
              AND clinic_id = :clinic_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findByClinicAppointmentAndTemplate(int $clinicId, int $appointmentId, string $templateCode): ?array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, appointment_id, template_code, scheduled_for,
                   status, sent_at, provider_message_id,
                   payload_json, response_json, error_message,
                   created_at, updated_at
            FROM whatsapp_message_logs
            WHERE clinic_id = :clinic_id
              AND appointment_id = :appointment_id
              AND template_code = :template_code
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'appointment_id' => $appointmentId,
            'template_code' => $templateCode,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createOrUpdatePending(
        int $clinicId,
        int $appointmentId,
        ?int $patientId,
        string $templateCode,
        string $scheduledFor
    ): int {
        $sql = "
            INSERT INTO whatsapp_message_logs (
                clinic_id, patient_id, appointment_id,
                template_code, scheduled_for,
                status,
                created_at
            ) VALUES (
                :clinic_id, :patient_id, :appointment_id,
                :template_code, :scheduled_for,
                'pending',
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                patient_id = VALUES(patient_id),
                scheduled_for = VALUES(scheduled_for),
                status = IF(status IN ('sent','skipped'), status, 'pending'),
                error_message = IF(status IN ('sent','skipped'), error_message, NULL),
                updated_at = NOW()
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'appointment_id' => $appointmentId,
            'template_code' => $templateCode,
            'scheduled_for' => $scheduledFor,
        ]);

        $existing = $this->findByClinicAppointmentAndTemplate($clinicId, $appointmentId, $templateCode);
        if ($existing === null) {
            return (int)$this->pdo->lastInsertId();
        }
        return (int)$existing['id'];
    }

    public function markSkipped(int $clinicId, int $id, string $reason): void
    {
        $sql = "
            UPDATE whatsapp_message_logs
            SET status = 'skipped',
                error_message = :reason,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId, 'reason' => $reason]);
    }

    public function markSendingSnapshot(
        int $clinicId,
        int $id,
        array $payload,
        ?string $providerMessageId = null
    ): void {
        $sql = "
            UPDATE whatsapp_message_logs
            SET status = 'processing',
                provider_message_id = :provider_message_id,
                payload_json = :payload_json,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND status IN ('pending','failed','processing')
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
            'provider_message_id' => $providerMessageId,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function markSent(int $clinicId, int $id, array $response, ?string $providerMessageId = null): void
    {
        $sql = "
            UPDATE whatsapp_message_logs
            SET status = 'sent',
                sent_at = NOW(),
                provider_message_id = :provider_message_id,
                response_json = :response_json,
                error_message = NULL,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
            'provider_message_id' => $providerMessageId,
            'response_json' => json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function markFailed(int $clinicId, int $id, string $errorMessage, ?array $response = null): void
    {
        $sql = "
            UPDATE whatsapp_message_logs
            SET status = 'failed',
                error_message = :error_message,
                response_json = :response_json,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
            'error_message' => $errorMessage,
            'response_json' => $response === null ? null : json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function resetToPendingForRetry(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE whatsapp_message_logs
            SET status = 'pending',
                error_message = NULL,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND status IN ('failed','pending')
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
    }

    public function cancelPendingForAppointment(int $clinicId, int $appointmentId): void
    {
        $sql = "
            UPDATE whatsapp_message_logs
            SET status = 'cancelled',
                updated_at = NOW()
            WHERE clinic_id = :clinic_id
              AND appointment_id = :appointment_id
              AND status IN ('pending','failed','processing')
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'appointment_id' => $appointmentId]);
    }
}
