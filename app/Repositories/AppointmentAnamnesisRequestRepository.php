<?php

declare(strict_types=1);

namespace App\Repositories;

final class AppointmentAnamnesisRequestRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed>|null */
    public function findValidByTokenHash(string $tokenHash): ?array
    {
        $sql = "
            SELECT id, clinic_id, appointment_id, patient_id, template_id,
                   token_hash, token_encrypted, expires_at, used_at, used_action,
                   response_id,
                   created_by_user_id, created_at
            FROM appointment_anamnesis_requests
            WHERE token_hash = :token_hash
              AND deleted_at IS NULL
              AND used_at IS NULL
              AND expires_at > NOW()
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findLatestValidByAppointment(int $clinicId, int $appointmentId): ?array
    {
        $sql = "
            SELECT id, clinic_id, appointment_id, patient_id, template_id,
                   token_hash, token_encrypted, expires_at, used_at, used_action,
                   response_id,
                   created_by_user_id, created_at
            FROM appointment_anamnesis_requests
            WHERE clinic_id = :clinic_id
              AND appointment_id = :appointment_id
              AND deleted_at IS NULL
              AND used_at IS NULL
              AND expires_at > NOW()
            ORDER BY id DESC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'appointment_id' => $appointmentId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(
        int $clinicId,
        int $appointmentId,
        int $patientId,
        int $templateId,
        string $tokenHash,
        ?string $tokenEncrypted,
        string $expiresAt,
        ?int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO appointment_anamnesis_requests (
                clinic_id, appointment_id, patient_id, template_id,
                token_hash, token_encrypted, expires_at,
                used_at, used_action,
                response_id,
                created_by_user_id,
                created_at
            ) VALUES (
                :clinic_id, :appointment_id, :patient_id, :template_id,
                :token_hash, :token_encrypted, :expires_at,
                NULL, NULL,
                NULL,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'appointment_id' => $appointmentId,
            'patient_id' => $patientId,
            'template_id' => $templateId,
            'token_hash' => $tokenHash,
            'token_encrypted' => ($tokenEncrypted !== null && trim($tokenEncrypted) !== '' ? $tokenEncrypted : null),
            'expires_at' => $expiresAt,
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function markUsed(int $clinicId, int $id, string $action, ?int $responseId): void
    {
        $sql = "
            UPDATE appointment_anamnesis_requests
            SET used_at = NOW(),
                used_action = :used_action,
                response_id = :response_id
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND used_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
            'used_action' => $action,
            'response_id' => $responseId,
        ]);
    }
}
