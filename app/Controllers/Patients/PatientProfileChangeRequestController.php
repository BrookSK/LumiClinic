<?php

declare(strict_types=1);

namespace App\Controllers\Patients;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Patients\PatientProfileChangeRequestService;

final class PatientProfileChangeRequestController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('patients.update');

        $status = trim((string)$request->input('status', 'pending'));
        if ($status === '') {
            $status = 'pending';
        }

        $svc = new PatientProfileChangeRequestService($this->container);
        $rows = $svc->listRequests($status, 200, 0);

        return $this->view('patients/profile_requests', [
            'rows' => $rows,
            'status' => $status,
            'error' => null,
            'success' => null,
        ]);
    }

    public function approve(Request $request)
    {
        $this->authorize('patients.update');

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/patients/profile-requests');
        }

        try {
            $svc = new PatientProfileChangeRequestService($this->container);
            $svc->approve($id, $request->ip());
            return $this->redirect('/patients/profile-requests?status=pending');
        } catch (\RuntimeException $e) {
            return $this->redirect('/patients/profile-requests?status=pending&error=' . urlencode($e->getMessage()));
        }
    }

    public function reject(Request $request)
    {
        $this->authorize('patients.update');

        $id = (int)$request->input('id', 0);
        $notes = trim((string)$request->input('notes', ''));
        if ($id <= 0) {
            return $this->redirect('/patients/profile-requests');
        }

        try {
            $svc = new PatientProfileChangeRequestService($this->container);
            $svc->reject($id, ($notes === '' ? null : $notes), $request->ip());
            return $this->redirect('/patients/profile-requests?status=pending');
        } catch (\RuntimeException $e) {
            return $this->redirect('/patients/profile-requests?status=pending&error=' . urlencode($e->getMessage()));
        }
    }
}
