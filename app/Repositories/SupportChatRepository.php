<?php

declare(strict_types=1);

namespace App\Repositories;

final class SupportChatRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function createConversation(int $clinicId, int $userId, ?string $pagePath): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO support_chat_conversations (clinic_id, user_id, status, page_path, created_at)
            VALUES (:clinic_id, :user_id, 'open', :page_path, NOW())
        ");
        $stmt->execute(['clinic_id' => $clinicId, 'user_id' => $userId, 'page_path' => $pagePath]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Leitura como usuário da clínica: sempre filtrada por clínica e usuário dono. @return array<string,mixed>|null */
    public function findOwnConversation(int $id, int $clinicId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM support_chat_conversations
            WHERE id = :id AND clinic_id = :clinic_id AND user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId, 'user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Conversa aberta mais recente do usuário (usada para retomar o chat). @return array<string,mixed>|null */
    public function findLatestOpenForUser(int $clinicId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM support_chat_conversations
            WHERE clinic_id = :clinic_id AND user_id = :user_id AND status = 'open'
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute(['clinic_id' => $clinicId, 'user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Uso exclusivo do super admin (tela do chamado). @return array<string,mixed>|null */
    public function findConversationForAdmin(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM support_chat_conversations WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function setStatus(int $id, int $clinicId, int $userId, string $status): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE support_chat_conversations SET status = :status
            WHERE id = :id AND clinic_id = :clinic_id AND user_id = :user_id
        ");
        $stmt->execute(['status' => $status, 'id' => $id, 'clinic_id' => $clinicId, 'user_id' => $userId]);
    }

    public function touchConversation(int $id, int $clinicId, int $userId, ?string $pagePath): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE support_chat_conversations SET page_path = COALESCE(:page_path, page_path), updated_at = NOW()
            WHERE id = :id AND clinic_id = :clinic_id AND user_id = :user_id
        ");
        $stmt->execute(['page_path' => $pagePath, 'id' => $id, 'clinic_id' => $clinicId, 'user_id' => $userId]);
    }

    /** role: user, assistant, system */
    public function addMessage(int $conversationId, string $role, string $content, ?string $kbChunkIds = null): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO support_chat_messages (conversation_id, role, content, kb_chunk_ids, created_at)
            VALUES (:conversation_id, :role, :content, :kb_chunk_ids, NOW())
        ");
        $stmt->execute([
            'conversation_id' => $conversationId,
            'role' => $role,
            'content' => $content,
            'kb_chunk_ids' => $kbChunkIds,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Últimas mensagens da conversa, em ordem cronológica. O chamador deve ter validado o dono da conversa
     * (findOwnConversation) ou ser o super admin.
     *
     * @return list<array<string,mixed>>
     */
    public function listMessages(int $conversationId, int $limit = 200): array
    {
        $limit = max(1, min($limit, 500));
        $stmt = $this->pdo->prepare("
            SELECT id, conversation_id, role, content, kb_chunk_ids, created_at FROM (
                SELECT id, conversation_id, role, content, kb_chunk_ids, created_at
                FROM support_chat_messages
                WHERE conversation_id = :conversation_id
                ORDER BY id DESC
                LIMIT {$limit}
            ) m
            ORDER BY id ASC
        ");
        $stmt->execute(['conversation_id' => $conversationId]);
        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function countUserMessages(int $conversationId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM support_chat_messages WHERE conversation_id = :id AND role = 'user'");
        $stmt->execute(['id' => $conversationId]);
        return (int)$stmt->fetchColumn();
    }
}
