<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientUserRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed>|null */
    public function findActiveByEmail(int $clinicId, string $email): ?array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, email, password_hash, two_factor_enabled, status
            FROM patient_users
            WHERE clinic_id = :clinic_id
              AND email = :email
              AND deleted_at IS NULL
              AND status = 'active'
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'email' => $email]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findByPatientId(int $clinicId, int $patientId): ?array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, email, two_factor_enabled, status, last_login_at, last_login_ip
            FROM patient_users
            WHERE clinic_id = :clinic_id
              AND patient_id = :patient_id
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findByPatientIdForUpdate(int $clinicId, int $patientId): ?array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, email, password_hash, two_factor_enabled, status
            FROM patient_users
            WHERE clinic_id = :clinic_id
              AND patient_id = :patient_id
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT 1
            FOR UPDATE
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(int $clinicId, int $patientId, string $email, string $passwordHash): int
    {
        $sql = "
            INSERT INTO patient_users (
                clinic_id, patient_id,
                email, password_hash,
                two_factor_enabled, two_factor_secret,
                status,
                created_at
            ) VALUES (
                :clinic_id, :patient_id,
                :email, :password_hash,
                0, NULL,
                'active',
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateEmail(int $clinicId, int $id, string $email): void
    {
        $sql = "
            UPDATE patient_users
            SET email = :email, updated_at = NOW()
            WHERE clinic_id = :clinic_id
              AND id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id, 'email' => $email]);
    }

    /** @return list<array<string,mixed>> */
    public function listActiveByEmail(string $email, int $limit = 5): array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, email, password_hash, two_factor_enabled, status
            FROM patient_users
            WHERE email = :email
              AND deleted_at IS NULL
              AND status = 'active'
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, email, password_hash, two_factor_enabled, status
            FROM patient_users
            WHERE clinic_id = :clinic_id
              AND id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function touchLogin(int $clinicId, int $id, string $ip): void
    {
        $sql = "
            UPDATE patient_users
            SET last_login_at = NOW(), last_login_ip = :ip
            WHERE clinic_id = :clinic_id
              AND id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id, 'ip' => $ip]);
    }

    public function updatePassword(int $clinicId, int $id, string $passwordHash): void
    {
        $sql = "
            UPDATE patient_users
            SET password_hash = :password_hash, updated_at = NOW()
            WHERE clinic_id = :clinic_id
              AND id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id, 'password_hash' => $passwordHash]);
    }

    public function anonymizeByPatientId(int $clinicId, int $patientId): void
    {
        $sql = "
            UPDATE patient_users
            SET
                email = CONCAT('anon+', id, '@invalid.local'),
                status = 'inactive',
                updated_at = NOW(),
                deleted_at = NOW()
            WHERE clinic_id = :clinic_id
              AND patient_id = :patient_id
              AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);
    }
}
