<?php

declare(strict_types=1);

namespace App\Repositories;

final class SaleRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listByClinic(int $clinicId, int $limit = 200, ?int $professionalId = null): array
    {
        $where = " s.clinic_id = :clinic_id AND s.deleted_at IS NULL ";
        $join = '';
        $params = ['clinic_id' => $clinicId];

        if ($professionalId !== null && $professionalId > 0) {
            $join = " INNER JOIN sale_items si ON si.sale_id = s.id AND si.deleted_at IS NULL ";
            $where .= " AND si.professional_id = :professional_id ";
            $params['professional_id'] = $professionalId;
        }

        $sql = "
            SELECT DISTINCT
                   s.id, s.clinic_id, s.patient_id,
                   s.total_bruto, s.desconto, s.total_liquido,
                   s.status, s.origin, s.notes,
                   s.created_by_user_id,
                   s.created_at, s.updated_at
            FROM sales s
            " . $join . "
            WHERE " . $where . "
            ORDER BY s.id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, patient_id,
                   total_bruto, desconto, total_liquido,
                   status, origin, notes,
                   created_by_user_id,
                   created_at, updated_at
            FROM sales
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

    public function create(
        int $clinicId,
        ?int $patientId,
        string $origin,
        ?string $notes,
        float $desconto,
        int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO sales (
                clinic_id, patient_id,
                total_bruto, desconto, total_liquido,
                status, origin, notes,
                created_by_user_id,
                created_at
            )
            VALUES (
                :clinic_id, :patient_id,
                0.00, :desconto, 0.00,
                'open', :origin, :notes,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'origin' => $origin,
            'notes' => ($notes === '' ? null : $notes),
            'desconto' => number_format(max(0.0, $desconto), 2, '.', ''),
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateTotals(int $clinicId, int $saleId, string $totalBruto, string $desconto, string $totalLiquido): void
    {
        $sql = "
            UPDATE sales
            SET total_bruto = :total_bruto,
                desconto = :desconto,
                total_liquido = :total_liquido,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $saleId,
            'clinic_id' => $clinicId,
            'total_bruto' => $totalBruto,
            'desconto' => $desconto,
            'total_liquido' => $totalLiquido,
        ]);
    }

    public function updateStatus(int $clinicId, int $saleId, string $status): void
    {
        $sql = "
            UPDATE sales
            SET status = :status,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $saleId,
            'clinic_id' => $clinicId,
            'status' => $status,
        ]);
    }
}
