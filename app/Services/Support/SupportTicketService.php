<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\SecurityRateLimitRepository;
use App\Repositories\SupportChatRepository;
use App\Repositories\SupportTicketEventRepository;
use App\Repositories\SupportTicketRepository;
use App\Repositories\SystemErrorLogRepository;
use App\Services\Auth\AuthService;

final class SupportTicketService
{
    public const MAX_TICKETS_PER_CLINIC_PER_HOUR = 5;

    private SupportTicketNotifierInterface $notifier;
    private SupportTextSanitizer $sanitizer;

    public function __construct(private readonly Container $container, ?SupportTicketNotifierInterface $notifier = null)
    {
        if ($notifier === null && $this->container->has('support.ticket_notifier')) {
            $candidate = $this->container->get('support.ticket_notifier');
            if ($candidate instanceof SupportTicketNotifierInterface) {
                $notifier = $candidate;
            }
        }
        // Padrão: notificação por WhatsApp/e-mail aos desenvolvedores (FEAT-006). SupportNullTicketNotifier segue disponível para testes.
        $this->notifier = $notifier ?? new SupportTicketWhatsappNotifier($this->container);
        $this->sanitizer = new SupportTextSanitizer();
    }

    /**
     * Cria um chamado a partir da conversa do chatbot. Clínica e usuário vêm SEMPRE da sessão; do cliente só
     * entram os textos do chamado e, em $clientContext, page_path, referrer_path, user_agent, client_time e kb_chunk_ids.
     *
     * @param array<string,mixed> $ticketData title, summary, category, severity, steps_tried, expected, actual
     * @param array<string,mixed> $clientContext
     * @return array{id:int,public_code:string}
     * @throws \RuntimeException conversa inválida, contexto inválido ou limite de chamados atingido
     */
    public function createFromBot(array $ticketData, int $conversationId, array $clientContext = []): array
    {
        $pdo = $this->container->get(\PDO::class);
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $clinicId <= 0 || $userId === null || $userId <= 0) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $chat = new SupportChatRepository($pdo);
        $conversation = $chat->findOwnConversation($conversationId, $clinicId, $userId);
        if ($conversation === null) {
            throw new \RuntimeException('Conversa não encontrada.');
        }
        if ((string)$conversation['status'] === 'ticketed') {
            throw new \RuntimeException('Esta conversa já gerou um chamado.');
        }

        $limit = (new SecurityRateLimitRepository($pdo))->hit(
            'support.ticket.clinic',
            (string)$clinicId,
            3600,
            self::MAX_TICKETS_PER_CLINIC_PER_HOUR,
            3600
        );
        if (!$limit['allowed']) {
            throw new \RuntimeException('Limite de chamados por hora atingido. Tente novamente mais tarde.');
        }

        $messages = $chat->listMessages($conversationId, 100);
        $transcript = [];
        foreach ($messages as $m) {
            $transcript[] = [
                'role' => (string)$m['role'],
                'content' => $this->clean((string)$m['content'], 2000),
                'at' => (string)$m['created_at'],
            ];
        }

        $kbChunkIds = [];
        if (isset($clientContext['kb_chunk_ids']) && is_array($clientContext['kb_chunk_ids'])) {
            foreach ($clientContext['kb_chunk_ids'] as $cid) {
                $cid = mb_substr(trim((string)$cid), 0, 80);
                if ($cid !== '') {
                    $kbChunkIds[] = $cid;
                }
            }
            $kbChunkIds = array_slice(array_values(array_unique($kbChunkIds)), 0, 20);
        }

        $pagePath = $this->pathWithQueryKeys((string)($clientContext['page_path'] ?? ($conversation['page_path'] ?? '')));
        $context = $this->buildContext($pdo, $clinicId, $userId, $clientContext, $pagePath, $kbChunkIds, $auth->isImpersonating());

        $title = $this->clean((string)($ticketData['title'] ?? ''), 190);
        if ($title === '') {
            $title = 'Chamado de suporte';
        }

        $category = (string)($ticketData['category'] ?? 'outro');
        if (!in_array($category, SupportTicketRepository::CATEGORIES, true)) {
            $category = 'outro';
        }
        $severity = (string)($ticketData['severity'] ?? 'media');
        if (!in_array($severity, SupportTicketRepository::SEVERITIES, true)) {
            $severity = 'media';
        }

