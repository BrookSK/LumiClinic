<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Core\Container\Container;
use App\Repositories\SupportDevPhoneRepository;
use App\Repositories\SupportTicketEventRepository;
use App\Repositories\SupportTicketNotificationRepository;
use App\Repositories\SupportTicketRepository;
use App\Services\Mail\MailerService;
use App\Services\System\SystemSettingsService;

/**
 * Notifica os desenvolvedores por WhatsApp (instância da plataforma) quando um chamado é criado.
 *
 * Modelo da mensagem (WhatsApp, negrito com *; omite seções vazias):
 *
 *   🆕 *NOVO CHAMADO DE SUPORTE*
 *   *LC-000123* | Severidade: *ALTA* | Categoria: Erro
 *   Título: ...
 *
 *   🏥 *Clínica*            nome (ID n)
 *   👤 *Solicitante*        nome, e-mail, telefone, perfil
 *   📍 *Onde*               página, URL completa, data/hora
 *   📝 *Resumo*
 *   🔧 *O que já tentou*
 *   🎯 *Esperado*
 *   ⚠️ *O que aconteceu*
 *   🐞 *Erros recentes (60 min)*   até 5 linhas: [status] METODO /rota: mensagem (hora)
 *   💬 *Conversa (últimas 10)*     "Usuário: ..." / "Bot: ..." (300 caracteres cada, segredos removidos)
 *   🔗 *Painel*             {base_url}/sys/support/tickets/show?id=N
 *
 * Mensagens acima de 3500 caracteres viram várias partes "(parte i/n)". Cada parte para cada número gera uma linha em
 * support_ticket_notifications. Nunca lança exceção: ao final, o resultado é registrado como evento do chamado.
 */
final class SupportTicketWhatsappNotifier implements SupportTicketNotifierInterface
{
    private const MAX_PART_CHARS = 3500;

    private SupportTextSanitizer $sanitizer;

    public function __construct(private readonly Container $container)
    {
        $this->sanitizer = new SupportTextSanitizer();
    }

    public function notifyCreated(array $ticket): void
    {
        // Com PHP-FPM, a entrega roda depois que a resposta do chat já foi enviada ao navegador.
        if (function_exists('fastcgi_finish_request')) {
            register_shutdown_function(function () use ($ticket): void {
                try {
                    @ignore_user_abort(true);
                    @set_time_limit(180);
                    fastcgi_finish_request();
                } catch (\Throwable $e) {
                }
                $this->releaseSession();
                $this->deliver($ticket, false, 15);
            });
            return;
        }

        // Sem FPM a entrega é síncrona: solta o lock da sessão para não travar as outras requisições do usuário.
        $this->releaseSession();
        $this->deliver($ticket, false, 8);
    }

    /** Libera o lock do arquivo de sessão antes de enviar mensagens lentas. $_SESSION continua legível. */
    private function releaseSession(): void
    {
        if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
    }

    /**
     * Reenvio manual (super admin). Monta a mensagem a partir do chamado salvo.
     * @return array{sent:int,failed:int}
     */
    public function resend(int $ticketId, bool $onlyFailed): array
    {
        $pdo = $this->container->get(\PDO::class);
        $ticket = (new SupportTicketRepository($pdo))->findById($ticketId);
        if ($ticket === null) {
            throw new \RuntimeException('Chamado não encontrado.');
        }

        $context = [];
        $transcript = [];
        $decoded = json_decode((string)($ticket['context_json'] ?? ''), true);
        if (is_array($decoded)) {
            if (isset($decoded['transcript']) && is_array($decoded['transcript'])) {
                $transcript = $decoded['transcript'];
                unset($decoded['transcript']);
            }
            $context = $decoded;
        }

        $only = null;
        if ($onlyFailed) {
            $only = [];
            foreach ((new SupportTicketNotificationRepository($pdo))->listByTicket($ticketId) as $n) {
                if ((string)$n['channel'] === 'whatsapp' && in_array((string)$n['status'], ['failed', 'skipped'], true)) {
                    $only[(string)$n['destination']] = true;
                }
            }
            if ($only === []) {
                return ['sent' => 0, 'failed' => 0];
            }
        }

        return $this->deliver($ticket + ['context' => $context, 'transcript' => $transcript], true, 15, $only);
    }

