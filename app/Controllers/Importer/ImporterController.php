<?php

declare(strict_types=1);

namespace App\Controllers\Importer;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\Auth\AuthService;
use App\Services\Importer\ClinicorpImporterService;

final class ImporterController extends Controller
{
    private function redirectSuperAdminWithoutClinicContext(): ?Response
    {
        $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;
        if (!$isSuperAdmin) return null;
        $auth = new AuthService($this->container);
        if ($auth->clinicId() === null) return $this->redirect('/sys/clinics');
        return null;
    }

    public function index(Request $request): Response
    {
        $this->authorize('settings.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) return $redirect;

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) throw new \RuntimeException('Contexto inválido.');

        $service = new ClinicorpImporterService($this->container);
        $history = $service->getImportHistory($clinicId);

        return $this->view('settings/importer', [
            'types'   => ClinicorpImporterService::TYPES,
            'history' => $history,
            'result'  => null,
        ]);
    }

    public function upload(Request $request): Response
    {
        $this->authorize('settings.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) return $redirect;

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) throw new \RuntimeException('Contexto inválido.');

        $type = trim((string)$request->input('import_type', ''));
        if (!isset(ClinicorpImporterService::TYPES[$type])) {
            return $this->redirect('/settings/importer');
        }

        // Check if type is under construction
        $meta = ClinicorpImporterService::TYPES[$type];
        if (($meta['status'] ?? '') === 'construction') {
            return $this->redirect('/settings/importer');
        }

        $file = $_FILES['xlsx_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] < 100) {
            return $this->view('settings/importer', [
                'types'   => ClinicorpImporterService::TYPES,
                'history' => (new ClinicorpImporterService($this->container))->getImportHistory($clinicId),
                'result'  => ['imported' => 0, 'skipped' => 0, 'errors' => ['Arquivo inválido ou não enviado.']],
                'selected_type' => $type,
            ]);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') {
            return $this->view('settings/importer', [
                'types'   => ClinicorpImporterService::TYPES,
                'history' => (new ClinicorpImporterService($this->container))->getImportHistory($clinicId),
                'result'  => ['imported' => 0, 'skipped' => 0, 'errors' => ['Apenas arquivos .xlsx são aceitos.']],
                'selected_type' => $type,
            ]);
        }

        $service = new ClinicorpImporterService($this->container);
        $result = $service->import($clinicId, $userId, $type, $file['tmp_name'], $file['name']);

        return $this->view('settings/importer', [
            'types'   => ClinicorpImporterService::TYPES,
            'history' => $service->getImportHistory($clinicId),
            'result'  => $result,
            'selected_type' => $type,
        ]);
    }
}