        $repo = new SupportTicketRepository($pdo);
        $ticketId = $repo->create([
            'clinic_id' => $clinicId,
            'user_id' => $userId,
            'conversation_id' => $conversationId,
            'title' => $title,
            'summary' => $this->clean((string)($ticketData['summary'] ?? ''), 4000),
            'category' => $category,
            'severity' => $severity,
            'context_json' => json_encode(
                $context + ['transcript' => $transcript],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
            ) ?: null,
            'steps_tried' => $this->clean((string)($ticketData['steps_tried'] ?? ''), 2000),
            'expected' => $this->clean((string)($ticketData['expected'] ?? ''), 2000),
            'actual' => $this->clean((string)($ticketData['actual'] ?? ''), 2000),
            'page_path' => $pagePath !== '' ? $pagePath : null,
        ]);

        $chat->setStatus($conversationId, $clinicId, $userId, 'ticketed');

        $events = new SupportTicketEventRepository($pdo);
        $events->add($ticketId, $userId, 'created', 'Chamado criado pelo assistente de suporte.');

        $ticket = $repo->findById($ticketId);
        if ($ticket === null) {
            throw new \RuntimeException('Falha ao criar o chamado.');
        }

        try {
            $this->notifier->notifyCreated($ticket + [
                'context' => $context,
                'transcript' => $transcript,
            ]);
        } catch (\Throwable $e) {
            // A criação do chamado não pode falhar por causa da notificação.
            $events->add($ticketId, null, 'notification', 'Falha ao notificar a equipe: ' . mb_substr($e->getMessage(), 0, 300));
        }

