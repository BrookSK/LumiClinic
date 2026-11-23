<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;
use App\Repositories\PatientUserRepository;
use App\Services\Legal\LegalDocumentService;
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
            '/portal/register',
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

        // Paciente excluido perde a sessao do portal imediatamente
        if ((new PatientUserRepository($this->container->get(\PDO::class)))->findByIdGlobal((int)$auth->patientUserId()) === null) {
            unset($_SESSION['patient_user_id'], $_SESSION['patient_id'], $_SESSION['patient_two_factor_required'], $_SESSION['patient_name']);
            if (!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] <= 0) {
                unset($_SESSION['clinic_id']);
            }
            return Response::redirect('/portal/login');
        }

        $pending = (new LegalDocumentService($this->container))->listPendingRequiredForCurrentPatientUser();
        $_SESSION['portal_required_legal_docs'] = $pending;

        $enforced = [
            '/portal/required-consents',
            '/portal/legal/read',
            '/portal/legal/accept',
            '/portal/legal/sign',
            '/portal/logout',
        ];

        if ($pending !== [] && !in_array($path, $enforced, true) && $request->method() !== 'GET') {
            return Response::redirect('/portal/required-consents');
        }

        return $next($request);
    }
}
