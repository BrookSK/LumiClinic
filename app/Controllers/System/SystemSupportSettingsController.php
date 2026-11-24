<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\System\SystemSettingsService;

final class SystemSupportSettingsController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
            throw new \RuntimeException('Acesso negado.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);
        $saved = trim((string)$request->input('saved', ''));
        $error = trim((string)$request->input('error', ''));

        return $this->view('system/support/settings', [
            'settings' => $service->getSupportBotSettings(),
            'global_key_set' => trim((string)($service->getText('ai.openai.global_api_key') ?? '')) !== '',
            'env_key_set' => trim((string)(getenv('OPENAI_API_KEY') ?: '')) !== '',
            'success' => $saved !== '' ? 'Salvo com sucesso.' : null,
            'error' => $error !== '' ? $error : null,
        ]);
    }

    public function submit(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);

        try {
            $service->saveSupportBotSettings([
                'enabled' => (string)$request->input('bot_enabled', '') === '1',
                'model' => (string)$request->input('bot_model', ''),
                'api_key' => (string)$request->input('bot_api_key', ''),
                'clear_api_key' => (string)$request->input('clear_api_key', '') === '1',
                'email_fallback' => (string)$request->input('email_fallback', '') === '1',
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->redirect('/sys/support/settings?error=' . urlencode($e->getMessage()));
        }

        return $this->redirect('/sys/support/settings?saved=1');
    }
}
