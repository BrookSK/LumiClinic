<?php

declare(strict_types=1);

namespace App\Repositories;

final class ProcedureProtocolStepRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByProtocol(int $clinicId, int $protocolId): array
    {
        $sql = "
            SELECT
                id, clinic_id, protocol_id,
                title,
                duration_minutes,
                notes,
                sort_order,
                created_at, updated_at
            FROM procedure_protocol_steps
            WHERE clinic_id = :clinic_id
              AND protocol_id = :protocol_id
              AND deleted_at IS NULL
            ORDER BY sort_order ASC, id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'protocol_id' => $protocolId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT
                id, clinic_id, protocol_id,
                title,
                duration_minutes,
                notes,
                sort_order,
                created_at, updated_at
            FROM procedure_protocol_steps
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

    public function create(int $clinicId, int $protocolId, string $title, ?int $durationMinutes, ?string $notes, int $sortOrder = 0): int
    {
        $durationMinutes = $durationMinutes !== null ? max(0, $durationMinutes) : null;

        $sql = "
            INSERT INTO procedure_protocol_steps (
                clinic_id, protocol_id,
                title,
                duration_minutes,
                notes,
                sort_order,
                created_at
            )
            VALUES (
                :clinic_id, :protocol_id,
                :title,
                :duration_minutes,
                :notes,
                :sort_order,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'protocol_id' => $protocolId,
            'title' => $title,
            'duration_minutes' => $durationMinutes,
            'notes' => $notes,
            'sort_order' => $sortOrder,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $clinicId, int $id, string $title, ?int $durationMinutes, ?string $notes, int $sortOrder): void
    {
        $durationMinutes = $durationMinutes !== null ? max(0, $durationMinutes) : null;

        $sql = "
            UPDATE procedure_protocol_steps
               SET title = :title,
                   duration_minutes = :duration_minutes,
                   notes = :notes,
                   sort_order = :sort_order,
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
            'title' => $title,
            'duration_minutes' => $durationMinutes,
            'notes' => $notes,
            'sort_order' => $sortOrder,
        ]);
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE procedure_protocol_steps
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
