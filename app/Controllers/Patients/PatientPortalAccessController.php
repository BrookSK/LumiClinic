<?php

declare(strict_types=1);

namespace App\Controllers\Patients;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Portal\PatientPortalAccessService;

final class PatientPortalAccessController extends Controller
{
    public function show(Request $request)
    {
        $this->authorize('patients.update');

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        $svc = new PatientPortalAccessService($this->container);
        $data = $svc->getAccess($patientId);

        return $this->view('patients/portal_access', [
            'patient_id' => $patientId,
            'patient_user' => $data['patient_user'],
        ]);
    }

    public function ensure(Request $request)
    {
        $this->authorize('patients.update');

        $patientId = (int)$request->input('patient_id', 0);
        $email = trim((string)$request->input('email', ''));

        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        try {
            $svc = new PatientPortalAccessService($this->container);
            $out = $svc->ensureAccessAndCreateReset($patientId, $email, $request->ip(), true);

            $data = $svc->getAccess($patientId);

            return $this->view('patients/portal_access', [
                'patient_id' => $patientId,
                'patient_user' => $data['patient_user'],
                'success' => 'Acesso criado/atualizado. Envie o link de redefinição ao paciente.',
                'reset_token' => $out['reset_token'],
                'reset_url' => $out['reset_url'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            $svc = new PatientPortalAccessService($this->container);
            $data = $svc->getAccess($patientId);

            return $this->view('patients/portal_access', [
                'patient_id' => $patientId,
                'patient_user' => $data['patient_user'],
                'error' => $e->getMessage(),
            ]);
        }
    }
}
