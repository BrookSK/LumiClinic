<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientPasswordResetRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(int $clinicId, int $patientUserId, string $tokenHash, string $expiresAt, ?string $ip): int
    {
        $sql = "
            INSERT INTO patient_password_resets (
                clinic_id, patient_user_id,
                token_hash, expires_at,
                used_at,
                created_ip, created_at
            ) VALUES (
                :clinic_id, :patient_user_id,
                :token_hash, :expires_at,
                NULL,
                :created_ip, NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_user_id' => $patientUserId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'created_ip' => ($ip === '' ? null : $ip),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public function findValidByTokenHash(string $tokenHash): ?array
    {
        $sql = "
            SELECT id, clinic_id, patient_user_id, token_hash, expires_at, used_at
            FROM patient_password_resets
            WHERE token_hash = :token_hash
              AND used_at IS NULL
              AND expires_at >= NOW()
            ORDER BY id DESC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function markUsed(int $id): void
    {
        $sql = "
            UPDATE patient_password_resets
            SET used_at = NOW()
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }
}
