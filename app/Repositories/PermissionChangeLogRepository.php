<?php

declare(strict_types=1);

namespace App\Repositories;

final class PermissionChangeLogRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @param array<string,mixed>|null $before @param array<string,mixed>|null $after */
    public function log(
        int $clinicId,
        int $actorUserId,
        int $roleId,
        string $action,
        ?array $before,
        ?array $after,
        ?string $ip
    ): void {
        $sql = "
            INSERT INTO permission_change_logs
                (clinic_id, actor_user_id, role_id, action, before_json, after_json, ip_address, created_at)
            VALUES
                (:clinic_id, :actor_user_id, :role_id, :action, :before_json, :after_json, :ip_address, NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'actor_user_id' => $actorUserId,
            'role_id' => $roleId,
            'action' => $action,
            'before_json' => $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'after_json' => $after !== null ? json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'ip_address' => $ip,
        ]);
    }
}
