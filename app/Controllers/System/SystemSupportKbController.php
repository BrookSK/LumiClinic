<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\AuditLogRepository;
use App\Repositories\SupportKbEntryRepository;
use App\Repositories\SupportTicketRepository;
use App\Services\Support\SupportKbDraftService;
use App\Services\Support\SupportKnowledgeBase;

/**
 * Revisão das resoluções que podem virar resposta do assistente. Nada chega à base sem Aprovar aqui.
 */
final class SystemSupportKbController extends Controller
{
    private const TABS = ['draft' => 'Rascunhos', 'approved' => 'Aprovadas', 'rejected' => 'Rejeitadas'];

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

    private function repo(): SupportKbEntryRepository
    {
        return new SupportKbEntryRepository($this->container->get(\PDO::class));
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin();

        $tab = (string)$request->input('tab', 'draft');
        if (!isset(self::TABS[$tab])) {
            $tab = 'draft';
        }

        $repo = $this->repo();
        $counts = [];
        foreach (array_keys(self::TABS) as $status) {
            $counts[$status] = $repo->countByStatus($status);
        }

        $entries = $repo->listByStatus($tab, 200);
        $ticketCodes = [];
        $tickets = new SupportTicketRepository($this->container->get(\PDO::class));
        foreach ($entries as $en) {
            $tid = (int)($en['source_ticket_id'] ?? 0);
            if ($tid > 0 && !isset($ticketCodes[$tid])) {
                $t = $tickets->findById($tid);
                if ($t !== null) {
                    $ticketCodes[$tid] = (string)$t['public_code'];
                }
            }
        }

        $saved = trim((string)$request->input('saved', ''));
        $error = trim((string)$request->input('error', ''));

        return $this->view('system/support/kb', [
            'tab' => $tab,
            'tabs' => self::TABS,
            'counts' => $counts,
            'entries' => $entries,
            'ticket_codes' => $ticketCodes,
            'success' => $saved !== '' ? $saved : null,
            'error' => $error !== '' ? $error : null,
        ]);
    }

    /** Gera (ou atualiza) o rascunho de um chamado já resolvido. */
    public function generate(Request $request)
    {
        $this->ensureSuperAdmin();

        $ticketId = (int)$request->input('ticket_id', 0);
        try {
            $entryId = (new SupportKbDraftService($this->container))->createDraftFromTicket($ticketId, $this->actorId());
        } catch (\RuntimeException $e) {
            return $this->redirect('/sys/support/tickets/show?id=' . $ticketId . '&error=' . urlencode($e->getMessage()));
        }

        $this->audit($request, 'support.kb.draft', $entryId, ['ticket_id' => $ticketId]);
        return $this->redirect('/sys/support/kb?tab=draft&saved=' . urlencode('Sugestão gerada. Revise antes de aprovar.'));
    }

    public function approve(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        $entry = $this->repo()->findById($id);
        if ($entry === null) {
            return $this->redirect('/sys/support/kb?error=' . urlencode('Entrada não encontrada.'));
        }

        // O texto que está no formulário vale: salva, confere e só então aprova.
        $q = $this->cleanQuestion((string)$request->input('question', (string)$entry['question']));
        $a = $this->cleanAnswer((string)$request->input('answer', (string)$entry['answer']));
        if ($q === '' || $a === '') {
            return $this->redirect($this->back((string)$entry['status'], 'error', 'Pergunta e resposta são obrigatórias.'));
        }

        $problems = SupportKbDraftService::findProblems($q . "\n" . $a);
        $this->repo()->updateText($id, $q, $a, $problems === [] ? null : implode("\n", $problems));
        if ($problems !== []) {
            return $this->redirect($this->back((string)$entry['status'], 'error', 'Não foi possível aprovar: ' . implode(' ', $problems)));
        }

        $this->repo()->review($id, 'approved', $this->actorId());
        (new SupportKnowledgeBase($this->container))->invalidateCache();
        $this->audit($request, 'support.kb.approve', $id, []);

        return $this->redirect('/sys/support/kb?tab=approved&saved=' . urlencode('Entrada aprovada. O assistente já pode usá-la.'));
    }

    public function reject(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        $entry = $this->repo()->findById($id);
        if ($entry === null) {
            return $this->redirect('/sys/support/kb?error=' . urlencode('Entrada não encontrada.'));
        }

        $this->repo()->review($id, 'rejected', $this->actorId());
        (new SupportKnowledgeBase($this->container))->invalidateCache();
        $this->audit($request, 'support.kb.reject', $id, []);

        return $this->redirect('/sys/support/kb?tab=rejected&saved=' . urlencode('Entrada rejeitada. O assistente não a usa.'));
    }

