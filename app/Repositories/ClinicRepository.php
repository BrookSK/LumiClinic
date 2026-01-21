<?php

declare(strict_types=1);

namespace App\Repositories;

final class ClinicRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId): ?array
    {
        $sql = "
            SELECT id, name, status, created_at, updated_at
            FROM clinics
            WHERE id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updateName(int $clinicId, string $name): void
    {
        $sql = "
            UPDATE clinics
               SET name = :name,
                   updated_at = NOW()
             WHERE id = :id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $clinicId, 'name' => $name]);
    }
}
