<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Portal\PatientAuthService;
use App\Services\Portal\PortalNotificationService;

final class PortalNotificationsController extends Controller
{
    public function index(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $svc = new PortalNotificationService($this->container);
        $items = $svc->list($clinicId, $patientId, $request->ip());

        return $this->view('portal/notifications', [
            'notifications' => $items,
        ]);
    }

    public function markRead(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/portal/notificacoes');
        }

        (new PortalNotificationService($this->container))->markRead($clinicId, $patientId, $id, $request->ip());
        return $this->redirect('/portal/notificacoes');
    }
}