    /**
     * @param array<string,mixed> $ticket
     * @param array<string,bool>|null $onlyDestinations telefones permitidos (reenvio apenas dos que falharam)
     * @return array{sent:int,failed:int}
     */
    private function deliver(array $ticket, bool $manual, int $timeout, ?array $onlyDestinations = null): array
    {
        $sent = 0;
        $failed = 0;
        $ticketId = (int)($ticket['id'] ?? 0);

        try {
            if ($ticketId <= 0) {
                return ['sent' => 0, 'failed' => 0];
            }

            $pdo = $this->container->get(\PDO::class);
            $notifications = new SupportTicketNotificationRepository($pdo);
            $wa = new SupportWhatsappService($this->container);

            $phones = (new SupportDevPhoneRepository($pdo))->listActive();
            if ($onlyDestinations !== null) {
                $phones = array_values(array_filter($phones, static fn (array $p): bool => isset($onlyDestinations[(string)$p['phone']])));
            }

            $parts = $this->splitMessage($this->buildMessage($ticket));
            $total = count($parts);

            $connected = false;
            $connectionError = 'WhatsApp de suporte não está conectado.';
            if ($phones !== []) {
                $st = $wa->status();
                $connected = $st['connected'];
                if (!$st['configured']) {
                    $connectionError = 'Evolution API global não configurada.';
                } elseif ($st['error'] !== null) {
                    $connectionError = $st['error'];
                }
            }

            $first = true;
            foreach ($phones as $phone) {
                $dest = (string)$phone['phone'];

                if (!$connected) {
                    $notifications->create($ticketId, 'whatsapp', $dest, 'skipped', $connectionError, 0);
                    $failed++;
                    continue;
                }

                foreach ($parts as $i => $text) {
                    if (!$first) {
                        sleep(1);
                    }
                    $first = false;

                    $label = $total > 1 ? ('parte ' . ($i + 1) . '/' . $total) : '';
                    try {
                        $wa->sendToNumber($dest, $text, $timeout);
                        $notifications->create($ticketId, 'whatsapp', $dest, 'sent', $label !== '' ? $label : null, 1);
                        $sent++;
                    } catch (\Throwable $e) {
                        $msg = ($label !== '' ? $label . ': ' : '') . $e->getMessage();
                        $notifications->create($ticketId, 'whatsapp', $dest, 'failed', $msg, 1);
                        $failed++;
                    }
                }
            }

            $emailSent = 0;
            if ($sent === 0 && $onlyDestinations === null) {
                $emailSent = $this->emailFallback($ticket, $ticketId, $notifications);
            }

            $summary = 'WhatsApp: ' . $sent . ' enviado(s), ' . $failed . ' falha(s)/ignorado(s)'
                . ($phones === [] ? ' (nenhum número ativo)' : '')
                . ($emailSent > 0 ? '; e-mail de contingência para ' . $emailSent . ' destinatário(s)' : '')
                . ($manual ? ' [reenvio manual]' : '') . '.';
            (new SupportTicketEventRepository($pdo))->add($ticketId, null, 'notification', $summary);
        } catch (\Throwable $e) {
            error_log('[support.notifier] ' . mb_substr($e->getMessage(), 0, 300));
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /** E-mail de contingência (dev.alert_emails) quando nenhuma mensagem de WhatsApp foi entregue. Retorna quantos e-mails foram enviados. */
    private function emailFallback(array $ticket, int $ticketId, SupportTicketNotificationRepository $notifications): int
    {
        try {
            $settings = new SystemSettingsService($this->container);
            $bot = $settings->getSupportBotSettings();
            if (empty($bot['email_fallback'])) {
                return 0;
            }

            $raw = trim((string)($settings->getText('dev.alert_emails') ?? ''));
            $emails = array_values(array_filter(array_map('trim', preg_split('/[\s,;]+/', $raw) ?: []), static fn ($x) => $x !== ''));
            if ($emails === []) {
                return 0;
            }

            $text = $this->buildMessage($ticket);
            $html = '<pre style="white-space:pre-wrap;font-family:Arial,sans-serif;font-size:14px;line-height:1.45;">'
                . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</pre>';
            $subject = 'Novo chamado de suporte ' . (string)($ticket['public_code'] ?? '') . ' - LumiClinic';

            $count = 0;
            $mailer = new MailerService($this->container);
            foreach ($emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    continue;
                }
                try {
                    $mailer->send($email, '', $subject, $html);
                    $notifications->create($ticketId, 'email', $email, 'sent', null, 1);
                    $count++;
                } catch (\Throwable $e) {
                    $notifications->create($ticketId, 'email', $email, 'failed', $e->getMessage(), 1);
                }
            }

            return $count;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Texto completo da notificação (sem divisão em partes).
     * @param array<string,mixed> $ticket linha do chamado + 'context' + 'transcript'
     */
    public function buildMessage(array $ticket): string
    {
        $context = isset($ticket['context']) && is_array($ticket['context']) ? $ticket['context'] : [];
        $transcript = isset($ticket['transcript']) && is_array($ticket['transcript']) ? $ticket['transcript'] : [];
        $clinic = isset($context['clinic']) && is_array($context['clinic']) ? $context['clinic'] : [];
        $user = isset($context['user']) && is_array($context['user']) ? $context['user'] : [];

        $sevLabels = ['baixa' => 'BAIXA', 'media' => 'MÉDIA', 'alta' => 'ALTA', 'critica' => 'CRÍTICA'];
        $catLabels = ['bug' => 'Erro', 'duvida' => 'Dúvida', 'acesso' => 'Acesso', 'financeiro' => 'Financeiro', 'integracao' => 'Integração', 'outro' => 'Outro'];
        $sev = (string)($ticket['severity'] ?? 'media');
        $cat = (string)($ticket['category'] ?? 'outro');

        $clinicName = $this->one((string)($ticket['clinic_name'] ?? ($clinic['name'] ?? '')), 120);
        $clinicId = (int)($ticket['clinic_id'] ?? ($clinic['id'] ?? 0));
        $userName = $this->one((string)($ticket['user_name'] ?? ($user['name'] ?? '')), 120);
        $userEmail = $this->one((string)($ticket['user_email'] ?? ($user['email'] ?? '')), 120);
        $userPhone = $this->one((string)($user['phone'] ?? ''), 40);
        $roles = isset($context['role_codes']) && is_array($context['role_codes']) ? implode(', ', array_map('strval', $context['role_codes'])) : '';

        $pagePath = $this->one((string)($ticket['page_path'] ?? ($context['page_path'] ?? '')), 200);
        $base = $this->baseUrl();
        $fullUrl = $pagePath !== '' && $base !== '' ? $base . $pagePath : '';

        $createdAt = (string)($ticket['created_at'] ?? ($context['server_time'] ?? ''));
        $ts = $createdAt !== '' ? strtotime($createdAt) : false;
        $when = $ts !== false ? date('d/m/Y H:i', $ts) : date('d/m/Y H:i');

        $lines = [];
        $lines[] = '🆕 *NOVO CHAMADO DE SUPORTE*';
        $lines[] = '*' . (string)($ticket['public_code'] ?? '') . '* | Severidade: *' . ($sevLabels[$sev] ?? strtoupper($sev)) . '* | Categoria: ' . ($catLabels[$cat] ?? $cat);
        $title = $this->one((string)($ticket['title'] ?? ''), 190);
        if ($title !== '') {
            $lines[] = 'Título: ' . $title;
        }

        if (!empty($context['impersonated_session'])) {
            $impId = (int)($context['impersonator_user_id'] ?? 0);
            $lines[] = '⚠ *Aberto por super admin em modo Logar como*' . ($impId > 0 ? ' (usuário #' . $impId . ')' : '') . ' - não é um cliente real';
        }

        $lines[] = '';
        $lines[] = '🏥 *Clínica*';
        $lines[] = ($clinicName !== '' ? $clinicName : '-') . ($clinicId > 0 ? ' (ID ' . $clinicId . ')' : '');

        $lines[] = '';
        $lines[] = '👤 *Solicitante*';
        $lines[] = $userName !== '' ? $userName : '-';
        if ($userEmail !== '') {
            $lines[] = 'E-mail: ' . $userEmail;
        }
        if ($userPhone !== '') {
            $lines[] = 'Telefone: ' . $userPhone;
        }
        if ($roles !== '') {
            $lines[] = 'Perfil: ' . $roles;
        }

        $lines[] = '';
        $lines[] = '📍 *Onde*';
        $lines[] = 'Página: ' . ($pagePath !== '' ? $pagePath : '-');
        if ($fullUrl !== '') {
            $lines[] = 'URL: ' . $fullUrl;
        }
        $lines[] = 'Quando: ' . $when;

        foreach ([
            ['📝 *Resumo*', 'summary', 1500],
            ['🔧 *O que já tentou*', 'steps_tried', 1000],
            ['🎯 *Esperado*', 'expected', 800],
            ['⚠️ *O que aconteceu*', 'actual', 800],
        ] as [$header, $key, $max]) {
            $text = $this->block((string)($ticket[$key] ?? ''), $max);
            if ($text !== '') {
                $lines[] = '';
                $lines[] = $header;
                $lines[] = $text;
            }
        }

        $errors = isset($context['recent_errors']) && is_array($context['recent_errors']) ? $context['recent_errors'] : [];
        if ($errors !== []) {
            $lines[] = '';
            $lines[] = '🐞 *Erros recentes (60 min)*';
            foreach (array_slice($errors, 0, 5) as $er) {
                if (!is_array($er)) {
                    continue;
                }
                $at = isset($er['at']) && strtotime((string)$er['at']) !== false ? date('H:i', (int)strtotime((string)$er['at'])) : '';
                $lines[] = '- [' . (int)($er['status'] ?? 0) . '] ' . $this->one((string)($er['method'] ?? ''), 8) . ' '
                    . $this->one((string)($er['path'] ?? ''), 120) . ': ' . $this->one((string)($er['message'] ?? ''), 200)
                    . ($at !== '' ? ' (' . $at . ')' : '');
            }
        }

        $conv = [];
        foreach ($transcript as $m) {
            if (!is_array($m)) {
                continue;
            }
            $role = (string)($m['role'] ?? '');
            if ($role !== 'user' && $role !== 'assistant') {
                continue;
            }
            $conv[] = ['role' => $role, 'content' => (string)($m['content'] ?? '')];
        }
        $conv = array_slice($conv, -10);
        if ($conv !== []) {
            $lines[] = '';
            $lines[] = '💬 *Conversa (últimas ' . count($conv) . ')*';
            foreach ($conv as $m) {
                $lines[] = ($m['role'] === 'user' ? 'Usuário: ' : 'Bot: ') . $this->one($m['content'], 300);
            }
        }

        $id = (int)($ticket['id'] ?? 0);
        if ($id > 0 && $base !== '') {
            $lines[] = '';
            $lines[] = '🔗 *Painel*';
            $lines[] = $base . '/sys/support/tickets/show?id=' . $id;
        }

        return implode("\n", $lines);
    }

    /**
     * Divide em partes de até 3500 caracteres (por linhas; linhas maiores são cortadas). Com mais de uma parte, cada uma
     * recebe o prefixo "(parte i/n)".
     * @return list<string>
     */
    public function splitMessage(string $text): array
    {
        if (mb_strlen($text, 'UTF-8') <= self::MAX_PART_CHARS) {
            return [$text];
        }

        $limit = self::MAX_PART_CHARS - 40;
        $chunks = [];
        $cur = '';
        foreach (explode("\n", $text) as $line) {
            while (mb_strlen($line, 'UTF-8') > $limit) {
                if ($cur !== '') {
                    $chunks[] = $cur;
                    $cur = '';
                }
                $chunks[] = mb_substr($line, 0, $limit, 'UTF-8');
                $line = mb_substr($line, $limit, null, 'UTF-8');
            }
            $candidate = $cur === '' ? $line : $cur . "\n" . $line;
            if (mb_strlen($candidate, 'UTF-8') > $limit) {
                $chunks[] = $cur;
                $cur = $line;
            } else {
                $cur = $candidate;
            }
        }
        if ($cur !== '') {
            $chunks[] = $cur;
        }

        $n = count($chunks);
        $out = [];
        foreach ($chunks as $i => $c) {
            $out[] = '(parte ' . ($i + 1) . '/' . $n . ")\n" . $c;
        }

        return $out;
    }

    private function baseUrl(): string
    {
        $cfg = $this->container->has('config') ? $this->container->get('config') : [];
        $base = is_array($cfg) && isset($cfg['app']) && is_array($cfg['app']) ? (string)($cfg['app']['base_url'] ?? '') : '';

        return rtrim(trim($base), '/');
    }

    /** Uma linha, sem segredos, limitada a $max caracteres. */
    private function one(string $text, int $max): string
    {
        $text = $this->sanitizer->redactSecrets($text);
        $text = trim((string)preg_replace('/\s+/u', ' ', $text));

        return mb_strlen($text, 'UTF-8') > $max ? mb_substr($text, 0, $max - 1, 'UTF-8') . '…' : $text;
    }

    /** Bloco de texto (mantém quebras de linha), sem segredos, limitado a $max caracteres. */
    private function block(string $text, int $max): string
    {
        $text = trim($this->sanitizer->redactSecrets($text));
        $text = (string)preg_replace("/\n{3,}/", "\n\n", str_replace("\r", '', $text));

        return mb_strlen($text, 'UTF-8') > $max ? mb_substr($text, 0, $max - 1, 'UTF-8') . '…' : $text;
    }
}
