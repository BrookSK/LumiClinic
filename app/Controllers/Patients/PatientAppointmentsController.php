<?php

declare(strict_types=1);

namespace App\Controllers\Patients;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\AppointmentRepository;
use App\Services\Auth\AuthService;
use App\Services\Patients\PatientService;

final class PatientAppointmentsController extends Controller
{
    private function redirectSuperAdminWithoutClinicContext(): ?\App\Core\Http\Response
    {
        $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;
        if (!$isSuperAdmin) {
            return null;
        }

        $auth = new AuthService($this->container);
        if ($auth->clinicId() === null) {
            return $this->redirect('/sys/clinics');
        }

        return null;
    }

    public function index(Request $request)
    {
        $this->authorize('patients.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        $patient = (new PatientService($this->container))->get($patientId, $request->ip());
        if ($patient === null) {
            return $this->redirect('/patients');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 50);
        $page = max(1, $page);
        $perPage = max(25, min(200, $perPage));
        $offset = ($page - 1) * $perPage;

        $status = trim((string)$request->input('status', 'all'));
        $startDate = trim((string)$request->input('start_date', ''));
        $endDate = trim((string)$request->input('end_date', ''));

        $repo = new AppointmentRepository($this->container->get(\PDO::class));
        $items = $repo->listByClinicPatientDetailed($clinicId, $patientId, $perPage + 1, $offset, $status, $startDate, $endDate);
        $hasNext = count($items) > $perPage;
        if ($hasNext) {
            $items = array_slice($items, 0, $perPage);
        }

        return $this->view('patients/appointments', [
            'patient' => $patient,
            'items' => $items,
            'page' => $page,
            'per_page' => $perPage,
            'has_next' => $hasNext,
            'status' => $status,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }
}
