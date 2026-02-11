<?php

declare(strict_types=1);

namespace App\Repositories;

final class UserRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string, mixed>|null */
    public function findActiveByEmail(string $email): ?array
    {
        $sql = "
            SELECT id, clinic_id, name, email, password_hash, is_super_admin
            FROM users
            WHERE email = :email
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function listActiveByEmail(string $email, int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));

        $sql = "
            SELECT
                u.id, u.clinic_id, u.name, u.email, u.password_hash, u.is_super_admin,
                c.name AS clinic_name
            FROM users u
            LEFT JOIN clinics c ON c.id = u.clinic_id
            WHERE u.email = :email
              AND u.deleted_at IS NULL
            ORDER BY u.id DESC
            LIMIT {$limit}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $userId): ?array
    {
        $sql = "
            SELECT id, clinic_id, name, email, password_hash, is_super_admin
            FROM users
            WHERE id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updatePasswordById(int $userId, string $passwordHash): void
    {
        $sql = "
            UPDATE users
               SET password_hash = :password_hash,
                   updated_at = NOW()
             WHERE id = :id
               AND deleted_at IS NULL
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $userId,
            'password_hash' => $passwordHash,
        ]);
    }

    public function updateSelfProfile(int $userId, string $name, string $email): void
    {
        $sql = "
            UPDATE users
               SET name = :name,
                   email = :email,
                   updated_at = NOW()
             WHERE id = :id
               AND deleted_at IS NULL
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $userId,
            'name' => $name,
            'email' => $email,
        ]);
    }
}
