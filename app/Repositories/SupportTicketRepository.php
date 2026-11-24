<?php

declare(strict_types=1);

namespace App\Repositories;

final class SupportTicketRepository
{
    public const STATUSES = ['novo', 'em_analise', 'aguardando_usuario', 'resolvido', 'fechado'];
    public const SEVERITIES = ['baixa', 'media', 'alta', 'critica'];
    public const CATEGORIES = ['bug', 'duvida', 'acesso', 'financeiro', 'integracao', 'outro'];

    public function __construct(private readonly \PDO $pdo) {}

    /**
     * Cria o chamado e devolve o id. O public_code (LC-000123) é gerado a partir do id.
     *
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO support_tickets (
                public_code, clinic_id, user_id, conversation_id, title, summary, category, severity, status,
                context_json, steps_tried, expected, actual, page_path, created_at
            ) VALUES (
                :public_code, :clinic_id, :user_id, :conversation_id, :title, :summary, :category, :severity, 'novo',
                :context_json, :steps_tried, :expected, :actual, :page_path, NOW()
            )
        ");
        $stmt->execute([
            'public_code' => 'T' . bin2hex(random_bytes(8)),
            'clinic_id' => (int)$data['clinic_id'],
            'user_id' => (int)$data['user_id'],
            'conversation_id' => isset($data['conversation_id']) ? (int)$data['conversation_id'] : null,
            'title' => (string)$data['title'],
            'summary' => $data['summary'] ?? null,
            'category' => (string)($data['category'] ?? 'outro'),
            'severity' => (string)($data['severity'] ?? 'media'),
            'context_json' => $data['context_json'] ?? null,
            'steps_tried' => $data['steps_tried'] ?? null,
            'expected' => $data['expected'] ?? null,
            'actual' => $data['actual'] ?? null,
            'page_path' => $data['page_path'] ?? null,
        ]);

        $id = (int)$this->pdo->lastInsertId();

        $upd = $this->pdo->prepare('UPDATE support_tickets SET public_code = :code WHERE id = :id');
        $upd->execute(['code' => sprintf('LC-%06d', $id), 'id' => $id]);

        return $id;
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT t.*, c.name AS clinic_name, u.name AS user_name, u.email AS user_email
            FROM support_tickets t
            LEFT JOIN clinics c ON c.id = t.clinic_id
            LEFT JOIN users u ON u.id = t.user_id
            WHERE t.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Filtros aceitos: clinic_id (int), severity, category, q (texto), date_from, date_to (Y-m-d),
     * closed_since (Y-m-d H:i:s, só vale para o status fechado).
     *
     * @param array<string,mixed> $filters
     * @return list<array<string,mixed>>
     */
    public function listByStatus(string $status, array $filters = [], int $limit = 100): array
    {
        $limit = max(1, min($limit, 500));
        $where = ['t.status = :status'];
        $params = ['status' => $status];

        $clinicId = (int)($filters['clinic_id'] ?? 0);
        if ($clinicId > 0) {
            $where[] = 't.clinic_id = :clinic_id';
            $params['clinic_id'] = $clinicId;
        }

        $severity = (string)($filters['severity'] ?? '');
        if ($severity !== '' && in_array($severity, self::SEVERITIES, true)) {
            $where[] = 't.severity = :severity';
            $params['severity'] = $severity;
        }

        $category = (string)($filters['category'] ?? '');
        if ($category !== '' && in_array($category, self::CATEGORIES, true)) {
            $where[] = 't.category = :category';
            $params['category'] = $category;
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $where[] = '(t.title LIKE :q1 OR t.summary LIKE :q2 OR t.public_code LIKE :q3 OR c.name LIKE :q4)';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
        }

        $from = (string)($filters['date_from'] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) === 1) {
            $where[] = 't.created_at >= :date_from';
            $params['date_from'] = $from . ' 00:00:00';
        }
        $to = (string)($filters['date_to'] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) === 1) {
            $where[] = 't.created_at <= :date_to';
            $params['date_to'] = $to . ' 23:59:59';
        }

        $closedSince = (string)($filters['closed_since'] ?? '');
        if ($status === 'fechado' && $closedSince !== '') {
            $where[] = 'COALESCE(t.closed_at, t.updated_at, t.created_at) >= :closed_since';
            $params['closed_since'] = $closedSince;
        }

        $sql = "
            SELECT t.id, t.public_code, t.clinic_id, t.user_id, t.title, t.category, t.severity, t.status,
                   t.assigned_to_name, t.created_at, t.updated_at, c.name AS clinic_name,
                   (t.context_json LIKE '%\"impersonated_session\":true%') AS is_impersonated
            FROM support_tickets t
            LEFT JOIN clinics c ON c.id = t.clinic_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY t.id DESC
            LIMIT {$limit}
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE support_tickets
            SET status = :status,
                closed_at = CASE WHEN :status_closed = 'fechado' THEN NOW() ELSE NULL END
            WHERE id = :id
        ");
        $stmt->execute(['status' => $status, 'status_closed' => $status, 'id' => $id]);
    }

    public function setResolution(int $id, string $resolutionText): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE support_tickets
            SET resolution_text = :resolution_text, status = 'resolvido', resolved_at = NOW(), closed_at = NULL
            WHERE id = :id
        ");
        $stmt->execute(['resolution_text' => $resolutionText, 'id' => $id]);
    }

    public function setAssignedName(int $id, ?string $name): void
    {
        $stmt = $this->pdo->prepare('UPDATE support_tickets SET assigned_to_name = :name WHERE id = :id');
        $stmt->execute(['name' => $name, 'id' => $id]);
    }

    /** @return array<string,int> status => total */
    public function countsByStatus(): array
    {
        $out = array_fill_keys(self::STATUSES, 0);
        $rows = $this->pdo->query('SELECT status, COUNT(*) AS total FROM support_tickets GROUP BY status')->fetchAll();
        foreach ($rows as $r) {
            $out[(string)$r['status']] = (int)$r['total'];
        }
        return $out;
    }

    /** Chamados que ainda pedem ação da equipe (novo + em análise). */
    public function countOpen(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status IN ('novo','em_analise')");
        return (int)$stmt->fetchColumn();
    }

    /** Quantos chamados a clínica abriu desde a data informada (Y-m-d H:i:s). */
    public function countByClinicSince(int $clinicId, string $since): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM support_tickets WHERE clinic_id = :clinic_id AND created_at >= :since');
        $stmt->execute(['clinic_id' => $clinicId, 'since' => $since]);
        return (int)$stmt->fetchColumn();
    }

    /** @return list<array{id:int,name:string}> */
    public function listClinicsWithTickets(): array
    {
        $stmt = $this->pdo->query("
            SELECT c.id, c.name
            FROM clinics c
            WHERE c.id IN (SELECT DISTINCT clinic_id FROM support_tickets)
            ORDER BY c.name ASC
        ");
        /** @var list<array{id:int,name:string}> */
        return $stmt->fetchAll();
    }
}
