<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;
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

        $public = [
            '/login',
            '/forgot',
            '/reset',
            '/choose-access',
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
            if ($uri !== '' && $uri !== '/login' && $uri !== '/choose-access') {
                $_SESSION['auth_next'] = $uri;
            }
            return Response::redirect('/login');
        }

        $pending = (new LegalDocumentService($this->container))->listPendingRequiredForCurrentUser();
        $_SESSION['required_legal_docs'] = $pending;

        $enforced = [
            '/logout',
            '/legal/required',
            '/legal/accept',
        ];
        if ($pending !== [] && !in_array($path, $enforced, true) && $request->method() !== 'GET') {
            return Response::redirect('/legal/required');
        }

        return $next($request);
    }
}
