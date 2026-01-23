<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Portal\PatientAuthService;
use App\Services\Portal\PortalDocumentsService;

final class PortalDocumentsController extends Controller
{
    public function index(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $svc = new PortalDocumentsService($this->container);
        $data = $svc->list($clinicId, $patientId, $request->ip(), $request->header('user-agent'));

        return $this->view('portal/documents', [
            'acceptances' => $data['acceptances'],
            'signatures' => $data['signatures'],
            'images' => $data['images'],
        ]);
    }

    public function signatureFile(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/portal/documentos');
        }

        return (new PortalDocumentsService($this->container))->serveSignature($clinicId, $patientId, $id, $request->ip(), $request->header('user-agent'));
    }

    public function medicalImageFile(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/portal/documentos');
        }

        return (new PortalDocumentsService($this->container))->serveMedicalImage($clinicId, $patientId, $id, $request->ip(), $request->header('user-agent'));
    }
}