        return ['id' => $ticketId, 'public_code' => (string)$ticket['public_code']];
    }

    public function changeStatus(int $ticketId, string $status, int $actorUserId, ?string $ip = null, ?string $userAgent = null): void
    {
        if (!in_array($status, SupportTicketRepository::STATUSES, true)) {
            throw new \RuntimeException('Status inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new SupportTicketRepository($pdo);
        $ticket = $repo->findById($ticketId);
        if ($ticket === null) {
            throw new \RuntimeException('Chamado não encontrado.');
        }

        $old = (string)$ticket['status'];
        if ($old === $status) {
            return;
        }

        $repo->updateStatus($ticketId, $status);
        (new SupportTicketEventRepository($pdo))->add($ticketId, $actorUserId, 'status_change', $old . ' -> ' . $status);
        $this->audit($pdo, $actorUserId, 'support.ticket.status_change', $ticket, ['from' => $old, 'to' => $status], $ip, $userAgent);
    }

    public function addNote(int $ticketId, string $note, int $actorUserId): void
    {
        $note = $this->clean($note, 4000);
        if ($note === '') {
            throw new \RuntimeException('Informe a anotação.');
        }

        $pdo = $this->container->get(\PDO::class);
        if ((new SupportTicketRepository($pdo))->findById($ticketId) === null) {
            throw new \RuntimeException('Chamado não encontrado.');
        }
        (new SupportTicketEventRepository($pdo))->add($ticketId, $actorUserId, 'note', $note);
    }

    public function resolve(int $ticketId, string $resolution, int $actorUserId, ?string $ip = null, ?string $userAgent = null): void
    {
        $resolution = $this->clean($resolution, 4000);
        if ($resolution === '') {
            throw new \RuntimeException('Informe a resolução.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new SupportTicketRepository($pdo);
        $ticket = $repo->findById($ticketId);
        if ($ticket === null) {
            throw new \RuntimeException('Chamado não encontrado.');
        }

        $old = (string)$ticket['status'];
        $repo->setResolution($ticketId, $resolution);
        (new SupportTicketEventRepository($pdo))->add($ticketId, $actorUserId, 'resolution', $resolution);
        $this->audit($pdo, $actorUserId, 'support.ticket.resolve', $ticket, ['from' => $old, 'to' => 'resolvido'], $ip, $userAgent);
    }

    public function close(int $ticketId, int $actorUserId, ?string $ip = null, ?string $userAgent = null): void
    {
        $this->changeStatus($ticketId, 'fechado', $actorUserId, $ip, $userAgent);
    }

    /**
     * @param list<string> $kbChunkIds
     * @param array<string,mixed> $clientContext
     * @return array<string,mixed>
     */
    private function buildContext(\PDO $pdo, int $clinicId, int $userId, array $clientContext, string $pagePath, array $kbChunkIds, bool $impersonating): array
    {
        $stmt = $pdo->prepare('SELECT name FROM clinics WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $clinicId]);
        $clinicName = (string)($stmt->fetchColumn() ?: '');

        $stmt = $pdo->prepare('SELECT name, email, phone FROM users WHERE id = :id AND clinic_id = :clinic_id LIMIT 1');
        $stmt->execute(['id' => $userId, 'clinic_id' => $clinicId]);
        $user = $stmt->fetch() ?: [];

        $roleCodes = [];
        if (isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes'])) {
            foreach ($_SESSION['role_codes'] as $rc) {
                $roleCodes[] = mb_substr((string)$rc, 0, 64);
            }
        }

        $permissions = [];
        $perm = $_SESSION['permissions'] ?? [];
        if (is_array($perm)) {
            $list = array_key_exists('allow', $perm) && is_array($perm['allow']) ? $perm['allow'] : $perm;
            foreach ($list as $code) {
                if (is_string($code)) {
                    $permissions[] = mb_substr($code, 0, 80);
                }
            }
        }
        sort($permissions);

        $recentErrors = [];
        try {
            $rows = (new SystemErrorLogRepository($pdo))->listRecentByUser($userId, 60, 5);
            foreach ($rows as $r) {
                $recentErrors[] = [
                    'status' => (int)$r['status_code'],
                    'method' => (string)$r['method'],
                    'path' => $this->pathWithQueryKeys((string)$r['path']),
                    'message' => $this->clean((string)$r['message'], 300),
                    'at' => (string)$r['created_at'],
                ];
            }
        } catch (\Throwable $e) {
            // Tabela de logs indisponível não impede o chamado.
        }

        return [
            'clinic' => ['id' => $clinicId, 'name' => $clinicName],
            'user' => [
                'id' => $userId,
                'name' => (string)($user['name'] ?? ''),
                'email' => (string)($user['email'] ?? ''),
                'phone' => (string)($user['phone'] ?? ''),
            ],
            'role_codes' => $roleCodes,
            'permission_count' => count($permissions),
            'permissions' => $permissions,
            'impersonated_session' => $impersonating,
            'page_path' => $pagePath,
            'referrer_path' => $this->pathWithQueryKeys((string)($clientContext['referrer_path'] ?? '')),
            'user_agent' => mb_substr(trim((string)($clientContext['user_agent'] ?? '')), 0, 300),
            'client_time' => mb_substr(trim((string)($clientContext['client_time'] ?? '')), 0, 40),
            'server_time' => date('Y-m-d H:i:s'),
            'app_env' => (string)(getenv('APP_ENV') ?: 'local'),
            'recent_errors' => $recentErrors,
            'kb_chunk_ids' => $kbChunkIds,
        ];
    }

    /** Mantém só o caminho e os NOMES dos parâmetros da URL (valores são descartados). */
    private function pathWithQueryKeys(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        $path = (string)(parse_url($url, PHP_URL_PATH) ?? '');
        if ($path === '' || $path[0] !== '/') {
            return '';
        }
        $path = mb_substr($path, 0, 200);

        $query = (string)(parse_url($url, PHP_URL_QUERY) ?? '');
        if ($query !== '') {
            parse_str($query, $params);
            $keys = [];
            foreach (array_keys($params) as $k) {
                $k = preg_replace('/[^A-Za-z0-9_\-\[\]]/', '', (string)$k);
                if ($k !== '' && $k !== null) {
                    $keys[] = $k;
                }
            }
            if ($keys !== []) {
                $path .= '?' . implode(',', array_slice($keys, 0, 10));
            }
        }

        return mb_substr($path, 0, 255);
    }

    private function clean(string $text, int $max): string
    {
        $text = trim($this->sanitizer->redactSecrets($text));
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max) : $text;
    }

    /**
     * @param array<string,mixed> $ticket
     * @param array<string,mixed> $meta
     */
    private function audit(\PDO $pdo, int $actorUserId, string $action, array $ticket, array $meta, ?string $ip, ?string $userAgent): void
    {
        $meta['public_code'] = (string)$ticket['public_code'];
        $meta['ticket_clinic_id'] = (int)$ticket['clinic_id'];
        (new AuditLogRepository($pdo))->log($actorUserId, null, $action, $meta, $ip, null, 'support_ticket', (int)$ticket['id'], $userAgent);
    }
}
