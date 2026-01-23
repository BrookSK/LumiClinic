<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;
use App\Repositories\PerformanceLogRepository;
use App\Services\Auth\AuthService;

final class PerformanceMonitoringMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Container $container) {}

    public function handle(Request $request, callable $next): Response
    {
        $start = microtime(true);

        try {
            $response = $next($request);
        } finally {
            $elapsedMs = (int)round((microtime(true) - $start) * 1000);

            $auth = new AuthService($this->container);
            $clinicId = $auth->clinicId();

            $status = 0;
            if (isset($response) && $response instanceof Response) {
                $status = $response->status();
            }

            (new PerformanceLogRepository($this->container->get(\PDO::class)))->log(
                $request->path(),
                $request->method(),
                $elapsedMs,
                $status,
                $clinicId
            );
        }

        return $response;
    }
}
