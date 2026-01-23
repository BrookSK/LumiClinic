<?php

declare(strict_types=1);

namespace App\Repositories;

final class DataVersionRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /**
     * @param array<string,mixed> $snapshot
     */
    public function record(
        int $clinicId,
        string $entityType,
        int $entityId,
        string $action,
        array $snapshot,
        ?int $createdByUserId,
        ?string $ip,
        ?string $userAgent
    ): void {
        $snapshotJson = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($snapshotJson === false) {
            throw new \RuntimeException('Falha ao serializar snapshot.');
        }

        $hash = hash('sha256', $clinicId . '|' . $entityType . '|' . $entityId . '|' . $action . '|' . $snapshotJson);

        $sql = "
            INSERT IGNORE INTO data_versions (
                clinic_id,
                entity_type,
                entity_id,
                action,
                snapshot_json,
                snapshot_hash,
                created_by_user_id,
                ip_address,
                user_agent,
                occurred_at,
                created_at
            ) VALUES (
                :clinic_id,
                :entity_type,
                :entity_id,
                :action,
                CAST(:snapshot_json AS JSON),
                :snapshot_hash,
                :created_by_user_id,
                :ip_address,
                :user_agent,
                NOW(),
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'snapshot_json' => $snapshotJson,
            'snapshot_hash' => $hash,
            'created_by_user_id' => $createdByUserId,
            'ip_address' => ($ip === '' ? null : $ip),
            'user_agent' => ($userAgent === '' ? null : $userAgent),
        ]);
    }
}
