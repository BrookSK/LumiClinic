<?php

declare(strict_types=1);

namespace App\Controllers\Clinics;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\LegalDocumentRepository;
use App\Services\Auth\AuthService;

final class ClinicLegalDocumentsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('clinics.read');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/');
        }

        $pdo = $this->container->get(\PDO::class);
        $rows = (new LegalDocumentRepository($pdo))->listByClinicForPatientPortal($clinicId);

        return $this->view('clinic/legal_documents', [
            'rows' => $rows,
        ]);
    }

    public function edit(Request $request)
    {
        $this->authorize('clinics.read');

        $id = (int)$request->input('id', 0);

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new LegalDocumentRepository($pdo);

        $doc = null;
        if ($id > 0) {
            $doc = $repo->findById($id);
            if ($doc === null || (int)($doc['clinic_id'] ?? 0) !== $clinicId || (string)($doc['scope'] ?? '') !== 'patient_portal') {
                $doc = null;
            }
        }

        return $this->view('clinic/legal_documents_edit', [
            'doc' => $doc,
        ]);
    }

    public function save(Request $request)
    {
        $this->authorize('clinics.read');

        $id = (int)$request->input('id', 0);
        $title = trim((string)$request->input('title', ''));
        $body = trim((string)$request->input('body', ''));
        $status = trim((string)$request->input('status', 'active'));
        $isRequired = (string)$request->input('is_required', '') === '1';

        if ($title === '' || $body === '') {
            return $this->redirect('/clinic/legal-documents/edit?id=' . $id . '&error=' . urlencode('Preencha os campos obrigatórios.'));
        }

        if (!in_array($status, ['active', 'disabled'], true)) {
            $status = 'active';
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new LegalDocumentRepository($pdo);

        if ($id > 0) {
            $repo->updateForPatientPortal($clinicId, $id, $title, $body, $isRequired, $status);
            return $this->redirect('/clinic/legal-documents/edit?id=' . $id . '&success=' . urlencode('Salvo.'));
        }

        $newId = $repo->createForPatientPortal($clinicId, $title, $body, $isRequired, $status);
        return $this->redirect('/clinic/legal-documents/edit?id=' . $newId . '&success=' . urlencode('Criado.'));
    }
}
