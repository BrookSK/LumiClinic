<?php

declare(strict_types=1);

namespace App\Repositories;

final class GoogleCalendarSyncLogRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @param array<string,mixed>|null $meta */
    public function log(
        int $clinicId,
        ?int $userId,
        ?int $tokenId,
        ?int $appointmentId,
        string $action,
        string $status,
        ?string $message,
        ?array $meta
    ): int {
        $stmt = $this->pdo->prepare("\n            INSERT INTO google_calendar_sync_logs (\n                clinic_id, user_id, token_id, appointment_id,\n                action, status, message, meta_json,\n                created_at\n            ) VALUES (\n                :clinic_id, :user_id, :token_id, :appointment_id,\n                :action, :status, :message, :meta_json,\n                NOW()\n            )\n        ");

        $metaJson = null;
        if (is_array($meta)) {
            $enc = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($enc !== false) {
                $metaJson = $enc;
            }
        }

        $stmt->execute([
            'clinic_id' => $clinicId,
            'user_id' => $userId,
            'token_id' => $tokenId,
            'appointment_id' => $appointmentId,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'meta_json' => $metaJson,
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
