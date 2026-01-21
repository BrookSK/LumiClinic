<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http\Request;

final class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return $this->view('dashboard/index');
    }
}
