<?php

declare(strict_types=1);

namespace App\Repositories;

final class SecurityIncidentRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByClinic(int $clinicId, int $limit = 200): array
    {
        $sql = "
            SELECT
                id, clinic_id,
                severity, status,
                title, description,
                detected_at, resolved_at,
                reported_by_user_id, assigned_to_user_id,
                corrective_action,
                created_at, updated_at
            FROM security_incidents
            WHERE clinic_id = :clinic_id
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
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
                severity, status,
                title, description,
                detected_at, resolved_at,
                reported_by_user_id, assigned_to_user_id,
                corrective_action,
                created_at, updated_at
            FROM security_incidents
            WHERE clinic_id = :clinic_id
              AND id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(
        int $clinicId,
        string $severity,
        string $title,
        ?string $description,
        ?int $reportedByUserId
    ): int {
        $sql = "
            INSERT INTO security_incidents (
                clinic_id,
                severity, status,
                title, description,
                detected_at,
                reported_by_user_id,
                created_at
            ) VALUES (
                :clinic_id,
                :severity, 'open',
                :title, :description,
                NOW(),
                :reported_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'severity' => $severity,
            'title' => $title,
            'description' => ($description === '' ? null : $description),
            'reported_by_user_id' => $reportedByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(
        int $clinicId,
        int $id,
        string $status,
        ?int $assignedToUserId,
        ?string $correctiveAction
    ): void {
        $resolvedAt = null;
        if ($status === 'resolved') {
            $resolvedAt = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        }

        $sql = "
            UPDATE security_incidents
            SET status = :status,
                assigned_to_user_id = :assigned_to_user_id,
                corrective_action = :corrective_action,
                resolved_at = :resolved_at,
                updated_at = NOW()
            WHERE clinic_id = :clinic_id
              AND id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'id' => $id,
            'status' => $status,
            'assigned_to_user_id' => $assignedToUserId,
            'corrective_action' => ($correctiveAction === '' ? null : $correctiveAction),
            'resolved_at' => $resolvedAt,
        ]);
    }
}
