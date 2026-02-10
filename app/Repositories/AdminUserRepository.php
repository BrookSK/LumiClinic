<?php

declare(strict_types=1);

namespace App\Repositories;

final class AdminUserRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function countActiveByClinic(int $clinicId): int
    {
        $stmt = $this->pdo->prepare("\n            SELECT COUNT(*) AS c
            FROM users
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
        ");
        $stmt->execute(['clinic_id' => $clinicId]);
        $row = $stmt->fetch();
        return (int)($row['c'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public function listByClinic(int $clinicId, int $limit = 200, int $offset = 0): array
    {
        $offset = max(0, $offset);
        $sql = "
            SELECT id, name, email, status, created_at
            FROM users
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
            OFFSET " . (int)$offset . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    public function create(int $clinicId, string $name, string $email, string $passwordHash): int
    {
        $sql = "
            INSERT INTO users (clinic_id, name, email, password_hash, status, created_at)
            VALUES (:clinic_id, :name, :email, :password_hash, 'active', NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $userId): ?array
    {
        $sql = "
            SELECT id, clinic_id, name, email, status, created_at,
                   (
                       SELECT ur.role_id
                       FROM user_roles ur
                       WHERE ur.clinic_id = users.clinic_id
                         AND ur.user_id = users.id
                       ORDER BY ur.id DESC
                       LIMIT 1
                   ) AS role_id
            FROM users
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $userId, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updateProfile(int $clinicId, int $userId, string $name, string $email, string $status): void
    {
        $sql = "
            UPDATE users
               SET name = :name,
                   email = :email,
                   status = :status,
                   updated_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $userId,
            'clinic_id' => $clinicId,
            'name' => $name,
            'email' => $email,
            'status' => $status,
        ]);
    }

    public function updatePassword(int $clinicId, int $userId, string $passwordHash): void
    {
        $sql = "
            UPDATE users
               SET password_hash = :password_hash,
                   updated_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $userId,
            'clinic_id' => $clinicId,
            'password_hash' => $passwordHash,
        ]);
    }

    public function disable(int $clinicId, int $userId): void
    {
        $sql = "
            UPDATE users
               SET status = 'disabled',
                   deleted_at = NOW(),
                   updated_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $userId, 'clinic_id' => $clinicId]);
    }
}
