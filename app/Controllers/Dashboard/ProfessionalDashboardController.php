<?php

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;

final class ProfessionalDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        return Response::json(['ok' => true, 'message' => 'professional dashboard']);
    }
}
