<?php

declare(strict_types=1);

namespace App\Repositories;

final class SupportTicketEventRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** type: created, status_change, note, notification, resolution */
    public function add(int $ticketId, ?int $actorUserId, string $type, ?string $body): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO support_ticket_events (ticket_id, actor_user_id, type, body, created_at)
            VALUES (:ticket_id, :actor_user_id, :type, :body, NOW())
        ");
        $stmt->execute([
            'ticket_id' => $ticketId,
            'actor_user_id' => $actorUserId,
            'type' => $type,
            'body' => $body,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function listByTicket(int $ticketId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT e.id, e.ticket_id, e.actor_user_id, e.type, e.body, e.created_at, u.name AS actor_name
            FROM support_ticket_events e
            LEFT JOIN users u ON u.id = e.actor_user_id
            WHERE e.ticket_id = :ticket_id
            ORDER BY e.id ASC
        ");
        $stmt->execute(['ticket_id' => $ticketId]);
        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }
}
