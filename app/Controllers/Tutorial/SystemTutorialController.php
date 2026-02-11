<?php

declare(strict_types=1);

namespace App\Controllers\Tutorial;

use App\Controllers\Controller;
use App\Core\Http\Request;

final class SystemTutorialController extends Controller
{
    public function index(Request $request)
    {
        return $this->view('tutorial/system');
    }
}
