<?php

declare(strict_types=1);

namespace App\Repositories;

final class RoleRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array{id:int,code:string,name:string}> */
    public function listByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, code, name
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
            ];
        }

        return $out;
    }

    public function assignRoleToUser(int $clinicId, int $userId, int $roleId): void
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

    public function clearRolesForUser(int $clinicId, int $userId): void
    {
        $sql = "
            DELETE FROM user_roles
            WHERE clinic_id = :clinic_id
              AND user_id = :user_id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'user_id' => $userId]);
    }
}
