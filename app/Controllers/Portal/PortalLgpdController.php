<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Portal\PatientAuthService;
use App\Services\Portal\PortalLgpdService;

final class PortalLgpdController extends Controller
{
    public function index(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $svc = new PortalLgpdService($this->container);
        $items = $svc->list($clinicId, $patientId, $request->ip());

        return $this->view('portal/lgpd', [
            'requests' => $items,
        ]);
    }

    public function submit(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $type = trim((string)$request->input('type', ''));
        $note = trim((string)$request->input('note', ''));

        try {
            (new PortalLgpdService($this->container))->request($clinicId, $patientId, $type, $note === '' ? null : $note, $request->ip());
            return $this->redirect('/portal/lgpd');
        } catch (\RuntimeException $e) {
            $svc = new PortalLgpdService($this->container);
            $items = $svc->list($clinicId, $patientId, $request->ip());
            return $this->view('portal/lgpd', [
                'error' => $e->getMessage(),
                'requests' => $items,
            ]);
        }
    }
}
