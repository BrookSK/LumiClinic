<?php

declare(strict_types=1);

namespace App\Repositories;

final class SystemClinicRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function search(string $q, int $limit = 200): array
    {
        $q = trim($q);
        $limit = max(1, min(500, $limit));
        if ($q === '') {
            return $this->listAll();
        }

        $like = '%' . $q . '%';
        $sql = "
            SELECT
                id, name, tenant_key, status, created_at,
                (
                    SELECT d.domain
                    FROM clinic_domains d
                    WHERE d.clinic_id = clinics.id
                      AND d.is_primary = 1
                    ORDER BY d.id DESC
                    LIMIT 1
                ) AS primary_domain
            FROM clinics
            WHERE deleted_at IS NULL
              AND (
                  name LIKE :like
                  OR tenant_key LIKE :like
                  OR EXISTS (
                      SELECT 1
                      FROM clinic_domains d
                      WHERE d.clinic_id = clinics.id
                        AND d.domain LIKE :like
                        AND d.deleted_at IS NULL
                  )
              )
            ORDER BY id DESC
            LIMIT {$limit}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['like' => $like]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function listAll(): array
    {
        $sql = "
            SELECT
                id, name, tenant_key, status, created_at,
                (
                    SELECT d.domain
                    FROM clinic_domains d
                    WHERE d.clinic_id = clinics.id
                      AND d.is_primary = 1
                    ORDER BY d.id DESC
                    LIMIT 1
                ) AS primary_domain
            FROM clinics
            WHERE deleted_at IS NULL
            ORDER BY id DESC
        ";

        $stmt = $this->pdo->query($sql);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    public function createClinic(string $name, ?string $tenantKey): int
    {
        $sql = "
            INSERT INTO clinics (name, tenant_key, status, created_at)
            VALUES (:name, :tenant_key, 'active', NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'tenant_key' => $tenantKey,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function createPrimaryDomain(int $clinicId, string $domain): int
    {
        $sql = "
            INSERT INTO clinic_domains (clinic_id, domain, is_primary, created_at)
            VALUES (:clinic_id, :domain, 1, NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'domain' => $domain,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function createClinicDefaults(int $clinicId): void
    {
        $encryptionKey = bin2hex(random_bytes(32));

        $sql1 = "
            INSERT INTO clinic_settings (clinic_id, timezone, language, encryption_key, created_at)
            VALUES (:clinic_id, 'America/Sao_Paulo', 'pt-BR', :encryption_key, NOW())
        ";

        $sql2 = "
            INSERT INTO clinic_terminology (clinic_id, patient_label, appointment_label, professional_label, created_at)
            VALUES (:clinic_id, 'Paciente', 'Consulta', 'Profissional', NOW())
        ";

        $stmt1 = $this->pdo->prepare($sql1);
        $stmt1->execute(['clinic_id' => $clinicId, 'encryption_key' => $encryptionKey]);

        $stmt2 = $this->pdo->prepare($sql2);
        $stmt2->execute(['clinic_id' => $clinicId]);
    }

    public function createOwnerUser(int $clinicId, string $name, string $email, string $passwordHash): int
    {
        $sql = "
            INSERT INTO users (clinic_id, name, email, password_hash, status, created_at)
            VALUES (:clinic_id, :name, :email, :password_hash, 'active', NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function seedRbacAndReturnOwnerRoleId(int $clinicId): int
    {
        $sqlRoles = "
            INSERT INTO roles (clinic_id, code, name, is_system, created_at)
            VALUES
            (:clinic_id, 'owner', 'Owner', 1, NOW()),
            (:clinic_id, 'admin', 'Admin', 1, NOW()),
            (:clinic_id, 'professional', 'Profissional', 1, NOW()),
            (:clinic_id, 'reception', 'Recepção', 1, NOW()),
            (:clinic_id, 'finance', 'Financeiro', 1, NOW())
        ";

        $stmt = $this->pdo->prepare($sqlRoles);
        $stmt->execute(['clinic_id' => $clinicId]);

        $sqlPerms = "
            INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
            SELECT NULL, t.module, t.action, t.code, t.description, NOW()
            FROM (
                SELECT 'clinics' AS module, 'read' AS action, 'clinics.read' AS code, 'Ver dados da clínica' AS description
                UNION ALL SELECT 'clinics', 'create', 'clinics.create', 'Criar clínica'
                UNION ALL SELECT 'clinics', 'update', 'clinics.update', 'Editar dados da clínica'
                UNION ALL SELECT 'clinics', 'delete', 'clinics.delete', 'Excluir clínica'
                UNION ALL SELECT 'clinics', 'export', 'clinics.export', 'Exportar dados da clínica'

                UNION ALL SELECT 'users', 'read', 'users.read', 'Listar usuários'
                UNION ALL SELECT 'users', 'create', 'users.create', 'Criar usuário'
                UNION ALL SELECT 'users', 'update', 'users.update', 'Editar usuário'
                UNION ALL SELECT 'users', 'delete', 'users.delete', 'Desativar/excluir usuário'
                UNION ALL SELECT 'users', 'export', 'users.export', 'Exportar usuários'
                UNION ALL SELECT 'users', 'sensitive', 'users.sensitive', 'Acesso a dados sensíveis de usuários'

                UNION ALL SELECT 'settings', 'read', 'settings.read', 'Ver configurações'
                UNION ALL SELECT 'settings', 'update', 'settings.update', 'Editar configurações'

                UNION ALL SELECT 'audit', 'read', 'audit.read', 'Ver logs de auditoria'
                UNION ALL SELECT 'audit', 'export', 'audit.export', 'Exportar logs de auditoria'
            ) t
            WHERE NOT EXISTS (
                SELECT 1 FROM permissions p WHERE p.code = t.code AND p.deleted_at IS NULL
            )
        ";

        $this->pdo->exec($sqlPerms);

        $roleOwnerId = $this->getRoleId($clinicId, 'owner');

        $sqlEnsureRbacManage = "
            INSERT INTO permissions (clinic_id, module, action, code, description, created_at)
            SELECT NULL, 'rbac', 'manage', 'rbac.manage', 'Gerenciar papéis e permissões', NOW()
            WHERE NOT EXISTS (
                SELECT 1 FROM permissions p WHERE p.code = 'rbac.manage' AND p.deleted_at IS NULL
            )
        ";

        $this->pdo->exec($sqlEnsureRbacManage);

        $sqlAssignRbacManage = "
            INSERT INTO role_permissions (clinic_id, role_id, permission_id, effect, created_at)
            SELECT :clinic_id, :role_id, p.id, 'allow', NOW()
            FROM permissions p
            WHERE p.code = 'rbac.manage'
              AND p.deleted_at IS NULL
              AND NOT EXISTS (
                  SELECT 1
                  FROM role_permissions rp
                  WHERE rp.clinic_id = :clinic_id
                    AND rp.role_id = :role_id
                    AND rp.permission_id = p.id
                    AND rp.deleted_at IS NULL
              )
        ";

        $stmtAssign = $this->pdo->prepare($sqlAssignRbacManage);
        $stmtAssign->execute(['clinic_id' => $clinicId, 'role_id' => $roleOwnerId]);

        $sqlRolePerms = "
            INSERT INTO role_permissions (clinic_id, role_id, permission_id, created_at)
            SELECT :clinic_id, :role_id, p.id, NOW()
            FROM permissions p
            WHERE p.deleted_at IS NULL
              AND NOT EXISTS (
                  SELECT 1
                  FROM role_permissions rp
                  WHERE rp.clinic_id = :clinic_id
                    AND rp.role_id = :role_id
                    AND rp.permission_id = p.id
                    AND rp.deleted_at IS NULL
              )
        ";

        $stmt3 = $this->pdo->prepare($sqlRolePerms);
        $stmt3->execute(['clinic_id' => $clinicId, 'role_id' => $roleOwnerId]);

        return $roleOwnerId;
    }

    private function getRoleId(int $clinicId, string $code): int
    {
        $sql = "
            SELECT id
            FROM roles
            WHERE clinic_id = :clinic_id
              AND code = :code
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'code' => $code]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new \RuntimeException('Role não encontrada.');
        }

        return (int)$row['id'];
    }

    public function assignRole(int $clinicId, int $userId, int $roleId): void
    {
        $sql = "
            INSERT INTO user_roles (clinic_id, user_id, role_id, created_at)
            VALUES (:clinic_id, :user_id, :role_id, NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);
    }
}
