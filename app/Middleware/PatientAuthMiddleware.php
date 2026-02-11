<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;
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

        $pending = (new LegalDocumentService($this->container))->listPendingRequiredForCurrentPatientUser();
        $_SESSION['portal_required_legal_docs'] = $pending;

        $enforced = [
            '/portal/required-consents',
            '/portal/legal/read',
            '/portal/legal/accept',
            '/portal/logout',
        ];

        if ($pending !== [] && !in_array($path, $enforced, true) && $request->method() !== 'GET') {
            return Response::redirect('/portal/required-consents');
        }

        return $next($request);
    }
}
