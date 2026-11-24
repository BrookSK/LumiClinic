<?php

declare(strict_types=1);

namespace App\Repositories;

final class SupportKbEntryRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(?int $sourceTicketId, string $question, string $answer, ?int $createdBy, ?string $warnings = null): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO support_kb_entries (source_ticket_id, question, answer, warnings, status, created_by, created_at)
            VALUES (:source_ticket_id, :question, :answer, :warnings, 'draft', :created_by, NOW())
        ");
        $stmt->execute([
            'source_ticket_id' => $sourceTicketId,
            'question' => $question,
            'answer' => $answer,
            'warnings' => $warnings,
            'created_by' => $createdBy,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM support_kb_entries WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findBySourceTicket(int $ticketId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM support_kb_entries WHERE source_ticket_id = :id ORDER BY id DESC LIMIT 1');
        $stmt->execute(['id' => $ticketId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return list<array<string,mixed>> */
    public function listByStatus(string $status, int $limit = 200): array
    {
        $limit = max(1, min($limit, 1000));
        $stmt = $this->pdo->prepare("SELECT * FROM support_kb_entries WHERE status = :status ORDER BY id DESC LIMIT {$limit}");
        $stmt->execute(['status' => $status]);
        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listApproved(): array
    {
        return $this->listByStatus('approved', 1000);
    }

    public function updateText(int $id, string $question, string $answer, ?string $warnings = null): void
    {
        $stmt = $this->pdo->prepare('UPDATE support_kb_entries SET question = :question, answer = :answer, warnings = :warnings WHERE id = :id');
        $stmt->execute(['question' => $question, 'answer' => $answer, 'warnings' => $warnings, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM support_kb_entries WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM support_kb_entries WHERE status = :status');
        $stmt->execute(['status' => $status]);
        return (int)$stmt->fetchColumn();
    }

    /** @param list<int> $ids */
    public function markExported(array $ids): void
    {
        foreach ($ids as $id) {
            $stmt = $this->pdo->prepare('UPDATE support_kb_entries SET exported_at = NOW() WHERE id = :id');
            $stmt->execute(['id' => (int)$id]);
        }
    }

    /** status: approved ou rejected (ou draft para reabrir). */
    public function review(int $id, string $status, ?int $reviewerId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE support_kb_entries SET status = :status, reviewed_by = :reviewed_by, reviewed_at = NOW() WHERE id = :id
        ");
        $stmt->execute(['status' => $status, 'reviewed_by' => $reviewerId, 'id' => $id]);
    }
}
