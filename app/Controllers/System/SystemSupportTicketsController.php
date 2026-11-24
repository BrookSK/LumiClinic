<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\SupportChatRepository;
use App\Repositories\SupportTicketEventRepository;
use App\Repositories\SupportTicketNotificationRepository;
use App\Repositories\SupportTicketRepository;
use App\Services\Support\SupportTicketService;
use App\Services\Support\SupportTicketWhatsappNotifier;

final class SystemSupportTicketsController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
            throw new \RuntimeException('Acesso negado.');
        }
    }

    private function actorId(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin();

        $filters = [
            'clinic_id' => (int)$request->input('clinic_id', 0),
            'severity' => trim((string)$request->input('severity', '')),
            'category' => trim((string)$request->input('category', '')),
            'q' => mb_substr(trim((string)$request->input('q', '')), 0, 100),
        ];
        $showAllClosed = (string)$request->input('all_closed', '') === '1';

        $repo = new SupportTicketRepository($this->container->get(\PDO::class));

        $columns = [];
        foreach (SupportTicketRepository::STATUSES as $status) {
            $f = $filters;
            if ($status === 'fechado' && !$showAllClosed) {
                $f['closed_since'] = date('Y-m-d H:i:s', strtotime('-30 days'));
            }
            $columns[$status] = $repo->listByStatus($status, $f, 100);
        }

        $saved = trim((string)$request->input('saved', ''));
        $error = trim((string)$request->input('error', ''));

        return $this->view('system/support/tickets_index', [
            'columns' => $columns,
            'counts' => $repo->countsByStatus(),
            'clinics' => $repo->listClinicsWithTickets(),
            'filters' => $filters,
            'show_all_closed' => $showAllClosed,
            'success' => $saved !== '' ? 'Salvo com sucesso.' : null,
            'error' => $error !== '' ? $error : null,
        ]);
    }

    public function show(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/sys/support/tickets');
        }

        $pdo = $this->container->get(\PDO::class);
        $ticket = (new SupportTicketRepository($pdo))->findById($id);
        if ($ticket === null) {
            return $this->redirect('/sys/support/tickets?error=' . urlencode('Chamado não encontrado.'));
        }

        $context = json_decode((string)($ticket['context_json'] ?? ''), true);
        if (!is_array($context)) {
            $context = [];
        }

        $transcript = [];
        $conversationId = (int)($ticket['conversation_id'] ?? 0);
        if ($conversationId > 0) {
            foreach ((new SupportChatRepository($pdo))->listMessages($conversationId, 200) as $m) {
                $transcript[] = ['role' => (string)$m['role'], 'content' => (string)$m['content'], 'at' => (string)$m['created_at']];
            }
        }
        if ($transcript === [] && isset($context['transcript']) && is_array($context['transcript'])) {
            $transcript = $context['transcript'];
        }

        $saved = trim((string)$request->input('saved', ''));
        $error = trim((string)$request->input('error', ''));

        return $this->view('system/support/tickets_show', [
            'ticket' => $ticket,
            'context' => $context,
            'transcript' => $transcript,
            'events' => (new SupportTicketEventRepository($pdo))->listByTicket($id),
            'notifications' => (new SupportTicketNotificationRepository($pdo))->listByTicket($id),
            'success' => $saved !== '' ? 'Salvo com sucesso.' : null,
            'error' => $error !== '' ? $error : null,
        ]);
    }

    public function status(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        $status = trim((string)$request->input('status', ''));

        try {
            (new SupportTicketService($this->container))->changeStatus($id, $status, $this->actorId(), $request->ip(), $request->header('user-agent'));
        } catch (\RuntimeException $e) {
            return $this->redirect($this->back($id, 'error', $e->getMessage()));
        }

        return $this->redirect($this->back($id, 'saved', '1'));
    }

    public function note(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);

        try {
            (new SupportTicketService($this->container))->addNote($id, (string)$request->input('note', ''), $this->actorId());
        } catch (\RuntimeException $e) {
            return $this->redirect($this->back($id, 'error', $e->getMessage()));
        }

        return $this->redirect($this->back($id, 'saved', '1'));
    }

    public function resolve(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);

        try {
            (new SupportTicketService($this->container))->resolve($id, (string)$request->input('resolution', ''), $this->actorId(), $request->ip(), $request->header('user-agent'));
        } catch (\RuntimeException $e) {
            return $this->redirect($this->back($id, 'error', $e->getMessage()));
        }

        return $this->redirect($this->back($id, 'saved', '1'));
    }

    /** Reenvia a notificação do chamado: apenas aos números que falharam (mode=failed) ou a todos os ativos (mode=all). */
    public function resendNotification(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        $onlyFailed = (string)$request->input('mode', 'failed') !== 'all';

        try {
            $result = (new SupportTicketWhatsappNotifier($this->container))->resend($id, $onlyFailed);
        } catch (\RuntimeException $e) {
            return $this->redirect($this->back($id, 'error', $e->getMessage()));
        }

        if ($result['sent'] === 0 && $result['failed'] === 0) {
            return $this->redirect($this->back($id, 'error', 'Nada a reenviar: nenhum número com falha ou nenhum número ativo.'));
        }
        if ($result['sent'] === 0) {
            return $this->redirect($this->back($id, 'error', 'Nenhuma mensagem foi entregue. Verifique a conexão em WhatsApp do suporte.'));
        }

        return $this->redirect($this->back($id, 'saved', '1'));
    }

    private function back(int $id, string $key, string $value): string
    {
        if ($id <= 0) {
            return '/sys/support/tickets?' . $key . '=' . urlencode($value);
        }
        return '/sys/support/tickets/show?id=' . $id . '&' . $key . '=' . urlencode($value);
    }
}
