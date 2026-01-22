<?php

declare(strict_types=1);

namespace App\Repositories;

final class SystemClinicRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listAll(): array
    {
        $sql = "
            SELECT id, name, tenant_key, status, created_at
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
            VALUES
            (:clinic_id, 'clinics', 'read', 'clinics.read', 'Ver dados da clínica', NOW()),
            (:clinic_id, 'clinics', 'create', 'clinics.create', 'Criar clínica', NOW()),
            (:clinic_id, 'clinics', 'update', 'clinics.update', 'Editar dados da clínica', NOW()),
            (:clinic_id, 'clinics', 'delete', 'clinics.delete', 'Excluir clínica', NOW()),
            (:clinic_id, 'clinics', 'export', 'clinics.export', 'Exportar dados da clínica', NOW()),

            (:clinic_id, 'users', 'read', 'users.read', 'Listar usuários', NOW()),
            (:clinic_id, 'users', 'create', 'users.create', 'Criar usuário', NOW()),
            (:clinic_id, 'users', 'update', 'users.update', 'Editar usuário', NOW()),
            (:clinic_id, 'users', 'delete', 'users.delete', 'Desativar/excluir usuário', NOW()),
            (:clinic_id, 'users', 'export', 'users.export', 'Exportar usuários', NOW()),
            (:clinic_id, 'users', 'sensitive', 'users.sensitive', 'Acesso a dados sensíveis de usuários', NOW()),

            (:clinic_id, 'settings', 'read', 'settings.read', 'Ver configurações', NOW()),
            (:clinic_id, 'settings', 'update', 'settings.update', 'Editar configurações', NOW()),

            (:clinic_id, 'audit', 'read', 'audit.read', 'Ver logs de auditoria', NOW()),
            (:clinic_id, 'audit', 'export', 'audit.export', 'Exportar logs de auditoria', NOW())
        ";

        $stmt2 = $this->pdo->prepare($sqlPerms);
        $stmt2->execute(['clinic_id' => $clinicId]);

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
        ";

        $stmtAssign = $this->pdo->prepare($sqlAssignRbacManage);
        $stmtAssign->execute(['clinic_id' => $clinicId, 'role_id' => $roleOwnerId]);

        $sqlRolePerms = "
            INSERT INTO role_permissions (clinic_id, role_id, permission_id, created_at)
            SELECT :clinic_id, :role_id, p.id, NOW()
            FROM permissions p
            WHERE p.clinic_id = :clinic_id
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
