<?php

declare(strict_types=1);

namespace App\Controllers\Bi;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Bi\BiService;
use App\Services\Auth\AuthService;
use App\Services\Queue\QueueService;

final class BiController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('bi.read');

        $periodStart = trim((string)$request->input('from', date('Y-m-01')));
        $periodEnd = trim((string)$request->input('to', date('Y-m-d')));

        $svc = new BiService($this->container);
        $data = $svc->dashboard($periodStart, $periodEnd, $request->ip(), $request->header('user-agent'));

        return $this->view('bi/index', $data);
    }

    public function refresh(Request $request)
    {
        $this->authorize('bi.refresh');

        $periodStart = trim((string)$request->input('from', date('Y-m-01')));
        $periodEnd = trim((string)$request->input('to', date('Y-m-d')));

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId !== null) {
            (new QueueService($this->container))->enqueue(
                'bi.refresh_executive',
                [
                    'from' => $periodStart,
                    'to' => $periodEnd,
                    'ip' => $request->ip(),
                    'user_agent' => $request->header('user-agent'),
                ],
                $clinicId,
                'reports'
            );
        } else {
            (new BiService($this->container))->refreshExecutiveSnapshot($periodStart, $periodEnd, $request->ip(), $request->header('user-agent'));
        }

        return $this->redirect('/bi?from=' . urlencode($periodStart) . '&to=' . urlencode($periodEnd));
    }
}
