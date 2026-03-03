<?php

declare(strict_types=1);

namespace App\Repositories;

final class DataExportRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @param array<string, mixed> $meta */
    public function create(
        ?int $clinicId,
        ?int $userId,
        string $action,
        ?string $entityType,
        ?int $entityId,
        ?string $format,
        ?string $filename,
        array $meta,
        ?string $ip,
        ?string $userAgent
    ): int {
        $sql = "
            INSERT INTO data_exports (
                clinic_id,
                user_id,
                action,
                entity_type,
                entity_id,
                format,
                filename,
                meta_json,
                ip_address,
                user_agent,
                created_at
            ) VALUES (
                :clinic_id,
                :user_id,
                :action,
                :entity_type,
                :entity_id,
                :format,
                :filename,
                :meta_json,
                :ip_address,
                :user_agent,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'format' => $format,
            'filename' => $filename,
            'meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
