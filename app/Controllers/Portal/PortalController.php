<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Portal\PatientAuthService;
use App\Services\Portal\PortalDashboardService;

final class PortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $auth = new PatientAuthService($this->container);

        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $data = (new PortalDashboardService($this->container))->dashboard($clinicId, $patientId);

        return $this->view('portal/dashboard', [
            'patient_id' => $patientId,
            'clinic_id' => $clinicId,
            'upcoming_appointments' => $data['upcoming_appointments'],
            'notifications' => $data['notifications'],
        ]);
    }
}
