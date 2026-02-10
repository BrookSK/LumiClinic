<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\ClinicRepository;
use App\Repositories\PatientRepository;
use App\Services\Portal\PatientAuthService;

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

        return $this->view('portal/profile', [
            'patient' => $patient,
            'clinic' => $clinic,
        ]);
    }
}
