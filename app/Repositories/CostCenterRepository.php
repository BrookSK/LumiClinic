<?php

declare(strict_types=1);

namespace App\Repositories;

final class CostCenterRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listActiveByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, clinic_id, name, code, description, category, chart_account_id, status
            FROM cost_centers
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

    /** @return list<array<string,mixed>> */
    public function listByClinic(int $clinicId): array
    {
        $sql = "
            SELECT cc.id, cc.clinic_id, cc.name, cc.code, cc.description, cc.category, cc.chart_account_id,
                   cc.status, cc.created_at, cc.updated_at,
                   ca.code AS chart_account_code,
                   ca.name AS chart_account_name
            FROM cost_centers cc
            LEFT JOIN chart_accounts ca
                   ON ca.id = cc.chart_account_id
                  AND ca.clinic_id = cc.clinic_id
                  AND ca.deleted_at IS NULL
            WHERE cc.clinic_id = :clinic_id
              AND cc.deleted_at IS NULL
            ORDER BY cc.status DESC, cc.name ASC
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
            SELECT id, clinic_id, name, code, description, category, chart_account_id, status
            FROM cost_centers
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
    public function findByNameIncludingDeleted(int $clinicId, string $name): ?array
    {
        $sql = "
            SELECT id, clinic_id, name, status, deleted_at
            FROM cost_centers
            WHERE clinic_id = :clinic_id
              AND name = :name
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'name' => $name]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function restore(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE cost_centers
            SET deleted_at = NULL,
                status = 'active',
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
        ]);
    }

    /** @return array<string,mixed>|null */
    public function findByCode(int $clinicId, string $code): ?array
    {
        $sql = "
            SELECT id, clinic_id, name, code, status
            FROM cost_centers
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

    public function create(int $clinicId, string $name, ?string $code = null, ?string $description = null, ?int $chartAccountId = null, ?string $category = null): int
    {
        $sql = "
            INSERT INTO cost_centers (clinic_id, name, code, description, category, chart_account_id, status, created_at)
            VALUES (:clinic_id, :name, :code, :description, :category, :chart_account_id, 'active', NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'name' => $name,
            'code' => $code,
            'description' => $description,
            'category' => $category,
            'chart_account_id' => $chartAccountId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $clinicId, int $id, string $name, ?string $code = null, ?string $description = null, ?int $chartAccountId = null, ?string $category = null): void
    {
        $sql = "
            UPDATE cost_centers
            SET name = :name,
                code = :code,
                description = :description,
                category = :category,
                chart_account_id = :chart_account_id,
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
            'name' => $name,
            'code' => $code,
            'description' => $description,
            'category' => $category,
            'chart_account_id' => $chartAccountId,
        ]);
    }

    /** Vincula (ou desvincula, com null) o centro de custo a uma conta do plano de contas. */
    public function setChartAccount(int $clinicId, int $id, ?int $chartAccountId): void
    {
        $sql = "
            UPDATE cost_centers
            SET chart_account_id = :chart_account_id,
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
            'chart_account_id' => $chartAccountId,
        ]);
    }

    public function setStatus(int $clinicId, int $id, string $status): void
    {
        $sql = "
            UPDATE cost_centers
            SET status = :status,
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
            'status' => $status,
        ]);
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE cost_centers
            SET deleted_at = NOW(),
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
        ]);
    }
}
