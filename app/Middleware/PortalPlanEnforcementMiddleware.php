<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;
use App\Services\Billing\PlanEntitlementsService;

final class PortalPlanEnforcementMiddleware implements MiddlewareInterface
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

        $clinicId = isset($_SESSION['clinic_id']) ? (int)$_SESSION['clinic_id'] : null;
        if ($clinicId === null || $clinicId <= 0) {
            return $next($request);
        }

        $ent = new PlanEntitlementsService($this->container);
        if (!$ent->isPortalEnabled($clinicId)) {
            return Response::html('Portal do paciente indisponível no plano atual.', 403);
        }

        return $next($request);
    }
}
