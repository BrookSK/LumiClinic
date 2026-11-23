<?php

declare(strict_types=1);

namespace App\Repositories;

final class ChartAccountRepository
{
    private const COLUMNS = 'ca.id, ca.clinic_id, ca.parent_id, ca.code, ca.name, ca.nature, ca.account_type, ca.payment_method, ca.default_for, ca.external_code, ca.description, ca.status, ca.created_at, ca.updated_at';

    public function __construct(private readonly \PDO $pdo) {}

    /**
     * Ordena por código de forma natural por segmento ("1.1.2.07" < "1.1.2.08", "1.9" < "1.10", "1.1" < "1.1.01").
     *
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    public static function sortByCode(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            $sa = explode('.', (string)($a['code'] ?? ''));
            $sb = explode('.', (string)($b['code'] ?? ''));
            $n = min(count($sa), count($sb));
            for ($i = 0; $i < $n; $i++) {
                $cmp = ((int)$sa[$i]) <=> ((int)$sb[$i]);
                if ($cmp !== 0) {
                    return $cmp;
                }
                $cmp = strcmp($sa[$i], $sb[$i]);
                if ($cmp !== 0) {
                    return $cmp;
                }
            }
            return count($sa) <=> count($sb);
        });

        return $rows;
    }

    /**
     * Todas as contas não excluídas da clínica, ordenadas por código.
     * Inclui linked_cost_centers (quantidade de centros de custo não excluídos vinculados).
     *
     * @return list<array<string,mixed>>
     */
    public function listByClinic(int $clinicId): array
    {
        $sql = "
            SELECT " . self::COLUMNS . ",
                   (
                       SELECT COUNT(*)
                       FROM cost_centers cc
                       WHERE cc.clinic_id = ca.clinic_id
                         AND cc.chart_account_id = ca.id
                         AND cc.deleted_at IS NULL
                   ) AS linked_cost_centers
            FROM chart_accounts ca
            WHERE ca.clinic_id = :clinic_id
              AND ca.deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> $rows */
        $rows = $stmt->fetchAll();
        return self::sortByCode($rows);
    }

    /**
     * Contas ativas e analíticas (recebem lançamentos), ordenadas por código. Para selects.
     *
     * @return list<array<string,mixed>>
     */
    public function listActiveAnalytic(int $clinicId): array
    {
        $sql = "
            SELECT " . self::COLUMNS . "
            FROM chart_accounts ca
            WHERE ca.clinic_id = :clinic_id
              AND ca.deleted_at IS NULL
              AND ca.status = 'active'
              AND ca.account_type = 'analytic'
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> $rows */
        $rows = $stmt->fetchAll();
        return self::sortByCode($rows);
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT " . self::COLUMNS . "
            FROM chart_accounts ca
            WHERE ca.id = :id
              AND ca.clinic_id = :clinic_id
              AND ca.deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findByCodeIncludingDeleted(int $clinicId, string $code): ?array
    {
        $sql = "
            SELECT " . self::COLUMNS . ", ca.deleted_at
            FROM chart_accounts ca
            WHERE ca.clinic_id = :clinic_id
              AND ca.code = :code
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'code' => $code]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** Quantidade de contas não excluídas (qualquer status). */
    public function countByClinic(int $clinicId): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM chart_accounts
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        return (int)$stmt->fetchColumn();
    }

    /** Indica se a conta possui contas filhas não excluídas (qualquer status). */
    public function hasChildren(int $clinicId, int $id): bool
    {
        $sql = "
            SELECT 1
            FROM chart_accounts
            WHERE clinic_id = :clinic_id
              AND parent_id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id]);

        return $stmt->fetchColumn() !== false;
    }

    /** Quantidade de centros de custo não excluídos vinculados à conta. */
    public function countLinkedCostCenters(int $clinicId, int $id): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM cost_centers
            WHERE clinic_id = :clinic_id
              AND chart_account_id = :id
              AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Conta ativa e analítica vinculada à forma de pagamento (pix, credit_card, cash...).
     *
     * @return array<string,mixed>|null
     */
    public function findByPaymentMethod(int $clinicId, string $method): ?array
    {
        $sql = "
            SELECT " . self::COLUMNS . "
            FROM chart_accounts ca
            WHERE ca.clinic_id = :clinic_id
              AND ca.payment_method = :method
              AND ca.deleted_at IS NULL
              AND ca.status = 'active'
              AND ca.account_type = 'analytic'
            ORDER BY ca.id ASC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'method' => $method]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Conta ativa e analítica padrão para lançamentos sem classificação ('revenue' ou 'expense').
     *
     * @return array<string,mixed>|null
     */
    public function findDefaultFor(int $clinicId, string $kind): ?array
    {
        $sql = "
            SELECT " . self::COLUMNS . "
            FROM chart_accounts ca
            WHERE ca.clinic_id = :clinic_id
              AND ca.default_for = :kind
              AND ca.deleted_at IS NULL
              AND ca.status = 'active'
              AND ca.account_type = 'analytic'
            ORDER BY ca.id ASC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'kind' => $kind]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** Remove a marcação default_for de todas as contas da clínica, exceto $exceptId. */
    public function clearDefaultFor(int $clinicId, string $kind, ?int $exceptId = null): void
    {
        $sql = "
            UPDATE chart_accounts
            SET default_for = NULL,
                updated_at = NOW()
            WHERE clinic_id = :clinic_id
              AND default_for = :kind
              AND id <> :except_id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'kind' => $kind,
            'except_id' => $exceptId ?? 0,
        ]);
    }

    /**
     * @param array{parent_id:?int,code:string,name:string,nature:string,account_type:string,payment_method:?string,default_for:?string,external_code:?string,description:?string} $data
     */
    public function create(int $clinicId, array $data): int
    {
        $sql = "
            INSERT INTO chart_accounts (
                clinic_id, parent_id, code, name, nature, account_type,
                payment_method, default_for, external_code, description, status, created_at
            ) VALUES (
                :clinic_id, :parent_id, :code, :name, :nature, :account_type,
                :payment_method, :default_for, :external_code, :description, 'active', NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'parent_id' => $data['parent_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'nature' => $data['nature'],
            'account_type' => $data['account_type'],
            'payment_method' => $data['payment_method'],
            'default_for' => $data['default_for'],
            'external_code' => $data['external_code'],
            'description' => $data['description'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Atualiza os dados da conta. Não altera status nem deleted_at.
     *
     * @param array{parent_id:?int,code:string,name:string,nature:string,account_type:string,payment_method:?string,default_for:?string,external_code:?string,description:?string} $data
     */
    public function update(int $clinicId, int $id, array $data): void
    {
        $sql = "
            UPDATE chart_accounts
            SET parent_id = :parent_id,
                code = :code,
                name = :name,
                nature = :nature,
                account_type = :account_type,
                payment_method = :payment_method,
                default_for = :default_for,
                external_code = :external_code,
                description = :description,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
            'parent_id' => $data['parent_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'nature' => $data['nature'],
            'account_type' => $data['account_type'],
            'payment_method' => $data['payment_method'],
            'default_for' => $data['default_for'],
            'external_code' => $data['external_code'],
            'description' => $data['description'],
        ]);
    }

    public function setStatus(int $clinicId, int $id, string $status): void
    {
        $sql = "
            UPDATE chart_accounts
            SET status = :status,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId, 'status' => $status]);
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE chart_accounts
            SET deleted_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
    }

    public function restore(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE chart_accounts
            SET deleted_at = NULL,
                status = 'active',
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
    }
}
