<?php

declare(strict_types=1);

namespace App\Repositories;

final class FinancialEntryLogRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @param array<string,mixed>|null $before */
    /** @param array<string,mixed>|null $after */
    public function log(int $clinicId, int $entryId, string $action, ?array $before, ?array $after, ?int $actorUserId, ?string $ip): void
    {
        $sql = "
            INSERT INTO financial_entry_logs (
                clinic_id, entry_id,
                action, before_json, after_json,
                actor_user_id, ip_address,
                created_at
            )
            VALUES (
                :clinic_id, :entry_id,
                :action, :before_json, :after_json,
                :actor_user_id, :ip_address,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'entry_id' => $entryId,
            'action' => $action,
            'before_json' => $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'after_json' => $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'actor_user_id' => $actorUserId,
            'ip_address' => $ip,
        ]);
    }

    /** @return list<array<string,mixed>> */
    public function listByEntry(int $clinicId, int $entryId, int $limit = 200): array
    {
        $sql = "
            SELECT id, clinic_id, entry_id, action, before_json, after_json, actor_user_id, ip_address, created_at
            FROM financial_entry_logs
            WHERE clinic_id = :clinic_id
              AND entry_id = :entry_id
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'entry_id' => $entryId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }
}
