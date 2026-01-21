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
            SELECT id, name, tenant_key, status, created_at, updated_at
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

    public function updateTenantKey(int $clinicId, ?string $tenantKey): void
    {
        $sql = "
            UPDATE clinics
               SET tenant_key = :tenant_key,
                   updated_at = NOW()
             WHERE id = :id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $clinicId,
            'tenant_key' => $tenantKey,
        ]);
    }

    /** @return array<string, mixed>|null */
    public function findByTenantKey(string $tenantKey): ?array
    {
        $sql = "
            SELECT id, name, tenant_key, status
            FROM clinics
            WHERE tenant_key = :tenant_key
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tenant_key' => $tenantKey]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
