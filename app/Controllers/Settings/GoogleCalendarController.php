<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\AuditLogRepository;
use App\Repositories\GoogleOAuthTokenRepository;
use App\Services\Auth\AuthService;
use App\Services\Google\GoogleCalendarOAuthService;
use App\Services\Security\CryptoService;

final class GoogleCalendarController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('settings.read');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new GoogleOAuthTokenRepository($pdo);
        $token = $repo->findActiveByClinicUser($clinicId, $userId);

        return $this->view('settings/google_calendar', [
            'connected' => $token !== null,
            'calendar_id' => $token !== null ? (string)($token['calendar_id'] ?? 'primary') : 'primary',
            'client_ready' => (new \App\Services\Google\GoogleOAuthConfigService($this->container))->getClientId() !== null,
            'lib_ready' => class_exists('Google\\Client'),
            'error' => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
        ]);
    }

    public function connect(Request $request)
    {
        $this->authorize('settings.update');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $calendarId = trim((string)$request->input('calendar_id', 'primary'));
        if ($calendarId === '') {
            $calendarId = 'primary';
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['gcal_oauth_state'] = $state;
        $_SESSION['gcal_oauth_calendar_id'] = $calendarId;

        $redirectUri = $this->baseUrl($request) . '/settings/google-calendar/callback';

        $oauth = new GoogleCalendarOAuthService($this->container);
        if (!$oauth->isAvailable()) {
            return $this->redirect('/settings/google-calendar?error=' . urlencode('Dependência ausente: instale google/apiclient via Composer.'));
        }

        try {
            $url = $oauth->buildAuthUrl($redirectUri, $state, $calendarId);
        } catch (\RuntimeException $e) {
            return $this->redirect('/settings/google-calendar?error=' . urlencode($e->getMessage()));
        }

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            $userId,
            $clinicId,
            'settings.gcal_connect_start',
            ['calendar_id' => $calendarId],
            $request->ip()
        );

        return $this->redirect($url);
    }

    public function callback(Request $request)
    {
        $this->authorize('settings.update');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $state = trim((string)$request->input('state', ''));
        $expected = isset($_SESSION['gcal_oauth_state']) ? (string)$_SESSION['gcal_oauth_state'] : '';
        if ($state === '' || $expected === '' || !hash_equals($expected, $state)) {
            return $this->redirect('/settings/google-calendar?error=' . urlencode('State inválido.'));
        }

        $code = trim((string)$request->input('code', ''));
        if ($code === '') {
            return $this->redirect('/settings/google-calendar?error=' . urlencode('Código OAuth ausente.'));
        }

        $calendarId = isset($_SESSION['gcal_oauth_calendar_id']) ? (string)$_SESSION['gcal_oauth_calendar_id'] : 'primary';
        $calendarId = trim($calendarId) === '' ? 'primary' : trim($calendarId);

        $redirectUri = $this->baseUrl($request) . '/settings/google-calendar/callback';

        $oauth = new GoogleCalendarOAuthService($this->container);
        if (!$oauth->isAvailable()) {
            return $this->redirect('/settings/google-calendar?error=' . urlencode('Dependência ausente: instale google/apiclient via Composer.'));
        }

        try {
            $token = $oauth->exchangeCode($redirectUri, $code);
        } catch (\RuntimeException $e) {
            return $this->redirect('/settings/google-calendar?error=' . urlencode($e->getMessage()));
        }

        $refresh = isset($token['refresh_token']) ? (string)$token['refresh_token'] : '';
        if (trim($refresh) === '') {
            return $this->redirect('/settings/google-calendar?error=' . urlencode('Google não retornou refresh_token. Tente desconectar e conectar novamente.'));
        }

        $refreshEnc = (new CryptoService($this->container))->encrypt($clinicId, $refresh);

        $expiresAt = null;
        if (isset($token['expires_in']) && is_numeric($token['expires_in'])) {
            $expiresAt = (new \DateTimeImmutable('now'))->modify('+' . (int)$token['expires_in'] . ' seconds')->format('Y-m-d H:i:s');
        }

        $scopes = null;
        if (isset($token['scope']) && is_string($token['scope'])) {
            $scopes = (string)$token['scope'];
        }

        (new GoogleOAuthTokenRepository($this->container->get(\PDO::class)))->upsert(
            $clinicId,
            $userId,
            $scopes,
            isset($token['access_token']) ? (string)$token['access_token'] : null,
            $refreshEnc,
            $expiresAt,
            $calendarId,
            null
        );

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            $userId,
            $clinicId,
            'settings.gcal_connected',
            ['calendar_id' => $calendarId],
            $request->ip()
        );

        unset($_SESSION['gcal_oauth_state'], $_SESSION['gcal_oauth_calendar_id']);

        return $this->redirect('/settings/google-calendar?success=' . urlencode('Google Calendar conectado.'));
    }

    public function disconnect(Request $request)
    {
        $this->authorize('settings.update');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        (new GoogleOAuthTokenRepository($this->container->get(\PDO::class)))->revoke($clinicId, $userId);

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            $userId,
            $clinicId,
            'settings.gcal_disconnected',
            [],
            $request->ip()
        );

        return $this->redirect('/settings/google-calendar?success=' . urlencode('Desconectado.'));
    }

    private function baseUrl(Request $request): string
    {
        $baseUrl = getenv('APP_BASE_URL') ?: (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
            . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
        );
        return rtrim((string)$baseUrl, '/');
    }
}
