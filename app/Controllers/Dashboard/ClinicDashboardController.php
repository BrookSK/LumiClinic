<?php

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\SystemMetricRepository;
use App\Services\Auth\AuthService;

final class ClinicDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return Response::json(['error' => 'invalid_context'], 403);
        }

        $items = (new SystemMetricRepository($this->container->get(\PDO::class)))->latestByClinic($clinicId, 50);
        return Response::json(['clinic_id' => $clinicId, 'metrics' => $items]);
    }
}
