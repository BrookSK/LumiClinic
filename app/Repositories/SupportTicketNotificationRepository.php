<?php

declare(strict_types=1);

namespace App\Repositories;

final class SupportTicketNotificationRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** channel: whatsapp, email. status: sent, failed, skipped. A UI deve mascarar o destino ao exibir. */
    public function create(int $ticketId, string $channel, string $destination, string $status, ?string $error = null, int $attempts = 0): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO support_ticket_notifications (ticket_id, channel, destination, status, error, attempts, created_at)
            VALUES (:ticket_id, :channel, :destination, :status, :error, :attempts, NOW())
        ");
        $stmt->execute([
            'ticket_id' => $ticketId,
            'channel' => $channel,
            'destination' => $destination,
            'status' => $status,
            'error' => $error !== null ? mb_substr($error, 0, 500) : null,
            'attempts' => $attempts,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Registra o resultado de uma tentativa de envio e soma 1 em attempts. */
    public function markResult(int $id, string $status, ?string $error = null): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE support_ticket_notifications
            SET status = :status, error = :error, attempts = attempts + 1
            WHERE id = :id
        ");
        $stmt->execute([
            'status' => $status,
            'error' => $error !== null ? mb_substr($error, 0, 500) : null,
            'id' => $id,
        ]);
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM support_ticket_notifications WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return list<array<string,mixed>> */
    public function listByTicket(int $ticketId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM support_ticket_notifications WHERE ticket_id = :ticket_id ORDER BY id ASC');
        $stmt->execute(['ticket_id' => $ticketId]);
        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }
}
