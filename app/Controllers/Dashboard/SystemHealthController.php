<?php

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;

final class SystemHealthController extends Controller
{
    public function index(Request $request): Response
    {
        if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
            throw new \RuntimeException('Acesso negado.');
        }

        return Response::json(['ok' => true, 'status' => 'healthy']);
    }
}
