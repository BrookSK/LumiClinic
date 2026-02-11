<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Legal\LegalDocumentService;
use App\Services\Portal\PatientAuthService;

final class PortalLegalDocumentsController extends Controller
{
    public function required(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        if ($auth->patientUserId() === null) {
            return $this->redirect('/portal/login');
        }

        $svc = new LegalDocumentService($this->container);
        $pending = $svc->listPendingRequiredForCurrentPatientUser();

        return $this->view('portal/legal_required', [
            'pending' => $pending,
        ]);
    }

    public function read(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null || $auth->patientUserId() === null) {
            return $this->redirect('/portal/login');
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/portal/required-consents');
        }

        $svc = new LegalDocumentService($this->container);
        $docs = $svc->listActivePatientPortalDocuments($clinicId);
        $doc = null;
        foreach ($docs as $d) {
            if ((int)($d['id'] ?? 0) === $id) {
                $doc = $d;
                break;
            }
        }

        if ($doc === null) {
            return $this->redirect('/portal/required-consents');
        }

        return $this->view('portal/legal_read', [
            'doc' => $doc,
        ]);
    }

    public function accept(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        if ($auth->patientUserId() === null) {
            return $this->redirect('/portal/login');
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/portal/required-consents');
        }

        try {
            (new LegalDocumentService($this->container))->acceptForCurrentPatientUser($id, $request->ip(), $request->header('user-agent'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/portal/required-consents?error=' . urlencode($e->getMessage()));
        }

        $ref = (string)($request->header('referer') ?? '');
        $path = (string)parse_url($ref, PHP_URL_PATH);
        if ($path !== '' && str_starts_with($path, '/portal')) {
            return $this->redirect($path);
        }

        return $this->redirect('/portal/required-consents');
    }
}
