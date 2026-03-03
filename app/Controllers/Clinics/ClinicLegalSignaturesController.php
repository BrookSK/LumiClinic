<?php

declare(strict_types=1);

namespace App\Controllers\Clinics;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\LegalDocumentSignatureRepository;
use App\Services\Auth\AuthService;
use App\Services\Legal\LegalDocumentVersioningService;

final class ClinicLegalSignaturesController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('clinics.read');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/');
        }

        $limit = (int)$request->input('limit', 200);
        $limit = max(50, min(1000, $limit));

        $scope = trim((string)$request->input('scope', 'all'));

        $pdo = $this->container->get(\PDO::class);
        $rows = (new LegalDocumentSignatureRepository($pdo))->listByClinic($clinicId, $scope, $limit);

        return $this->view('clinic/legal_signatures', [
            'rows' => $rows,
            'limit' => $limit,
            'scope' => $scope,
        ]);
    }

    public function show(Request $request)
    {
        $this->authorize('clinics.read');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/');
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/clinic/legal-signatures');
        }

        $pdo = $this->container->get(\PDO::class);
        $row = (new LegalDocumentSignatureRepository($pdo))->findById($clinicId, $id);
        if ($row === null) {
            return $this->redirect('/clinic/legal-signatures');
        }

        $verTitle = (string)($row['version_title'] ?? '');
        $verBody = (string)($row['version_body'] ?? '');
        $storedHash = (string)($row['document_hash_sha256'] ?? '');
        $computedHash = LegalDocumentVersioningService::computeDocumentHashSha256($verTitle, $verBody);

        return $this->view('clinic/legal_signature_view', [
            'row' => $row,
            'hash_ok' => ($storedHash !== '' && hash_equals($storedHash, $computedHash)),
            'computed_hash' => $computedHash,
        ]);
    }
}
