<?php

declare(strict_types=1);

namespace App\Repositories;

final class RbacRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array{id:int,code:string,name:string,is_system:int,is_editable:int}> */
    public function listRolesByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, code, name, is_system, is_editable
            FROM roles
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
            ORDER BY is_system DESC, name ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int)$row['id'],
                'code' => (string)$row['code'],
                'name' => (string)$row['name'],
                'is_system' => (int)$row['is_system'],
                'is_editable' => (int)$row['is_editable'],
            ];
        }

        return $out;
    }

    public function findRoleById(int $clinicId, int $roleId): ?array
    {
        $sql = "
            SELECT id, code, name, is_system, is_editable
            FROM roles
            WHERE clinic_id = :clinic_id
              AND id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $roleId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return [
            'id' => (int)$row['id'],
            'code' => (string)$row['code'],
            'name' => (string)$row['name'],
            'is_system' => (int)$row['is_system'],
            'is_editable' => (int)$row['is_editable'],
        ];
    }

    public function createRole(int $clinicId, string $code, string $name, int $isSystem, int $isEditable): int
    {
        $sql = "
            INSERT INTO roles (clinic_id, code, name, is_system, is_editable, created_at)
            VALUES (:clinic_id, :code, :name, :is_system, :is_editable, NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'code' => $code,
            'name' => $name,
            'is_system' => $isSystem,
            'is_editable' => $isEditable,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateRoleName(int $clinicId, int $roleId, string $name): void
    {
        $sql = "
            UPDATE roles
            SET name = :name,
                updated_at = NOW()
            WHERE clinic_id = :clinic_id
              AND id = :id
              AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $roleId, 'name' => $name]);
    }

    /** @return list<array{id:int,module:string,action:string,code:string,description:?string}> */
    public function listGlobalPermissions(): array
    {
        $sql = "
            SELECT id, module, action, code, description
            FROM permissions
            WHERE deleted_at IS NULL
            ORDER BY module ASC, action ASC, code ASC
        ";

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int)$row['id'],
                'module' => (string)$row['module'],
                'action' => (string)$row['action'],
                'code' => (string)$row['code'],
                'description' => $row['description'] !== null ? (string)$row['description'] : null,
            ];
        }

        return $out;
    }

    /** @return array{allow:list<string>,deny:list<string>} */
    public function getRolePermissionDecisions(int $clinicId, int $roleId): array
    {
        $sql = "
            SELECT p.code, rp.effect
            FROM role_permissions rp
            INNER JOIN permissions p ON p.id = rp.permission_id
            WHERE rp.clinic_id = :clinic_id
              AND rp.role_id = :role_id
              AND rp.deleted_at IS NULL
              AND p.deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'role_id' => $roleId]);

        $rows = $stmt->fetchAll();

        $allow = [];
        $deny = [];
        foreach ($rows as $row) {
            $code = (string)$row['code'];
            $effect = isset($row['effect']) ? (string)$row['effect'] : 'allow';

            if ($effect === 'deny') {
                $deny[] = $code;
            } else {
                $allow[] = $code;
            }
        }

        return [
            'allow' => array_values(array_unique($allow)),
            'deny' => array_values(array_unique($deny)),
        ];
    }

    public function clearRolePermissions(int $clinicId, int $roleId): void
    {
        $sql = "
            DELETE FROM role_permissions
            WHERE clinic_id = :clinic_id
              AND role_id = :role_id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'role_id' => $roleId]);
    }

    public function addRolePermission(int $clinicId, int $roleId, int $permissionId, string $effect): void
    {
        $sql = "
            INSERT INTO role_permissions (clinic_id, role_id, permission_id, effect, created_at)
            VALUES (:clinic_id, :role_id, :permission_id, :effect, NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'effect' => $effect,
        ]);
    }

    public function copyRolePermissions(int $clinicId, int $fromRoleId, int $toRoleId): void
    {
        $sql = "
            INSERT INTO role_permissions (clinic_id, role_id, permission_id, effect, constraints_json, created_at)
            SELECT :clinic_id, :to_role_id, permission_id, effect, constraints_json, NOW()
            FROM role_permissions
            WHERE clinic_id = :clinic_id
              AND role_id = :from_role_id
              AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'to_role_id' => $toRoleId,
            'from_role_id' => $fromRoleId,
        ]);
    }

    public function resetRolePermissionsToDefaults(int $clinicId, string $roleCode, int $roleId): void
    {
        $this->clearRolePermissions($clinicId, $roleId);

        $sql = "
            INSERT INTO role_permissions (clinic_id, role_id, permission_id, effect, created_at)
            SELECT :clinic_id, :role_id, p.id, d.effect, NOW()
            FROM rbac_role_permission_defaults d
            INNER JOIN permissions p ON p.code = d.permission_code
            WHERE d.role_code = :role_code
              AND p.deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'role_id' => $roleId,
            'role_code' => $roleCode,
        ]);
    }
}
