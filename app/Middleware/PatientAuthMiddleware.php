<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;
use App\Services\Portal\PatientAuthService;

final class PatientAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Container $container) {}

    public function handle(Request $request, callable $next): Response
    {
        $path = $request->path();
        if (!str_starts_with($path, '/portal')) {
            return $next($request);
        }

        $public = [
            '/portal/login',
            '/portal/forgot',
            '/portal/reset',
        ];

        if (in_array($path, $public, true)) {
            return $next($request);
        }

        $auth = new PatientAuthService($this->container);
        if ($auth->patientUserId() === null) {
            $uri = (string)($_SERVER['REQUEST_URI'] ?? '/portal');
            if ($uri !== '' && $uri !== '/portal/login') {
                $_SESSION['portal_next'] = $uri;
            }
            return Response::redirect('/portal/login');
        }

        return $next($request);
    }
}
