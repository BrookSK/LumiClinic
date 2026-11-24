<?php

declare(strict_types=1);

namespace App\Controllers\Support;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\SupportChatRepository;
use App\Services\Auth\AuthService;
use App\Services\Support\SupportBotService;
use App\Services\System\SystemSettingsService;

/**
 * Endpoints JSON do assistente de suporte para usuários de clínica.
 * clinic_id e user_id vêm sempre da sessão, nunca da requisição.
 */
final class SupportChatController extends Controller
{
    /** @var array<string,list<string>> prefixo da página => sugestões */
    private const CHIPS = [
        '/schedule' => ['Como agendar um paciente?', 'Como cancelar ou reagendar?', 'Como bloquear um horário?'],
        '/patients' => ['Como cadastrar um paciente?', 'Como anexar um documento?', 'Como dar acesso ao portal?'],
        '/finance' => ['Como registrar um pagamento?', 'Como lançar uma conta a pagar?', 'Como ver o fluxo de caixa?'],
        '/stock' => ['Como dar entrada no estoque?', 'Como fazer um inventário?', 'Como criar um alerta de estoque?'],
        '/settings' => ['Como conectar o WhatsApp?', 'Como criar um usuário?', 'Como ajustar permissões?'],
        '/medical-records' => ['Como registrar um atendimento?', 'Como usar modelos de prontuário?'],
    ];
    private const DEFAULT_CHIPS = ['Como agendar um paciente?', 'Como cadastrar um paciente?', 'Como conectar o WhatsApp?'];

    public function bootstrap(Request $request): Response
    {
        $denied = $this->guard();
        if ($denied !== null) {
            return $denied;
        }

        $bot = new SupportBotService($this->container);
        $settings = new SystemSettingsService($this->container);
        $pagePath = SupportBotService::normalizePagePath((string)$request->input('page_path', ''));

        $enabled = $bot->isEnabled();
        $first = $this->firstName();

        $wa = '';
        try {
            $digits = preg_replace('/\D+/', '', (string)($settings->getSupportSettings()['support_whatsapp_number'] ?? ''));
            if ($digits !== '') {
                $wa = 'https://wa.me/' . $digits . '?text=' . rawurlencode('Olá! Preciso de ajuda com o sistema.');
            }
        } catch (\Throwable $e) {
            $wa = '';
        }

        return Response::json([
            'ok' => true,
            'enabled' => $enabled,
            'greeting' => 'Olá' . ($first !== '' ? ', ' . $first : '') . '! Sou o assistente do LumiClinic. Pergunte como usar qualquer tela do sistema. Se eu não souber, abro um chamado para a nossa equipe.',
            'chips' => $this->chipsFor($pagePath),
            'whatsapp_url' => $wa,
            'guide_url' => '/tutorial/guia-completo',
            'max_chars' => SupportBotService::MAX_MESSAGE_CHARS,
            'csrf' => (string)($_SESSION['_csrf'] ?? ''),
        ]);
    }

