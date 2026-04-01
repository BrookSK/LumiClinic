<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\LegalDocumentRepository;
use App\Repositories\RoleRepository;
use App\Services\Auth\AuthService;

/**
 * Tela unificada de documentos legais (LGPD).
 * Consolida: termos do portal do paciente + termos da equipe interna.
 */
final class LegalDocumentsUnifiedController extends Controller
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
        $repo = new LegalDocumentRepository($pdo);

        $portalDocs = $repo->listByClinicForPatientPortal($clinicId);
        $systemDocs = $repo->listByClinicForSystemUsers($clinicId);

        return $this->view('settings/legal_documents_unified', [
            'portal_docs' => $portalDocs,
            'system_docs' => $systemDocs,
            'error'   => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
        ]);
    }

    public function edit(Request $request)
    {
        $this->authorize('clinics.read');

        $id = (int)$request->input('id', 0);
        $scope = trim((string)$request->input('scope', 'patient_portal'));

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new LegalDocumentRepository($pdo);
        $roles = (new RoleRepository($pdo))->listByClinic($clinicId);

        $doc = null;
        if ($id > 0) {
            $doc = $repo->findById($id);
            if ($doc === null || (int)($doc['clinic_id'] ?? 0) !== $clinicId) {
                $doc = null;
            } else {
                $scope = (string)($doc['scope'] ?? $scope);
            }
        }

        return $this->view('settings/legal_document_edit_unified', [
            'doc'   => $doc,
            'scope' => $scope,
            'roles' => $roles,
            'error'   => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
        ]);
    }

    public function save(Request $request)
    {
        $this->authorize('clinics.read');

        $id       = (int)$request->input('id', 0);
        $scope    = trim((string)$request->input('scope', 'patient_portal'));
        $title    = trim((string)$request->input('title', ''));
        $body     = trim((string)$request->input('body', ''));
        $status   = trim((string)$request->input('status', 'active'));
        $isRequired = (string)$request->input('is_required', '') === '1';
        $targetRole = trim((string)$request->input('target_role_code', ''));

        if ($title === '' || $body === '') {
            return $this->redirect('/settings/lgpd/edit?id=' . $id . '&scope=' . urlencode($scope) . '&error=' . urlencode('Preencha título e conteúdo.'));
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

        if ($scope === 'system_user') {
            if ($id > 0) {
                $repo->updateForSystemUsers($clinicId, $id, ($targetRole === '' ? null : $targetRole), $title, $body, $isRequired, $status);
            } else {
                $id = $repo->createForSystemUsers($clinicId, ($targetRole === '' ? null : $targetRole), $title, $body, $isRequired, $status);
            }
        } else {
            // patient_portal (default)
            if ($id > 0) {
                $repo->updateForPatientPortal($clinicId, $id, $title, $body, $isRequired, $status);
            } else {
                $id = $repo->createForPatientPortal($clinicId, $title, $body, $isRequired, $status);
            }
        }

        return $this->redirect('/settings/lgpd/edit?id=' . $id . '&scope=' . urlencode($scope) . '&success=' . urlencode('Salvo.'));
    }
}
