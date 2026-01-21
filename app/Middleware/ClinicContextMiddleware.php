<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;
use App\Services\Auth\AuthService;

final class ClinicContextMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Container $container) {}

    public function handle(Request $request, callable $next): Response
    {
        $auth = new AuthService($this->container);

        $clinicId = $auth->clinicId();

        if ($auth->userId() !== null && $clinicId === null) {
            return Response::html('Contexto de clínica ausente.', 403);
        }

        $this->container->set('clinic_id', fn () => $clinicId);

        return $next($request);
    }
}
