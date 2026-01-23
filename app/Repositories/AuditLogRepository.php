<?php

declare(strict_types=1);

namespace App\Repositories;

final class AuditLogRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @param array<string, mixed> $meta */
    /** @param list<string>|null $roleCodes */
    public function log(
        ?int $userId,
        ?int $clinicId,
        string $action,
        array $meta,
        ?string $ip,
        ?array $roleCodes = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $userAgent = null,
        ?string $occurredAt = null,
    ): void
    {
        $sql = "
            INSERT INTO audit_logs (
                clinic_id,
                user_id,
                role_codes_json,
                action,
                entity_type,
                entity_id,
                meta_json,
                ip_address,
                user_agent,
                occurred_at,
                created_at
            )
            VALUES (
                :clinic_id,
                :user_id,
                :role_codes_json,
                :action,
                :entity_type,
                :entity_id,
                :meta_json,
                :ip_address,
                :user_agent,
                COALESCE(:occurred_at, NOW()),
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'user_id' => $userId,
            'role_codes_json' => $roleCodes !== null
                ? json_encode($roleCodes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'occurred_at' => $occurredAt,
        ]);
    }
}
