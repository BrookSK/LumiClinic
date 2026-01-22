<?php

declare(strict_types=1);

namespace App\Repositories;

final class UserRoleRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<string> */
    public function getRoleCodesForUser(int $clinicId, int $userId): array
    {
        $sql = "
            SELECT DISTINCT r.code
            FROM user_roles ur
            INNER JOIN roles r ON r.id = ur.role_id
            WHERE ur.clinic_id = :clinic_id
              AND ur.user_id = :user_id
              AND ur.deleted_at IS NULL
              AND r.deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'user_id' => $userId]);

        $rows = $stmt->fetchAll();
        $codes = [];
        foreach ($rows as $row) {
            $codes[] = (string)$row['code'];
        }

        return array_values(array_unique($codes));
    }
}
