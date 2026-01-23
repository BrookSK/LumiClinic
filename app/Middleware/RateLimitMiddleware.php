<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;
use App\Repositories\SecurityRateLimitRepository;

final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Container $container) {}

    public function handle(Request $request, callable $next): Response
    {
        $pdo = $this->container->get(\PDO::class);
        $repo = new SecurityRateLimitRepository($pdo);

        $path = $request->path();

        if (str_starts_with($path, '/webhooks')) {
            return $next($request);
        }

        $ip = $request->ip();
        $ua = (string)($request->header('user-agent') ?? '');

        // Basic fingerprint: IP + UA (reduz colisão em NAT sem guardar PII em claro)
        $fingerprint = $ip . '|' . substr($ua, 0, 120);

        // Defaults
        $scope = 'web';
        $windowSeconds = 60;
        $maxHits = 240;
        $blockSeconds = 60;

        // Login brute force protection
        if ($request->method() === 'POST' && $path === '/login') {
            $scope = 'login';
            $windowSeconds = 600; // 10 min
            $maxHits = 10;
            $blockSeconds = 900; // 15 min
        }

        if ($request->method() === 'POST' && $path === '/portal/login') {
            $scope = 'portal_login';
            $windowSeconds = 600;
            $maxHits = 10;
            $blockSeconds = 900;
        }

        // API throttle
        if (str_starts_with($path, '/api')) {
            $scope = 'api';
            $windowSeconds = 60;
            $maxHits = 120;
            $blockSeconds = 120;
        }

        $decision = $repo->hit($scope, $fingerprint . '|' . $path, $windowSeconds, $maxHits, $blockSeconds);

        if (!$decision['allowed']) {
            $headers = [
                'Retry-After' => (string)$blockSeconds,
                'X-RateLimit-Limit' => (string)$maxHits,
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => $decision['reset_at'],
            ];

            if (str_starts_with($path, '/api')) {
                return Response::json([
                    'error' => 'rate_limited',
                    'message' => 'Muitas requisições. Tente novamente mais tarde.',
                    'blocked_until' => $decision['blocked_until'],
                ], 429, $headers);
            }

            return Response::html('Muitas requisições. Tente novamente mais tarde.', 429, $headers);
        }

        $response = $next($request);

        // Best-effort add headers
        return $response;
    }
}
