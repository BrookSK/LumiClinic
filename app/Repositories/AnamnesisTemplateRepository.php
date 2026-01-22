<?php

declare(strict_types=1);

namespace App\Repositories;

final class AnamnesisTemplateRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listActiveByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, clinic_id, name, status, created_at, updated_at
            FROM anamnesis_templates
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
            ORDER BY name ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $templateId): ?array
    {
        $sql = "
            SELECT id, clinic_id, name, status, created_at, updated_at
            FROM anamnesis_templates
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $templateId, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(int $clinicId, string $name): int
    {
        $sql = "
            INSERT INTO anamnesis_templates (clinic_id, name, status, created_at)
            VALUES (:clinic_id, :name, 'active', NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'name' => $name,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $clinicId, int $templateId, string $name, string $status): void
    {
        $sql = "
            UPDATE anamnesis_templates
            SET name = :name,
                status = :status,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $templateId,
            'clinic_id' => $clinicId,
            'name' => $name,
            'status' => $status,
        ]);
    }
}
