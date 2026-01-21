<?php

declare(strict_types=1);

namespace App\Repositories;

final class AuditLogRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @param array<string, mixed> $meta */
    public function log(?int $userId, ?int $clinicId, string $action, array $meta, ?string $ip): void
    {
        $sql = "
            INSERT INTO audit_logs (clinic_id, user_id, action, meta_json, ip_address, created_at)
            VALUES (:clinic_id, :user_id, :action, :meta_json, :ip_address, NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'user_id' => $userId,
            'action' => $action,
            'meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ip_address' => $ip,
        ]);
    }
}
