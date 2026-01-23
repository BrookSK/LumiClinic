<?php

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\SystemMetricRepository;

final class PlatformDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
            throw new \RuntimeException('Acesso negado.');
        }

        $items = (new SystemMetricRepository($this->container->get(\PDO::class)))->latestByClinic(null, 50);
        return Response::json(['scope' => 'platform', 'metrics' => $items]);
    }
}
