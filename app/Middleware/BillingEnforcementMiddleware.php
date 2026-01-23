<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;
use App\Services\Auth\AuthService;
use App\Services\Billing\BillingService;

final class BillingEnforcementMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Container $container) {}

    public function handle(Request $request, callable $next): Response
    {
        $path = $request->path();

        if (
            $path === '/login' ||
            $path === '/logout' ||
            $path === '/favicon.ico' ||
            str_starts_with($path, '/assets') ||
            str_starts_with($path, '/css') ||
            str_starts_with($path, '/js') ||
            str_starts_with($path, '/webhooks') ||
            str_starts_with($path, '/portal') ||
            str_starts_with($path, '/api')
        ) {
            return $next($request);
        }

        if (str_starts_with($path, '/sys')) {
            return $next($request);
        }

        $auth = new AuthService($this->container);

        $userId = $auth->userId();
        if ($userId === null) {
            return $next($request);
        }

        $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;
        if ($isSuperAdmin) {
            return $next($request);
        }

        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return Response::html('Contexto inválido.', 403);
        }

        $billing = new BillingService($this->container);
        $data = $billing->getOrCreateClinicSubscription($clinicId);

        if ($billing->isBlocked($data['subscription'])) {
            return Response::html('Assinatura em atraso. Entre em contato para regularizar.', 402);
        }

        return $next($request);
    }
}