    public function message(Request $request): Response
    {
        $denied = $this->guard();
        if ($denied !== null) {
            return $denied;
        }

        $message = $request->input('message', '');
        if (!is_string($message) || trim($message) === '') {
            return Response::json(['ok' => false, 'error' => 'Escreva uma mensagem.'], 422);
        }

        $conversationId = $request->input('conversation_id', null);
        $conversationId = (is_int($conversationId) || (is_string($conversationId) && ctype_digit($conversationId))) ? (int)$conversationId : null;

        $pagePath = SupportBotService::normalizePagePath((string)$request->input('page_path', ''));
        $force = filter_var($request->input('force_ticket', false), FILTER_VALIDATE_BOOLEAN);

        $referer = (string)$request->header('referer', '');
        $refPath = $referer !== '' ? (string)(parse_url($referer, PHP_URL_PATH) ?? '') : '';
        $clientTime = $request->input('client_time', '');

        $bot = new SupportBotService($this->container);
        try {
            $conversationId = $bot->openConversation($conversationId, $pagePath);
            $out = $bot->handleMessage($conversationId, $message, $pagePath, $force, [
                'referrer_path' => SupportBotService::normalizePagePath($refPath),
                'user_agent' => mb_substr((string)$request->header('user-agent', ''), 0, 300),
                'client_time' => is_string($clientTime) ? mb_substr($clientTime, 0, 40) : '',
            ]);
        } catch (\PDOException $e) {
            // PDOException estende RuntimeException: nunca repassar o texto do banco ao navegador.
            error_log('[SupportChat] erro de banco: ' . get_class($e));
            return Response::json(['ok' => false, 'error' => 'Não foi possível processar sua mensagem agora.'], 500);
        } catch (\RuntimeException $e) {
            return Response::json(['ok' => false, 'error' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            error_log('[SupportChat] erro inesperado: ' . get_class($e));
            return Response::json(['ok' => false, 'error' => 'Não foi possível processar sua mensagem agora.'], 500);
        }

        $payload = [
            'ok' => true,
            'conversation_id' => $conversationId,
            'reply' => $out['reply'],
            'action' => $out['action'],
        ];
        if ($out['ticket_code'] !== null) {
            $payload['ticket_code'] = $out['ticket_code'];
        }
        return Response::json($payload);
    }

    public function history(Request $request): Response
    {
        $denied = $this->guard();
        if ($denied !== null) {
            return $denied;
        }

        $auth = new AuthService($this->container);
        $clinicId = (int)$auth->clinicId();
        $userId = (int)$auth->userId();

        $id = $request->input('conversation_id', '');
        if (!(is_int($id) || (is_string($id) && ctype_digit($id))) || (int)$id <= 0) {
            return Response::json(['ok' => false, 'error' => 'Conversa inválida.'], 422);
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new SupportChatRepository($pdo);
        $conv = $repo->findOwnConversation((int)$id, $clinicId, $userId);
        if ($conv === null) {
            return Response::json(['ok' => false, 'error' => 'Conversa não encontrada.'], 404);
        }

        $messages = [];
        foreach ($repo->listMessages((int)$id, 100) as $m) {
            $role = (string)$m['role'];
            if ($role !== 'user' && $role !== 'assistant') {
                continue;
            }
            $messages[] = ['role' => $role, 'content' => (string)$m['content'], 'at' => (string)$m['created_at']];
        }

        return Response::json([
            'ok' => true,
            'conversation_id' => (int)$id,
            'status' => (string)$conv['status'],
            'messages' => $messages,
        ]);
    }

    /** Exige usuário de clínica autenticado (inclui "logar como", que é marcado nos chamados) e fora do super admin sem clínica. */
    private function guard(): ?Response
    {
        $auth = new AuthService($this->container);
        $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;
        $userId = $auth->userId();
        $clinicId = $auth->clinicId();

        if ($isSuperAdmin || $userId === null || $userId <= 0 || $clinicId === null || $clinicId <= 0) {
            return Response::json(['ok' => false, 'error' => 'Acesso negado.'], 403);
        }
        return null;
    }

    private function firstName(): string
    {
        try {
            $auth = new AuthService($this->container);
            $pdo = $this->container->get(\PDO::class);
            $stmt = $pdo->prepare('SELECT name FROM users WHERE id = :id AND clinic_id = :clinic_id LIMIT 1');
            $stmt->execute(['id' => (int)$auth->userId(), 'clinic_id' => (int)$auth->clinicId()]);
            $name = trim((string)($stmt->fetchColumn() ?: ''));
            return $name === '' ? '' : (string)(preg_split('/\s+/u', $name)[0] ?? '');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** @return list<string> */
    private function chipsFor(string $pagePath): array
    {
        foreach (self::CHIPS as $prefix => $chips) {
            if ($pagePath === $prefix || str_starts_with($pagePath, $prefix . '/')) {
                return $chips;
            }
        }
        return self::DEFAULT_CHIPS;
    }
}
