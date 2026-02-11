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
            SELECT id, name, tenant_key,
                   contact_email, contact_phone, contact_whatsapp, contact_address,
                   contact_website, contact_instagram, contact_facebook,
                   status, created_at, updated_at
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

    /** @param array<string, string|null> $fields */
    public function updateContactFields(int $clinicId, array $fields): void
    {
        $sql = "
            UPDATE clinics
               SET contact_email = :contact_email,
                   contact_phone = :contact_phone,
                   contact_whatsapp = :contact_whatsapp,
                   contact_address = :contact_address,
                   contact_website = :contact_website,
                   contact_instagram = :contact_instagram,
                   contact_facebook = :contact_facebook,
                   updated_at = NOW()
             WHERE id = :id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $clinicId,
            'contact_email' => $fields['contact_email'] ?? null,
            'contact_phone' => $fields['contact_phone'] ?? null,
            'contact_whatsapp' => $fields['contact_whatsapp'] ?? null,
            'contact_address' => $fields['contact_address'] ?? null,
            'contact_website' => $fields['contact_website'] ?? null,
            'contact_instagram' => $fields['contact_instagram'] ?? null,
            'contact_facebook' => $fields['contact_facebook'] ?? null,
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