    /** Salva a edição de um rascunho ou entrada rejeitada e recalcula os avisos. Entradas aprovadas não são editáveis. */
    public function save(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        $entry = $this->repo()->findById($id);
        if ($entry === null) {
            return $this->redirect('/sys/support/kb?error=' . urlencode('Entrada não encontrada.'));
        }
        $status = (string)$entry['status'];
        if ($status === 'approved') {
            return $this->redirect($this->back($status, 'error', 'Entradas aprovadas não podem ser editadas. Rejeite a entrada para editar.'));
        }

        $q = $this->cleanQuestion((string)$request->input('question', ''));
        $a = $this->cleanAnswer((string)$request->input('answer', ''));
        if ($q === '' || $a === '') {
            return $this->redirect($this->back($status, 'error', 'Pergunta e resposta são obrigatórias.'));
        }

        $problems = SupportKbDraftService::findProblems($q . "\n" . $a);
        $this->repo()->updateText($id, $q, $a, $problems === [] ? null : implode("\n", $problems));
        $this->audit($request, 'support.kb.edit', $id, []);

        if ($problems !== []) {
            return $this->redirect($this->back($status, 'error', 'Edição salva, mas ainda há problemas: ' . implode(' ', $problems)));
        }
        return $this->redirect($this->back($status, 'saved', 'Edição salva. Nenhum problema encontrado.'));
    }

    public function delete(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        $entry = $this->repo()->findById($id);
        if ($entry === null) {
            return $this->redirect('/sys/support/kb?error=' . urlencode('Entrada não encontrada.'));
        }

        $this->repo()->delete($id);
        (new SupportKnowledgeBase($this->container))->invalidateCache();
        $this->audit($request, 'support.kb.delete', $id, ['status' => (string)$entry['status']]);

        return $this->redirect($this->back((string)$entry['status'], 'saved', 'Entrada excluída.'));
    }

    /** Baixa o markdown das entradas aprovadas (formato de docs/knowledge-base/99-faq-resolucoes.md), só para versionar no repositório. */
    public function export(Request $request)
    {
        $this->ensureSuperAdmin();

        $repo = $this->repo();
        $approved = $repo->listApproved();

        $md = "---\n"
            . "title: Resoluções de chamados aprovadas (arquivo gerenciado automaticamente)\n"
            . "routes:\n"
            . "  - /tutorial/sistema\n"
            . "---\n\n";
        $ids = [];
        foreach (array_reverse($approved) as $en) {
            $q = trim((string)preg_replace('/\s+/u', ' ', (string)$en['question']));
            $a = trim((string)preg_replace('/^#+\s*/m', '', (string)$en['answer']));
            if ($q === '' || $a === '') {
                continue;
            }
            $md .= '## ' . $q . "\n\n" . $a . "\n\n<!-- origem: chamado resolvido -->\n\n";
            $ids[] = (int)$en['id'];
        }
        if ($ids === []) {
            $md .= "<!-- Entradas aprovadas de chamados resolvidos serao exportadas aqui -->\n";
        }

        $repo->markExported($ids);
        $this->audit($request, 'support.kb.export', 0, ['count' => count($ids)]);

        return Response::raw($md, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="99-faq-resolucoes.md"',
        ]);
    }

    private function cleanQuestion(string $v): string
    {
        return mb_substr(trim((string)preg_replace('/\s+/u', ' ', $v)), 0, 300);
    }

    private function cleanAnswer(string $v): string
    {
        $v = str_replace(["\r\n", "\r"], "\n", $v);
        return mb_substr(trim($v), 0, 2000);
    }

    private function back(string $status, string $key, string $value): string
    {
        $tab = isset(self::TABS[$status]) ? $status : 'draft';
        return '/sys/support/kb?tab=' . $tab . '&' . $key . '=' . urlencode($value);
    }

    /** @param array<string,mixed> $meta */
    private function audit(Request $request, string $action, int $entityId, array $meta): void
    {
        try {
            (new AuditLogRepository($this->container->get(\PDO::class)))->log(
                $this->actorId(),
                null,
                $action,
                $meta,
                $request->ip(),
                null,
                'support_kb_entry',
                $entityId > 0 ? $entityId : null,
                $request->header('user-agent')
            );
        } catch (\Throwable $e) {
            error_log('[SupportKb] falha ao gravar auditoria: ' . $e->getMessage());
        }
    }
}
