<?php

declare(strict_types=1);

namespace App\Controllers\Tutorial;

use App\Controllers\Controller;
use App\Core\Http\Request;

final class ApiTokensTutorialController extends Controller
{
    public function patient(Request $request)
    {
        return $this->view('tutorial/api_tokens_patient');
    }
}
