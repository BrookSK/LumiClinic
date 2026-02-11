<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\LegalDocumentRepository;
use App\Repositories\SystemClinicRepository;

final class SystemLegalOwnerDocumentsController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
            throw new \RuntimeException('Acesso negado.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin();

        $clinicId = (int)$request->input('clinic_id', -1);
        if ($clinicId < -1) {
            $clinicId = -1;
        }

        $pdo = $this->container->get(\PDO::class);

        $repo = new LegalDocumentRepository($pdo);
        if ($clinicId === -1) {
            $rows = $repo->listForClinicOwnersAll();
        } elseif ($clinicId === 0) {
            $rows = $repo->listForClinicOwnersGlobal();
        } else {
            $rows = $repo->listForClinicOwnersByClinicId($clinicId);
        }
        $clinics = (new SystemClinicRepository($pdo))->listAll();

        return $this->view('system/legal_owner_documents', [
            'rows' => $rows,
            'clinics' => $clinics,
            'clinic_id' => $clinicId,
        ]);
    }

    public function edit(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);

        $pdo = $this->container->get(\PDO::class);
        $repo = new LegalDocumentRepository($pdo);

        $doc = null;
        if ($id > 0) {
            $d = $repo->findById($id);
            if ($d !== null && (string)($d['scope'] ?? '') === 'clinic_owner') {
                $doc = $d;
            }
        }

        $clinics = (new SystemClinicRepository($pdo))->listAll();

        return $this->view('system/legal_owner_documents_edit', [
            'doc' => $doc,
            'clinics' => $clinics,
        ]);
    }

    public function save(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        $clinicId = (int)$request->input('clinic_id', 0);
        $title = trim((string)$request->input('title', ''));
        $body = trim((string)$request->input('body', ''));
        $status = trim((string)$request->input('status', 'active'));
        $isRequired = (string)$request->input('is_required', '') === '1';

        if ($clinicId <= 0) {
            $clinicId = 0;
        }

        if ($title === '' || $body === '') {
            return $this->redirect('/sys/legal-owner-documents/edit?id=' . $id . '&error=' . urlencode('Preencha os campos obrigatórios.'));
        }

        if (!in_array($status, ['active', 'disabled'], true)) {
            $status = 'active';
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new LegalDocumentRepository($pdo);

        $clinicIdOrNull = $clinicId > 0 ? $clinicId : null;

        if ($id > 0) {
            $repo->updateForClinicOwners($id, $clinicIdOrNull, $title, $body, $isRequired, $status);
            return $this->redirect('/sys/legal-owner-documents/edit?id=' . $id . '&success=' . urlencode('Salvo.'));
        }

        $newId = $repo->createForClinicOwners($clinicIdOrNull, $title, $body, $isRequired, $status);
        return $this->redirect('/sys/legal-owner-documents/edit?id=' . $newId . '&success=' . urlencode('Criado.'));
    }
}
