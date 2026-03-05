<?php

declare(strict_types=1);

namespace App\Repositories;

final class ProcedureRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listActiveByClinic(int $clinicId): array
    {
        $sql = "
            SELECT
                id, clinic_id,
                name,
                contraindications, pre_guidelines, post_guidelines,
                status,
                created_at, updated_at
            FROM procedures
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'active'
            ORDER BY name ASC
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
            SELECT
                id, clinic_id,
                name,
                contraindications, pre_guidelines, post_guidelines,
                status,
                created_at, updated_at
            FROM procedures
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

    public function create(
        int $clinicId,
        string $name,
        ?string $contraindications,
        ?string $preGuidelines,
        ?string $postGuidelines
    ): int {
        $sql = "
            INSERT INTO procedures (
                clinic_id,
                name,
                contraindications,
                pre_guidelines,
                post_guidelines,
                status,
                created_at
            )
            VALUES (
                :clinic_id,
                :name,
                :contraindications,
                :pre_guidelines,
                :post_guidelines,
                'active',
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'name' => $name,
            'contraindications' => $contraindications,
            'pre_guidelines' => $preGuidelines,
            'post_guidelines' => $postGuidelines,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(
        int $clinicId,
        int $id,
        string $name,
        ?string $contraindications,
        ?string $preGuidelines,
        ?string $postGuidelines,
        string $status
    ): void {
        if (!in_array($status, ['active', 'disabled'], true)) {
            $status = 'active';
        }

        $sql = "
            UPDATE procedures
               SET name = :name,
                   contraindications = :contraindications,
                   pre_guidelines = :pre_guidelines,
                   post_guidelines = :post_guidelines,
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
            'contraindications' => $contraindications,
            'pre_guidelines' => $preGuidelines,
            'post_guidelines' => $postGuidelines,
            'status' => $status,
        ]);
    }

    /**
     * @param list<int> $procedureIds
     * @return array<string,int> map[procedure_id] => avg_minutes
     */
    public function avgRealDurationMinutesByProcedure(int $clinicId, array $procedureIds): array
    {
        $procedureIds = array_values(array_unique(array_filter(array_map('intval', $procedureIds), static fn ($v) => $v > 0)));
        if ($procedureIds === []) {
            return [];
        }

        $in = implode(',', array_fill(0, count($procedureIds), '?'));
        $sql = "
            SELECT
                s.procedure_id AS procedure_id,
                AVG(
                    TIMESTAMPDIFF(
                        MINUTE,
                        COALESCE(a.started_at, a.start_at),
                        a.end_at
                    )
                ) AS avg_minutes
            FROM appointments a
            INNER JOIN services s
                    ON s.id = a.service_id
                   AND s.clinic_id = a.clinic_id
                   AND s.deleted_at IS NULL
            WHERE a.clinic_id = ?
              AND a.deleted_at IS NULL
              AND a.status = 'completed'
              AND s.procedure_id IN ($in)
              AND a.end_at IS NOT NULL
              AND COALESCE(a.started_at, a.start_at) IS NOT NULL
              AND TIMESTAMPDIFF(MINUTE, COALESCE(a.started_at, a.start_at), a.end_at) > 0
            GROUP BY s.procedure_id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge([$clinicId], $procedureIds));

        $out = [];
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $pid = (int)($row['procedure_id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $avg = (int)round((float)($row['avg_minutes'] ?? 0));
            if ($avg <= 0) {
                continue;
            }
            $out[(string)$pid] = $avg;
        }

        return $out;
    }
}
