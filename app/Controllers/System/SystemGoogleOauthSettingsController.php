<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Google\GoogleOAuthConfigService;

final class SystemGoogleOauthSettingsController extends Controller
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

        $svc = new GoogleOAuthConfigService($this->container);
        $data = $svc->getConfig();
        $saved = trim((string)$request->input('saved', ''));

        return $this->view('system/settings/google_oauth', [
            'google_oauth_client_id' => $data['client_id'] ?? null,
            'google_oauth_client_secret_set' => (bool)($data['client_secret_set'] ?? false),
            'success' => $saved !== '' ? 'Salvo com sucesso.' : null,
        ]);
    }

    public function submit(Request $request)
    {
        $this->ensureSuperAdmin();

        $clientId = trim((string)$request->input('google_oauth_client_id', ''));
        $clientSecret = trim((string)$request->input('google_oauth_client_secret', ''));

        if ($clientId === '' || $clientSecret === '') {
            return $this->redirect('/sys/settings/google-oauth?error=' . urlencode('Informe client_id e client_secret.'));
        }

        (new GoogleOAuthConfigService($this->container))->setConfig($clientId, $clientSecret);

        return $this->redirect('/sys/settings/google-oauth?saved=1');
    }
}
