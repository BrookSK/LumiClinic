<?php

declare(strict_types=1);

namespace App\Repositories;

final class ProcedureProtocolRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByProcedure(int $clinicId, int $procedureId): array
    {
        $sql = "
            SELECT
                id, clinic_id, procedure_id,
                name, notes,
                sort_order,
                status,
                created_at, updated_at
            FROM procedure_protocols
            WHERE clinic_id = :clinic_id
              AND procedure_id = :procedure_id
              AND deleted_at IS NULL
            ORDER BY sort_order ASC, id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'procedure_id' => $procedureId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT
                id, clinic_id, procedure_id,
                name, notes,
                sort_order,
                status,
                created_at, updated_at
            FROM procedure_protocols
            WHERE clinic_id = :clinic_id
              AND id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(int $clinicId, int $procedureId, string $name, ?string $notes, int $sortOrder = 0): int
    {
        $sql = "
            INSERT INTO procedure_protocols (
                clinic_id, procedure_id,
                name, notes,
                sort_order,
                status,
                created_at
            )
            VALUES (
                :clinic_id, :procedure_id,
                :name, :notes,
                :sort_order,
                'active',
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'procedure_id' => $procedureId,
            'name' => $name,
            'notes' => $notes,
            'sort_order' => $sortOrder,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $clinicId, int $id, string $name, ?string $notes, int $sortOrder, string $status): void
    {
        if (!in_array($status, ['active', 'disabled'], true)) {
            $status = 'active';
        }

        $sql = "
            UPDATE procedure_protocols
               SET name = :name,
                   notes = :notes,
                   sort_order = :sort_order,
                   status = :status,
                   updated_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND deleted_at IS NULL
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'id' => $id,
            'name' => $name,
            'notes' => $notes,
            'sort_order' => $sortOrder,
            'status' => $status,
        ]);
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE procedure_protocols
               SET deleted_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND deleted_at IS NULL
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id]);
    }
}
