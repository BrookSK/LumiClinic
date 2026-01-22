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
            SELECT id, clinic_id, email, password_hash, is_super_admin
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
}
