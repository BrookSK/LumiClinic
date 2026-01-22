<?php

declare(strict_types=1);

namespace App\Repositories;

final class ConsentTermRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, clinic_id, procedure_type, title, status, created_at, updated_at
            FROM consent_terms
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
            ORDER BY procedure_type ASC, title ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, procedure_type, title, body, status, created_at, updated_at
            FROM consent_terms
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

    public function create(int $clinicId, string $procedureType, string $title, string $body): int
    {
        $sql = "
            INSERT INTO consent_terms (clinic_id, procedure_type, title, body, status, created_at)
            VALUES (:clinic_id, :procedure_type, :title, :body, 'active', NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'procedure_type' => $procedureType,
            'title' => $title,
            'body' => $body,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $clinicId, int $id, string $procedureType, string $title, string $body, string $status): void
    {
        $sql = "
            UPDATE consent_terms
            SET procedure_type = :procedure_type,
                title = :title,
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
            'procedure_type' => $procedureType,
            'title' => $title,
            'body' => $body,
            'status' => $status,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function listActiveByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, clinic_id, procedure_type, title, body, status
            FROM consent_terms
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'active'
            ORDER BY procedure_type ASC, title ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }
}
