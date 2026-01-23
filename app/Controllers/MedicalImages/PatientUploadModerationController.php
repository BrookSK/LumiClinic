<?php

declare(strict_types=1);

namespace App\Controllers\MedicalImages;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\MedicalImages\PatientUploadModerationService;

final class PatientUploadModerationController extends Controller
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
        $this->authorize('medical_images.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $svc = new PatientUploadModerationService($this->container);
        $pending = $svc->listPending();

        return $this->view('medical-images/moderation', [
            'pending' => $pending,
        ]);
    }

    public function approve(Request $request)
    {
        $this->authorize('medical_images.upload');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $note = trim((string)$request->input('note', ''));
        if ($id <= 0) {
            return $this->redirect('/medical-images/moderation');
        }

        (new PatientUploadModerationService($this->container))->approve($id, $note === '' ? null : $note, $request->ip());
        return $this->redirect('/medical-images/moderation');
    }

    public function reject(Request $request)
    {
        $this->authorize('medical_images.upload');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $note = trim((string)$request->input('note', ''));
        if ($id <= 0) {
            return $this->redirect('/medical-images/moderation');
        }

        (new PatientUploadModerationService($this->container))->reject($id, $note === '' ? null : $note, $request->ip());
        return $this->redirect('/medical-images/moderation');
    }
}
