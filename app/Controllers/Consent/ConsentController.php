<?php

declare(strict_types=1);

namespace App\Controllers\Consent;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Consent\ConsentService;

final class ConsentController extends Controller
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

    public function terms(Request $request)
    {
        $this->authorize('consent_terms.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $service = new ConsentService($this->container);
        return $this->view('consent/terms', [
            'terms' => $service->listTerms(),
        ]);
    }

    public function createTerm(Request $request)
    {
        $this->authorize('consent_terms.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $service = new ConsentService($this->container);
        return $this->view('consent/terms-create', [
            'procedure_types' => $service->listProcedureTypes(),
        ]);
    }

    public function storeTerm(Request $request)
    {
        $this->authorize('consent_terms.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $procedureType = trim((string)$request->input('procedure_type', ''));
        $title = trim((string)$request->input('title', ''));
        $body = trim((string)$request->input('body', ''));

        if ($procedureType === '' || $title === '' || $body === '') {
            return $this->view('consent/terms-create', ['error' => 'Preencha todos os campos.']);
        }

        $service = new ConsentService($this->container);
        $id = $service->createTerm($procedureType, $title, $body, $request->ip());

        return $this->redirect('/consent-terms/edit?id=' . $id);
    }

    public function editTerm(Request $request)
    {
        $this->authorize('consent_terms.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/consent-terms');
        }

        $service = new ConsentService($this->container);
        $term = $service->getTerm($id);
        if ($term === null) {
            return $this->redirect('/consent-terms');
        }

        return $this->view('consent/terms-edit', [
            'term' => $term,
            'procedure_types' => $service->listProcedureTypes(),
        ]);
    }

    public function updateTerm(Request $request)
    {
        $this->authorize('consent_terms.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $procedureType = trim((string)$request->input('procedure_type', ''));
        $title = trim((string)$request->input('title', ''));
        $body = trim((string)$request->input('body', ''));
        $status = trim((string)$request->input('status', 'active'));

        if ($id <= 0 || $procedureType === '' || $title === '' || $body === '') {
            $service = new ConsentService($this->container);
            return $this->view('consent/terms-edit', [
                'term' => $service->getTerm($id),
                'error' => 'Preencha os campos obrigatórios.',
            ]);
        }

        if (!in_array($status, ['active', 'disabled'], true)) {
            $status = 'active';
        }

        $service = new ConsentService($this->container);
        $service->updateTerm($id, $procedureType, $title, $body, $status, $request->ip());

        return $this->redirect('/consent-terms/edit?id=' . $id);
    }

    public function index(Request $request)
    {
        $this->authorize('consent_terms.accept');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        $service = new ConsentService($this->container);
        $data = $service->listForPatient($patientId, $request->ip());

        return $this->view('consent/index', $data);
    }

    public function accept(Request $request)
    {
        $this->authorize('consent_terms.accept');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $termId = (int)$request->input('term_id', 0);
        if ($patientId <= 0 || $termId <= 0) {
            return $this->redirect('/patients');
        }

        $service = new ConsentService($this->container);
        $data = $service->getAcceptForm($patientId, $termId, $request->ip());

        return $this->view('consent/accept', $data);
    }

    public function submit(Request $request)
    {
        $this->authorize('consent_terms.accept');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $termId = (int)$request->input('term_id', 0);
        $signature = (string)$request->input('signature', '');

        if ($patientId <= 0 || $termId <= 0 || trim($signature) === '') {
            return $this->redirect('/consent?patient_id=' . $patientId);
        }

        $service = new ConsentService($this->container);
        $service->accept($patientId, $termId, $signature, $request->ip());

        return $this->redirect('/consent?patient_id=' . $patientId);
    }

    public function signatureFile(Request $request)
    {
        $this->authorize('files.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/patients');
        }

        $service = new ConsentService($this->container);
        return $service->serveSignature($id, $request->ip(), $request->header('user-agent'));
    }
}
