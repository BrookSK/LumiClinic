<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container\Container;
use App\Core\Http\Response;
use App\Core\View\View;
use App\Repositories\SupportKbEntryRepository;
use App\Services\Auth\AuthService;
use App\Services\Authorization\AuthorizationService;
use App\Services\System\SystemSettingsService;
use App\Services\Settings\SettingsService;

abstract class Controller
{
    public function __construct(protected readonly Container $container) {}

    /** @param array<string, mixed> $data */
    protected function view(string $view, array $data = []): Response
    {
        $seo = (new SystemSettingsService($this->container))->getSeoSettings();
        if (!array_key_exists('seo', $data)) {
            $data['seo'] = $seo;
        }

        if (!array_key_exists('terminology', $data)) {
            $data['terminology'] = (new SettingsService($this->container))->getTerminology();
        }

        if (!array_key_exists('support_settings', $data)) {
            try {
                $data['support_settings'] = (new SystemSettingsService($this->container))->getSupportSettings();
            } catch (\Throwable $ignore) {
                $data['support_settings'] = [];
            }
        }

        if (!array_key_exists('support_bot_enabled', $data)) {
            $data['support_bot_enabled'] = $this->supportBotEnabledForSession();
        }

        if (!array_key_exists('support_kb_draft_count', $data)) {
            $data['support_kb_draft_count'] = $this->supportKbDraftCountForSession();
        }

        return Response::html(View::render($view, $data));
    }

    /** Rascunhos de FAQ aguardando revisão, só para o menu do super admin. */
    private function supportKbDraftCountForSession(): int
    {
        try {
            if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
                return 0;
            }
            return (new SupportKbEntryRepository($this->container->get(\PDO::class)))->countByStatus('draft');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Mostra o assistente de suporte para usuário de clínica (inclui "logar como"; super admin sem clínica não) com o bot ativo. */
    private function supportBotEnabledForSession(): bool
    {
        try {
            $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;
            if ($isSuperAdmin || !isset($_SESSION['user_id'])) {
                return false;
            }
            return (new SystemSettingsService($this->container))->isSupportBotEnabled();
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function redirect(string $to): Response
    {
        return Response::redirect($to);
    }

    protected function authorize(string $permissionCode): void
    {
        $authz = new AuthorizationService($this->container);

        if (!$authz->check($permissionCode)) {
            throw new \RuntimeException('Acesso negado.');
        }
    }
}
