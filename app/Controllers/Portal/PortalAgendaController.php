<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Portal\PatientAuthService;
use App\Services\Portal\PortalAgendaService;

final class PortalAgendaController extends Controller
{
    public function index(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 10);
        $page = max(1, $page);
        $perPage = max(5, min(25, $perPage));
        $offset = ($page - 1) * $perPage;

        $svc = new PortalAgendaService($this->container);
        $data = $svc->agenda($clinicId, $patientId, $perPage + 1, $offset);

        $hasNext = count($data['appointments']) > $perPage;
        if ($hasNext) {
            $data['appointments'] = array_slice($data['appointments'], 0, $perPage);
        }

        return $this->view('portal/agenda', [
            'appointments' => $data['appointments'],
            'pending_requests' => $data['pending_requests'],
            'page' => $page,
            'per_page' => $perPage,
            'has_next' => $hasNext,
        ]);
    }

    public function confirm(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $appointmentId = (int)$request->input('appointment_id', 0);
        if ($appointmentId <= 0) {
            return $this->redirect('/portal/agenda');
        }

        try {
            (new PortalAgendaService($this->container))->confirm($clinicId, $patientId, $appointmentId, $request->ip());
            return $this->redirect('/portal/agenda');
        } catch (\RuntimeException $e) {
            $svc = new PortalAgendaService($this->container);
            $data = $svc->agenda($clinicId, $patientId);
            return $this->view('portal/agenda', [
                'error' => $e->getMessage(),
                'appointments' => $data['appointments'],
                'pending_requests' => $data['pending_requests'],
            ]);
        }
    }

    public function requestCancel(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $appointmentId = (int)$request->input('appointment_id', 0);
        $note = trim((string)$request->input('note', ''));

        if ($appointmentId <= 0) {
            return $this->redirect('/portal/agenda');
        }

        try {
            (new PortalAgendaService($this->container))->requestCancel($clinicId, $patientId, $appointmentId, $note, $request->ip());
            return $this->redirect('/portal/agenda');
        } catch (\RuntimeException $e) {
            $svc = new PortalAgendaService($this->container);
            $data = $svc->agenda($clinicId, $patientId);
            return $this->view('portal/agenda', [
                'error' => $e->getMessage(),
                'appointments' => $data['appointments'],
                'pending_requests' => $data['pending_requests'],
            ]);
        }
    }

    public function requestReschedule(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $appointmentId = (int)$request->input('appointment_id', 0);
        $requestedStartAt = trim((string)$request->input('requested_start_at', ''));
        $note = trim((string)$request->input('note', ''));

        if ($appointmentId <= 0) {
            return $this->redirect('/portal/agenda');
        }

        try {
            (new PortalAgendaService($this->container))->requestReschedule($clinicId, $patientId, $appointmentId, $requestedStartAt, $note, $request->ip());
            return $this->redirect('/portal/agenda');
        } catch (\RuntimeException $e) {
            $svc = new PortalAgendaService($this->container);
            $data = $svc->agenda($clinicId, $patientId);
            return $this->view('portal/agenda', [
                'error' => $e->getMessage(),
                'appointments' => $data['appointments'],
                'pending_requests' => $data['pending_requests'],
            ]);
        }
    }
}
