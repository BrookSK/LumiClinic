<?php

declare(strict_types=1);

namespace App\Controllers\Ai;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;

final class AiController extends Controller
{
    public function insights(Request $request): Response
    {
        return Response::json(['ok' => true, 'insights' => []]);
    }

    public function forecast(Request $request): Response
    {
        return Response::json(['ok' => true, 'forecast' => []]);
    }

    public function anomalies(Request $request): Response
    {
        return Response::json(['ok' => true, 'anomalies' => []]);
    }
}
