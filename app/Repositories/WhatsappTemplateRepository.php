<?php

declare(strict_types=1);

namespace App\Repositories;

final class WhatsappTemplateRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, clinic_id, code, name, body, status, created_at, updated_at
            FROM whatsapp_templates
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
            ORDER BY code ASC, id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, code, name, body, status, created_at, updated_at
            FROM whatsapp_templates
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findByCode(int $clinicId, string $code): ?array
    {
        $sql = "
            SELECT id, clinic_id, code, name, body, status, created_at, updated_at
            FROM whatsapp_templates
            WHERE clinic_id = :clinic_id
              AND code = :code
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'code' => $code]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(int $clinicId, string $code, string $name, string $body): int
    {
        $sql = "
            INSERT INTO whatsapp_templates (clinic_id, code, name, body, status, created_at)
            VALUES (:clinic_id, :code, :name, :body, 'active', NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'code' => $code,
            'name' => $name,
            'body' => $body,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $clinicId, int $id, string $code, string $name, string $body, string $status): void
    {
        $sql = "
            UPDATE whatsapp_templates
            SET code = :code,
                name = :name,
                body = :body,
                status = :status,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
            'code' => $code,
            'name' => $name,
            'body' => $body,
            'status' => $status,
        ]);
    }
}
