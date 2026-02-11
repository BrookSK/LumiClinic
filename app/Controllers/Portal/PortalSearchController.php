<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Portal\PatientAuthService;
use App\Services\Portal\PortalSearchService;

final class PortalSearchController extends Controller
{
    public function index(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $q = (string)$request->input('q', '');

        $svc = new PortalSearchService($this->container);
        $data = $svc->search($clinicId, $patientId, $q, $request->ip(), $request->header('user-agent'));

        return $this->view('portal/search', [
            'q' => $data['q'],
            'agenda' => $data['agenda'],
            'documentos' => $data['documentos'],
            'notificacoes' => $data['notificacoes'],
            'uploads' => $data['uploads'],
        ]);
    }
}
