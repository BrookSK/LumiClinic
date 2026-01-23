<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientApiTokenRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(int $clinicId, int $patientUserId, int $patientId, string $tokenHash, ?string $name, ?string $scopesJson, ?string $expiresAt): int
    {
        $sql = "
            INSERT INTO patient_api_tokens (
                clinic_id, patient_user_id, patient_id,
                token_hash, name, scopes_json,
                expires_at, last_used_at,
                created_at, revoked_at
            ) VALUES (
                :clinic_id, :patient_user_id, :patient_id,
                :token_hash, :name, :scopes_json,
                :expires_at, NULL,
                NOW(), NULL
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_user_id' => $patientUserId,
            'patient_id' => $patientId,
            'token_hash' => $tokenHash,
            'name' => ($name === '' ? null : $name),
            'scopes_json' => ($scopesJson === '' ? null : $scopesJson),
            'expires_at' => ($expiresAt === '' ? null : $expiresAt),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function listByPatientUser(int $clinicId, int $patientUserId, int $limit = 50): array
    {
        $sql = "
            SELECT id, clinic_id, patient_user_id, patient_id, name, expires_at, last_used_at, created_at, revoked_at
            FROM patient_api_tokens
            WHERE clinic_id = :clinic_id
              AND patient_user_id = :patient_user_id
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_user_id' => $patientUserId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findValidByTokenHash(string $tokenHash): ?array
    {
        $sql = "
            SELECT id, clinic_id, patient_user_id, patient_id, token_hash, expires_at, revoked_at
            FROM patient_api_tokens
            WHERE token_hash = :token_hash
              AND revoked_at IS NULL
              AND (expires_at IS NULL OR expires_at >= NOW())
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function touchLastUsedAt(int $id): void
    {
        $sql = "
            UPDATE patient_api_tokens
            SET last_used_at = NOW()
            WHERE id = :id
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    public function revoke(int $clinicId, int $patientUserId, int $id): void
    {
        $sql = "
            UPDATE patient_api_tokens
            SET revoked_at = NOW()
            WHERE clinic_id = :clinic_id
              AND patient_user_id = :patient_user_id
              AND id = :id
              AND revoked_at IS NULL
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_user_id' => $patientUserId, 'id' => $id]);
    }
}
