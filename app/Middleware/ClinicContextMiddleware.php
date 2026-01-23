<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;
use App\Repositories\ClinicRepository;
use App\Repositories\ClinicDomainRepository;
use App\Services\Auth\AuthService;

final class ClinicContextMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Container $container) {}

    private function resolveTenantKeyFromHost(?string $host): ?string
    {
        if ($host === null || $host === '') {
            return null;
        }

        $host = strtolower(trim($host));
        $host = explode(':', $host, 2)[0];

        if ($host === 'localhost' || preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host)) {
            return null;
        }

        $parts = explode('.', $host);

        if (count($parts) < 3) {
            return null;
        }

        $tenant = $parts[0];
        return $tenant !== '' ? $tenant : null;
    }

    private function normalizeHost(?string $host): ?string
    {
        if ($host === null || $host === '') {
            return null;
        }

        $host = strtolower(trim($host));
        $host = explode(':', $host, 2)[0];

        if ($host === '' || $host === 'localhost' || preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host)) {
            return null;
        }

        return $host;
    }

    public function handle(Request $request, callable $next): Response
    {
        $auth = new AuthService($this->container);

        $sessionClinicId = $auth->clinicId();
        $userId = $auth->userId();
        $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;

        $host = $this->normalizeHost($request->header('host'));
        $tenantKey = $this->resolveTenantKeyFromHost($host);
        $hostClinicId = null;

        $pdo = $this->container->get(\PDO::class);

        if ($host !== null) {
            $domains = new ClinicDomainRepository($pdo);
            $match = $domains->findByDomain($host);
            if ($match !== null) {
                $hostClinicId = (int)$match['clinic_id'];
            }
        }

        if ($hostClinicId === null && $tenantKey !== null) {
            $repo = new ClinicRepository($pdo);
            $clinic = $repo->findByTenantKey($tenantKey);
            if ($clinic !== null) {
                $hostClinicId = (int)$clinic['id'];
            }
        }

        $this->container->set('host_clinic_id', fn () => $hostClinicId);

        if ($userId !== null && !$isSuperAdmin) {
            if ($sessionClinicId === null) {
                return Response::html('Contexto de clínica ausente.', 403);
            }

            if ($hostClinicId !== null && $hostClinicId !== $sessionClinicId) {
                return Response::html('Acesso negado.', 403);
            }
        }

        $activeClinicId = $hostClinicId ?? $sessionClinicId;
        $this->container->set('clinic_id', fn () => $activeClinicId);

        if ($userId !== null) {
            if (is_int($activeClinicId)) {
                $_SESSION['active_clinic_id'] = $activeClinicId;
            } else {
                unset($_SESSION['active_clinic_id']);
            }
        }

        return $next($request);
    }
}
