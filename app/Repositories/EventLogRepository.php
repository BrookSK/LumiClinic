<?php

declare(strict_types=1);

namespace App\Repositories;

final class EventLogRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @param array<string,mixed> $payload */
    public function log(
        ?int $clinicId,
        ?int $userId,
        ?string $role,
        string $event,
        ?string $entityType,
        ?int $entityId,
        array $payload,
        ?string $ip,
        ?string $userAgent
    ): void {
        $stmt = $this->pdo->prepare("\n            INSERT INTO event_logs (
                clinic_id, user_id, role, event, entity_type, entity_id,
                payload_json, ip, user_agent, created_at
            ) VALUES (
                :clinic_id, :user_id, :role, :event, :entity_type, :entity_id,
                :payload_json, :ip, :user_agent, NOW()
            )
        ");

        $stmt->execute([
            'clinic_id' => $clinicId,
            'user_id' => $userId,
            'role' => $role,
            'event' => $event,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ip' => $ip,
            'user_agent' => $userAgent,
        ]);
    }
}
