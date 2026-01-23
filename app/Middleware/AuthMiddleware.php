<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;
use App\Services\Auth\AuthService;

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

        $public = [
            '/login',
        ];

        if (in_array($path, $public, true)) {
            return $next($request);
        }

        $auth = new AuthService($this->container);

        if ($auth->userId() === null) {
            return Response::redirect('/login');
        }

        return $next($request);
    }
}
