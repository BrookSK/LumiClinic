<?php

declare(strict_types=1);

namespace App\Repositories;

final class UserNotificationRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(
        int $clinicId,
        int $userId,
        string $channel,
        string $type,
        string $title,
        string $body,
        ?string $referenceType,
        ?int $referenceId
    ): int {
        $sql = "
            INSERT INTO user_notifications (
                clinic_id, user_id,
                channel, type,
                title, body,
                reference_type, reference_id,
                read_at,
                created_at
            ) VALUES (
                :clinic_id, :user_id,
                :channel, :type,
                :title, :body,
                :reference_type, :reference_id,
                NULL,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'user_id' => $userId,
            'channel' => $channel,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'reference_type' => ($referenceType === '' ? null : $referenceType),
            'reference_id' => $referenceId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function listLatestByUser(int $clinicId, int $userId, int $limit = 20): array
    {
        $limit = max(1, min($limit, 100));

        $sql = "
            SELECT id, clinic_id, user_id, channel, type, title, body, reference_type, reference_id, read_at, created_at
            FROM user_notifications
            WHERE clinic_id = :clinic_id
              AND user_id = :user_id
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'user_id' => $userId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function markRead(int $clinicId, int $userId, int $id): void
    {
        $sql = "
            UPDATE user_notifications
            SET read_at = NOW()
            WHERE clinic_id = :clinic_id
              AND user_id = :user_id
              AND id = :id
              AND deleted_at IS NULL
              AND read_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'user_id' => $userId,
            'id' => $id,
        ]);
    }

    public function countUnread(int $clinicId, int $userId): int
    {
        $sql = "
            SELECT COUNT(*) AS c
            FROM user_notifications
            WHERE clinic_id = :clinic_id
              AND user_id = :user_id
              AND deleted_at IS NULL
              AND read_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'user_id' => $userId]);
        $row = $stmt->fetch() ?: [];

        return (int)($row['c'] ?? 0);
    }
}
