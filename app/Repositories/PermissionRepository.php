<?php

declare(strict_types=1);

namespace App\Repositories;

final class PermissionRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<string> */
    public function getPermissionCodesForUser(int $clinicId, int $userId): array
    {
        $sql = "
            SELECT DISTINCT p.code
            FROM user_roles ur
            INNER JOIN role_permissions rp ON rp.role_id = ur.role_id
            INNER JOIN permissions p ON p.id = rp.permission_id
            WHERE ur.clinic_id = :clinic_id
              AND ur.user_id = :user_id
              AND ur.deleted_at IS NULL
              AND rp.deleted_at IS NULL
              AND p.deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'user_id' => $userId]);

        $rows = $stmt->fetchAll();

        $codes = [];
        foreach ($rows as $row) {
            $codes[] = (string)$row['code'];
        }

        return $codes;
    }

    /**
     * @return array{allow:list<string>,deny:list<string>}
     */
    public function getPermissionDecisionsForUser(int $clinicId, int $userId): array
    {
        $allow = [];
        $deny = [];

        // Role-based permissions
        $sqlRoles = "
            SELECT DISTINCT p.code, rp.effect
            FROM user_roles ur
            INNER JOIN role_permissions rp ON rp.role_id = ur.role_id
            INNER JOIN permissions p ON p.id = rp.permission_id
            WHERE ur.clinic_id = :clinic_id
              AND ur.user_id = :user_id
              AND ur.deleted_at IS NULL
              AND rp.deleted_at IS NULL
              AND p.deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sqlRoles);
        $stmt->execute(['clinic_id' => $clinicId, 'user_id' => $userId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $code = (string)$row['code'];
            $effect = isset($row['effect']) ? (string)$row['effect'] : 'allow';

            if ($effect === 'deny') {
                $deny[] = $code;
            } else {
                $allow[] = $code;
            }
        }

        // User overrides (deny/allow), should be applied after role-based.
        $sqlOverrides = "
            SELECT p.code, u.effect
            FROM user_permissions_override u
            INNER JOIN permissions p ON p.id = u.permission_id
            WHERE u.clinic_id = :clinic_id
              AND u.user_id = :user_id
              AND u.deleted_at IS NULL
              AND p.deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sqlOverrides);
        $stmt->execute(['clinic_id' => $clinicId, 'user_id' => $userId]);
        $rows = $stmt->fetchAll();

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
}
