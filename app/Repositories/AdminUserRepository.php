<?php

declare(strict_types=1);

namespace App\Repositories;

final class AdminUserRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, name, email, status, created_at
            FROM users
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
            ORDER BY id DESC
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
}
