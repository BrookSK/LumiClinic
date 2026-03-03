<?php

declare(strict_types=1);

namespace App\Repositories;

final class ClinicPatientOriginRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listActiveByClinic(int $clinicId, int $limit = 300): array
    {
        $sql = "
            SELECT id, clinic_id, name, sort_order, status, created_at
            FROM clinic_patient_origins
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'ativo'
            ORDER BY sort_order ASC, name ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listAllByClinic(int $clinicId, int $limit = 500): array
    {
        $sql = "
            SELECT id, clinic_id, name, sort_order, status, created_at
            FROM clinic_patient_origins
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
            ORDER BY sort_order ASC, name ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function create(int $clinicId, string $name, int $sortOrder = 0): int
    {
        $sql = "
            INSERT INTO clinic_patient_origins (clinic_id, name, sort_order, status, created_at)
            VALUES (:clinic_id, :name, :sort_order, 'ativo', NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'name' => $name,
            'sort_order' => $sortOrder,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function existsActiveByClinicAndId(int $clinicId, int $id): bool
    {
        $sql = "
            SELECT 1
            FROM clinic_patient_origins
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'ativo'
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
        return (bool)$stmt->fetchColumn();
    }

    public function existsActiveByClinicAndName(int $clinicId, string $name): bool
    {
        $sql = "
            SELECT 1
            FROM clinic_patient_origins
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'ativo'
              AND name = :name
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'name' => $name]);
        return (bool)$stmt->fetchColumn();
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE clinic_patient_origins
            SET deleted_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
    }
}
