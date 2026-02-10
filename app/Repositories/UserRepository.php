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
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
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
}
