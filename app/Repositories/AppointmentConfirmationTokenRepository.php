<?php

declare(strict_types=1);

namespace App\Repositories;

final class AppointmentConfirmationTokenRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed>|null */
    public function findValidByTokenHash(string $tokenHash): ?array
    {
        $sql = "
            SELECT id, clinic_id, appointment_id, kind, token_hash, expires_at, used_at, used_action, created_at
            FROM appointment_confirmation_tokens
            WHERE token_hash = :token_hash
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
    public function findLatestValidByAppointment(int $clinicId, int $appointmentId, string $kind = 'confirm'): ?array
    {
        $sql = "
            SELECT id, clinic_id, appointment_id, kind, token_hash, expires_at, used_at, used_action, created_at
            FROM appointment_confirmation_tokens
            WHERE clinic_id = :clinic_id
              AND appointment_id = :appointment_id
              AND kind = :kind
              AND used_at IS NULL
              AND expires_at > NOW()
            ORDER BY id DESC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'appointment_id' => $appointmentId, 'kind' => $kind]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(int $clinicId, int $appointmentId, string $kind, string $tokenHash, string $expiresAt): int
    {
        $sql = "
            INSERT INTO appointment_confirmation_tokens (clinic_id, appointment_id, kind, token_hash, expires_at, created_at)
            VALUES (:clinic_id, :appointment_id, :kind, :token_hash, :expires_at, NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'appointment_id' => $appointmentId,
            'kind' => $kind,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function markUsed(int $id, string $action): void
    {
        $sql = "
            UPDATE appointment_confirmation_tokens
            SET used_at = NOW(),
                used_action = :used_action
            WHERE id = :id
              AND used_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'used_action' => $action]);
    }
}
