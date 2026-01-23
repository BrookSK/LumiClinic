<?php

declare(strict_types=1);

namespace App\Controllers\Patients;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Patients\PatientContentAdminService;

final class PatientContentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('patients.update');

        $svc = new PatientContentAdminService($this->container);
        $data = $svc->index($request->ip());

        return $this->view('patients/content', [
            'contents' => $data['contents'],
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('patients.update');

        $type = trim((string)$request->input('type', 'link'));
        $title = trim((string)$request->input('title', ''));
        $description = trim((string)$request->input('description', ''));
        $url = trim((string)$request->input('url', ''));
        $procedureType = trim((string)$request->input('procedure_type', ''));
        $audience = trim((string)$request->input('audience', ''));

        try {
            (new PatientContentAdminService($this->container))->create($type, $title, $description === '' ? null : $description, $url === '' ? null : $url, $procedureType === '' ? null : $procedureType, $audience === '' ? null : $audience, $request->ip());
            return $this->redirect('/patients/content');
        } catch (\RuntimeException $e) {
            $svc = new PatientContentAdminService($this->container);
            $data = $svc->index($request->ip());
            return $this->view('patients/content', [
                'contents' => $data['contents'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function grant(Request $request)
    {
        $this->authorize('patients.update');

        $patientId = (int)$request->input('patient_id', 0);
        $contentId = (int)$request->input('content_id', 0);
        if ($patientId <= 0 || $contentId <= 0) {
            return $this->redirect('/patients/content');
        }

        try {
            (new PatientContentAdminService($this->container))->grantToPatient($patientId, $contentId, $request->ip());
            return $this->redirect('/patients/content');
        } catch (\RuntimeException $e) {
            $svc = new PatientContentAdminService($this->container);
            $data = $svc->index($request->ip());
            return $this->view('patients/content', [
                'contents' => $data['contents'],
                'error' => $e->getMessage(),
            ]);
        }
    }
}
