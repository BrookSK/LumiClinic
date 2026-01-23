<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Portal\PatientAuthService;
use App\Services\Portal\PortalUploadService;

final class PortalUploadController extends Controller
{
    public function index(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        $patientUserId = $auth->patientUserId();
        if ($clinicId === null || $patientId === null || $patientUserId === null) {
            return $this->redirect('/portal/login');
        }

        $svc = new PortalUploadService($this->container);
        $uploads = $svc->listUploads($clinicId, $patientId);

        return $this->view('portal/uploads', [
            'uploads' => $uploads,
        ]);
    }

    public function submit(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        $patientUserId = $auth->patientUserId();
        if ($clinicId === null || $patientId === null || $patientUserId === null) {
            return $this->redirect('/portal/login');
        }

        $kind = trim((string)$request->input('kind', 'other'));
        $takenAt = trim((string)$request->input('taken_at', ''));
        $note = trim((string)$request->input('note', ''));

        $file = $_FILES['image'] ?? null;
        if (!is_array($file)) {
            return $this->redirect('/portal/uploads');
        }

        try {
            $svc = new PortalUploadService($this->container);
            $svc->upload($clinicId, $patientId, $patientUserId, [
                'kind' => $kind,
                'taken_at' => ($takenAt === '' ? null : $takenAt),
                'note' => ($note === '' ? null : $note),
            ], $file, $request->ip());

            return $this->redirect('/portal/uploads');
        } catch (\RuntimeException $e) {
            $svc = new PortalUploadService($this->container);
            $uploads = $svc->listUploads($clinicId, $patientId);
            return $this->view('portal/uploads', [
                'error' => $e->getMessage(),
                'uploads' => $uploads,
            ]);
        }
    }
}
