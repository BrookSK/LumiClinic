<?php

declare(strict_types=1);

namespace App\Repositories;

final class StockMovementRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByClinic(int $clinicId, string $fromDate, string $toDate, int $limit = 500): array
    {
        $sql = "
            SELECT
                id, clinic_id, material_id,
                type, quantity,
                reference_type, reference_id,
                loss_reason,
                unit_cost_snapshot, total_cost_snapshot,
                notes,
                user_id,
                created_at
            FROM stock_movements
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND DATE(created_at) BETWEEN :from_date AND :to_date
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function create(
        int $clinicId,
        int $materialId,
        string $type,
        string $quantity,
        ?string $referenceType,
        ?int $referenceId,
        ?string $lossReason,
        string $unitCostSnapshot,
        string $totalCostSnapshot,
        ?string $notes,
        ?int $userId
    ): int {
        $sql = "
            INSERT INTO stock_movements (
                clinic_id, material_id,
                type, quantity,
                reference_type, reference_id,
                loss_reason,
                unit_cost_snapshot, total_cost_snapshot,
                notes,
                user_id,
                created_at
            )
            VALUES (
                :clinic_id, :material_id,
                :type, :quantity,
                :reference_type, :reference_id,
                :loss_reason,
                :unit_cost_snapshot, :total_cost_snapshot,
                :notes,
                :user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'material_id' => $materialId,
            'type' => $type,
            'quantity' => $quantity,
            'reference_type' => ($referenceType === '' ? null : $referenceType),
            'reference_id' => $referenceId,
            'loss_reason' => ($lossReason === '' ? null : $lossReason),
            'unit_cost_snapshot' => $unitCostSnapshot,
            'total_cost_snapshot' => $totalCostSnapshot,
            'notes' => ($notes === '' ? null : $notes),
            'user_id' => $userId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function existsForReference(int $clinicId, string $referenceType, int $referenceId): bool
    {
        $sql = "
            SELECT 1
            FROM stock_movements
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND reference_type = :reference_type
              AND reference_id = :reference_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);

        return (bool)$stmt->fetchColumn();
    }

    /** @return array{total_exit_cost:float,total_loss_cost:float,total_expiration_cost:float,total_session_cost:float} */
    public function summarizeCosts(int $clinicId, string $fromDate, string $toDate): array
    {
        $sql = "
            SELECT
                SUM(CASE WHEN type = 'exit' THEN total_cost_snapshot ELSE 0 END) AS total_exit_cost,
                SUM(CASE WHEN type = 'loss' THEN total_cost_snapshot ELSE 0 END) AS total_loss_cost,
                SUM(CASE WHEN type = 'expiration' THEN total_cost_snapshot ELSE 0 END) AS total_expiration_cost,
                SUM(CASE WHEN reference_type = 'session' AND type = 'exit' THEN total_cost_snapshot ELSE 0 END) AS total_session_cost
            FROM stock_movements
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND DATE(created_at) BETWEEN :from_date AND :to_date
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        $row = $stmt->fetch() ?: [];

        return [
            'total_exit_cost' => (float)($row['total_exit_cost'] ?? 0),
            'total_loss_cost' => (float)($row['total_loss_cost'] ?? 0),
            'total_expiration_cost' => (float)($row['total_expiration_cost'] ?? 0),
            'total_session_cost' => (float)($row['total_session_cost'] ?? 0),
        ];
    }

    /** @return list<array<string,mixed>> */
    public function aggregateByMaterial(int $clinicId, string $fromDate, string $toDate, int $limit = 200): array
    {
        $sql = "
            SELECT
                m.id AS material_id,
                m.name AS material_name,
                m.unit AS unit,
                SUM(CASE WHEN sm.type IN ('exit','loss','expiration') THEN sm.quantity ELSE 0 END) AS qty,
                SUM(CASE WHEN sm.type IN ('exit','loss','expiration') THEN sm.total_cost_snapshot ELSE 0 END) AS cost
            FROM stock_movements sm
            INNER JOIN materials m
                    ON m.id = sm.material_id
                   AND m.clinic_id = sm.clinic_id
                   AND m.deleted_at IS NULL
            WHERE sm.clinic_id = :clinic_id
              AND sm.deleted_at IS NULL
              AND DATE(sm.created_at) BETWEEN :from_date AND :to_date
            GROUP BY m.id, m.name, m.unit
            ORDER BY cost DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function aggregateSessionCostByService(int $clinicId, string $fromDate, string $toDate, int $limit = 200): array
    {
        $sql = "
            SELECT
                s.id AS service_id,
                s.name AS service_name,
                COUNT(DISTINCT a.id) AS sessions,
                SUM(sm.total_cost_snapshot) AS cost
            FROM stock_movements sm
            INNER JOIN appointments a
                    ON a.id = sm.reference_id
                   AND a.clinic_id = sm.clinic_id
                   AND a.deleted_at IS NULL
            INNER JOIN services s
                    ON s.id = a.service_id
                   AND s.clinic_id = a.clinic_id
                   AND s.deleted_at IS NULL
            WHERE sm.clinic_id = :clinic_id
              AND sm.deleted_at IS NULL
              AND sm.reference_type = 'session'
              AND sm.type = 'exit'
              AND DATE(sm.created_at) BETWEEN :from_date AND :to_date
            GROUP BY s.id, s.name
            ORDER BY cost DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function aggregateSessionCostByProfessional(int $clinicId, string $fromDate, string $toDate, int $limit = 200): array
    {
        $sql = "
            SELECT
                p.id AS professional_id,
                p.name AS professional_name,
                COUNT(DISTINCT a.id) AS sessions,
                SUM(sm.total_cost_snapshot) AS cost
            FROM stock_movements sm
            INNER JOIN appointments a
                    ON a.id = sm.reference_id
                   AND a.clinic_id = sm.clinic_id
                   AND a.deleted_at IS NULL
            INNER JOIN professionals p
                    ON p.id = a.professional_id
                   AND p.clinic_id = a.clinic_id
                   AND p.deleted_at IS NULL
            WHERE sm.clinic_id = :clinic_id
              AND sm.deleted_at IS NULL
              AND sm.reference_type = 'session'
              AND sm.type = 'exit'
              AND DATE(sm.created_at) BETWEEN :from_date AND :to_date
            GROUP BY p.id, p.name
            ORDER BY cost DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }
}
