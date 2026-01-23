<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;
use App\Repositories\PatientApiTokenRepository;

final class ApiPatientAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Container $container) {}

    public function handle(Request $request, callable $next): Response
    {
        $path = $request->path();
        if (!str_starts_with($path, '/api/')) {
            return $next($request);
        }

        $authz = (string)($request->header('authorization', '') ?? '');
        $token = '';
        if (stripos($authz, 'Bearer ') === 0) {
            $token = trim(substr($authz, 7));
        }

        if ($token === '') {
            return Response::json(['error' => 'unauthorized'], 401);
        }

        $tokenHash = hash('sha256', $token);

        $repo = new PatientApiTokenRepository($this->container->get(\PDO::class));
        $row = $repo->findValidByTokenHash($tokenHash);
        if ($row === null) {
            return Response::json(['error' => 'unauthorized'], 401);
        }

        $clinicId = (int)$row['clinic_id'];
        $patientId = (int)$row['patient_id'];
        $patientUserId = (int)$row['patient_user_id'];

        $repo->touchLastUsedAt((int)$row['id']);

        $this->container->set('api.patient_clinic_id', fn () => $clinicId);
        $this->container->set('api.patient_id', fn () => $patientId);
        $this->container->set('api.patient_user_id', fn () => $patientUserId);

        return $next($request);
    }
}
