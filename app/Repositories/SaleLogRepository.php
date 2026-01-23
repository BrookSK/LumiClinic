<?php

declare(strict_types=1);

namespace App\Repositories;

final class SaleLogRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @param array<string, mixed> $meta */
    public function log(int $clinicId, int $saleId, string $action, array $meta, ?int $actorUserId, ?string $ip): void
    {
        $sql = "
            INSERT INTO sale_logs (
                clinic_id, sale_id,
                action, meta_json,
                actor_user_id, ip_address,
                created_at
            )
            VALUES (
                :clinic_id, :sale_id,
                :action, CAST(:meta_json AS JSON),
                :actor_user_id, :ip_address,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'sale_id' => $saleId,
            'action' => $action,
            'meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'actor_user_id' => $actorUserId,
            'ip_address' => $ip,
        ]);
    }

    /** @return list<array<string,mixed>> */
    public function listBySale(int $clinicId, int $saleId, int $limit = 200): array
    {
        $sql = "
            SELECT id, clinic_id, sale_id, action, meta_json, actor_user_id, ip_address, created_at
            FROM sale_logs
            WHERE clinic_id = :clinic_id
              AND sale_id = :sale_id
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'sale_id' => $saleId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }
}
