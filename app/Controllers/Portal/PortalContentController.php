<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Portal\PatientAuthService;
use App\Services\Portal\PortalContentService;

final class PortalContentController extends Controller
{
    public function index(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $items = (new PortalContentService($this->container))->listForPatient($clinicId, $patientId, $request->ip());

        return $this->view('portal/content', [
            'items' => $items,
        ]);
    }
}
