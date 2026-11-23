<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;
use App\Repositories\UserRepository;
use App\Services\Auth\AuthService;
use App\Services\Legal\LegalDocumentService;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Container $container) {}

    public function handle(Request $request, callable $next): Response
    {
        $path = $request->path();

        if (str_starts_with($path, '/portal')) {
            return $next($request);
        }

        if (str_starts_with($path, '/api')) {
            return $next($request);
        }

        if (str_starts_with($path, '/tutorial')) {
            return $next($request);
        }

        if (str_starts_with($path, '/doc/')) {
            return $next($request);
        }

        if (str_starts_with($path, '/pub/')) {
            return $next($request);
        }

        if (str_starts_with($path, '/dev/')) {
            return $next($request);
        }

        if (str_starts_with($path, '/webhooks/ai-billing/')) {
            return $next($request);
        }

        $public = [
            '/',
            '/login',
            '/forgot',
            '/reset',
            '/choose-access',
            '/criar-conta',
            '/a/confirm',
            '/a/anamnese',
            '/private/tutorial/platform',
            '/private/tutorial/clinic',
            '/tutorial/api-tokens/paciente',
            '/tutorial/sistema',
        ];

        if (in_array($path, $public, true)) {
            return $next($request);
        }

        $auth = new AuthService($this->container);

        if ($auth->userId() === null) {
            $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
            // Don't save JSON/API endpoints as redirect target
            if ($uri !== '' && $uri !== '/login' && $uri !== '/choose-access'
                && !str_contains($uri, '-json') && !str_contains($uri, '.json')
                && !str_starts_with($uri, '/api/')) {
                $_SESSION['auth_next'] = $uri;
            }
            return Response::redirect('/login');
        }

        // Usuario arquivado/excluido perde a sessao imediatamente
        $sessionUserId = (int)$auth->userId();
        if (!(new UserRepository($this->container->get(\PDO::class)))->isActiveById($sessionUserId)) {
            unset($_SESSION['user_id'], $_SESSION['active_clinic_id'], $_SESSION['permissions'], $_SESSION['is_super_admin']);
            unset($_SESSION['role_codes'], $_SESSION['pending_access'], $_SESSION['impersonator']);
            if (!isset($_SESSION['patient_user_id']) || (int)$_SESSION['patient_user_id'] <= 0) {
                unset($_SESSION['clinic_id']);
            }
            return Response::redirect('/login');
        }

        // Super admin "logado como" o dono: não exige aceite/assinatura de termos em nome do dono
        if ($auth->isImpersonating()) {
            $_SESSION['required_legal_docs'] = [];
            return $next($request);
        }

        $pending = (new LegalDocumentService($this->container))->listPendingRequiredForCurrentUser();
        $_SESSION['required_legal_docs'] = $pending;

        $enforced = [
            '/logout',
            '/legal/required',
            '/legal/accept',
            '/legal/sign',
        ];
        if ($pending !== [] && !in_array($path, $enforced, true) && $request->method() !== 'GET') {
            return Response::redirect('/legal/required');
        }

        return $next($request);
    }
}
