<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\ClinicRepository;
use App\Repositories\PatientRepository;
use App\Repositories\PatientProfileChangeRequestRepository;
use App\Services\Portal\PatientAuthService;
use App\Services\Portal\PatientProfileChangeRequestService;

final class PortalProfileController extends Controller
{
    public function index(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $pdo = $this->container->get(\PDO::class);
        $patient = (new PatientRepository($pdo))->findClinicalById($clinicId, $patientId);
        $clinic = (new ClinicRepository($pdo))->findById($clinicId);
        $pending = (new PatientProfileChangeRequestRepository($pdo))->findLatestPendingByPatient($clinicId, $patientId);

        return $this->view('portal/profile', [
            'patient' => $patient,
            'clinic' => $clinic,
            'pending_request' => $pending,
        ]);
    }

    public function requestChange(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        if ($auth->patientUserId() === null) {
            return $this->redirect('/portal/login');
        }

        try {
            $svc = new PatientProfileChangeRequestService($this->container);
            $svc->createForCurrentPatient([
                'name' => $request->input('name', ''),
                'email' => $request->input('email', ''),
                'phone' => $request->input('phone', ''),
                'birth_date' => $request->input('birth_date', ''),
                'address_street' => $request->input('address_street', ''),
                'address_number' => $request->input('address_number', ''),
                'address_complement' => $request->input('address_complement', ''),
                'address_district' => $request->input('address_district', ''),
                'address_city' => $request->input('address_city', ''),
                'address_state' => $request->input('address_state', ''),
                'address_zip' => $request->input('address_zip', ''),
            ], $request->ip());

            return $this->redirect('/portal/perfil?success=' . urlencode('Solicitação enviada para revisão da clínica.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/portal/perfil?error=' . urlencode($e->getMessage()));
        }
    }
}
