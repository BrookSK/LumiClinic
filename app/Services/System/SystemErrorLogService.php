<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Repositories\SystemErrorLogRepository;
use App\Services\Mail\MailerService;

final class SystemErrorLogService
{
    public function __construct(private readonly Container $container) {}

    /** @param array<string,mixed> $context */
    public function logHttpError(Request $request, int $statusCode, string $errorType, string $message, ?\Throwable $e = null, array $context = []): void
    {
        try {
            $statusCode = (int)$statusCode;
            $clinicId = null;
            if (isset($_SESSION['active_clinic_id']) && is_int($_SESSION['active_clinic_id'])) {
                $clinicId = (int)$_SESSION['active_clinic_id'];
            } elseif (isset($_SESSION['clinic_id'])) {
                $clinicId = (int)$_SESSION['clinic_id'];
            }

            $userId = null;
            if (isset($_SESSION['user_id'])) {
                $userId = (int)$_SESSION['user_id'];
            }

            $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;

            $trace = null;
            if ($e !== null) {
                $trace = $e->__toString();
            }

            $contextJson = null;
            if ($context !== []) {
                $contextJson = (string)json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $repo = new SystemErrorLogRepository($this->container->get(\PDO::class));
            $id = $repo->create([
                'status_code' => $statusCode,
                'error_type' => $errorType,
                'message' => $message !== '' ? $message : 'Erro',
                'method' => $request->method(),
                'path' => $request->path(),
                'clinic_id' => ($clinicId !== null && $clinicId > 0) ? $clinicId : null,
                'user_id' => ($userId !== null && $userId > 0) ? $userId : null,
                'is_super_admin' => $isSuperAdmin ? 1 : 0,
                'ip' => $request->ip(),
                'user_agent' => $request->header('user-agent'),
                'trace_text' => $trace,
                'context_json' => $contextJson,
            ]);

            if ($statusCode >= 500) {
                $this->notifyDevelopers($id, $statusCode, $errorType, $message, $request, $clinicId, $userId, $isSuperAdmin, $trace);
            }
        } catch (\Throwable $ignore) {
        }
    }

    private function notifyDevelopers(
        int $logId,
        int $statusCode,
        string $errorType,
        string $message,
        Request $request,
        ?int $clinicId,
        ?int $userId,
        bool $isSuperAdmin,
        ?string $trace
    ): void {
        try {
            $settings = new SystemSettingsService($this->container);
            $raw = trim((string)($settings->getText('dev.alert_emails') ?? ''));
            if ($raw === '') {
                return;
            }

            $emails = preg_split('/[\s,;]+/', $raw) ?: [];
            $emails = array_values(array_filter(array_map('trim', $emails), fn ($x) => $x !== ''));
            if ($emails === []) {
                return;
            }

            $subject = 'Erro ' . $statusCode . ' - LumiClinic';

            $safeMsg = htmlspecialchars($message !== '' ? $message : 'Erro', ENT_QUOTES, 'UTF-8');
            $safePath = htmlspecialchars($request->path(), ENT_QUOTES, 'UTF-8');
            $safeMethod = htmlspecialchars($request->method(), ENT_QUOTES, 'UTF-8');
            $safeType = htmlspecialchars($errorType, ENT_QUOTES, 'UTF-8');

            $meta = '';
            $meta .= '<div><b>Log ID:</b> ' . (int)$logId . '</div>';
            $meta .= '<div><b>Status:</b> ' . (int)$statusCode . '</div>';
            $meta .= '<div><b>Tipo:</b> ' . $safeType . '</div>';
            $meta .= '<div><b>Rota:</b> ' . $safeMethod . ' ' . $safePath . '</div>';
            $meta .= '<div><b>Clínica:</b> ' . htmlspecialchars($clinicId !== null ? (string)$clinicId : '-', ENT_QUOTES, 'UTF-8') . '</div>';
            $meta .= '<div><b>Usuário:</b> ' . htmlspecialchars($userId !== null ? (string)$userId : '-', ENT_QUOTES, 'UTF-8') . '</div>';
            $meta .= '<div><b>Super Admin:</b> ' . ($isSuperAdmin ? 'Sim' : 'Não') . '</div>';
            $meta .= '<div><b>IP:</b> ' . htmlspecialchars($request->ip(), ENT_QUOTES, 'UTF-8') . '</div>';

            $traceHtml = '';
            if ($trace !== null && trim($trace) !== '') {
                $traceHtml = '<pre style="white-space:pre-wrap; background:#f6f7f9; border:1px solid #e5e7eb; padding:10px; border-radius:8px; font-size:12px; line-height:1.4;">'
                    . htmlspecialchars($trace, ENT_QUOTES, 'UTF-8')
                    . '</pre>';
            }

            $html = '<div style="font-family:Arial,sans-serif; font-size:14px;">'
                . '<h2 style="margin:0 0 12px 0;">Erro no LumiClinic</h2>'
                . '<div style="margin-bottom:10px;"><b>Mensagem:</b> ' . $safeMsg . '</div>'
                . '<div style="margin-bottom:12px;">' . $meta . '</div>'
                . $traceHtml
                . '</div>';

            $mailer = new MailerService($this->container);
            foreach ($emails as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $mailer->send($email, $email, $subject, $html);
            }
        } catch (\Throwable $ignore) {
        }
    }
}
