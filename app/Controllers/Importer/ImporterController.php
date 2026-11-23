<?php

declare(strict_types=1);

namespace App\Controllers\Importer;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\Auth\AuthService;
use App\Services\Compliance\DataExportService;
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

    /** Detalhes de uma importação: quem foi ignorado, o motivo e os erros. */
    public function logDetail(Request $request): Response
    {
        $this->authorize('settings.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) return $redirect;

        $clinicId = (new AuthService($this->container))->clinicId();
        if ($clinicId === null) throw new \RuntimeException('Contexto inválido.');

        $logId = (int)$request->input('id', 0);
        $log = $logId > 0 ? (new ClinicorpImporterService($this->container))->getImportLog($clinicId, $logId) : null;
        if ($log === null) {
            return $this->redirect('/settings/importer');
        }

        return $this->view('settings/importer_log', [
            'types' => ClinicorpImporterService::TYPES,
            'log'   => $log,
        ]);
    }

    /** CSV com os registros ignorados e os erros de uma importação. */
    public function logExportCsv(Request $request): Response
    {
        $this->authorize('settings.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) return $redirect;

        $clinicId = (new AuthService($this->container))->clinicId();
        if ($clinicId === null) throw new \RuntimeException('Contexto inválido.');

        $logId = (int)$request->input('id', 0);
        $log = $logId > 0 ? (new ClinicorpImporterService($this->container))->getImportLog($clinicId, $logId) : null;
        if ($log === null) {
            return $this->redirect('/settings/importer');
        }

        $cell = static function (string|int $value): string {
            $value = str_replace(["\r\n", "\n", "\r"], ' ', (string)$value);
            // Evita injeção de fórmulas ao abrir no Excel/LibreOffice.
            if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
                $value = "'" . $value;
            }
            return '"' . str_replace('"', '""', $value) . '"';
        };

        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        $csv .= "Linha;Identificação;Motivo;Tipo\n";

        $skippedList = is_array($log['skipped_list']) ? $log['skipped_list'] : [];
        foreach ($skippedList as $s) {
            $csv .= implode(';', [
                $cell((int)$s['line']),
                $cell((string)$s['name']),
                $cell((string)$s['reason']),
                $cell('Ignorado'),
            ]) . "\n";
        }

        foreach ($log['errors_list'] as $err) {
            $line = '';
            $msg = (string)$err;
            if (preg_match('/^Linha (\d+):\s*(.*)$/u', $msg, $m)) {
                $line = $m[1];
                $msg = $m[2];
            }
            $csv .= implode(';', [$cell($line), $cell(''), $cell($msg), $cell('Erro')]) . "\n";
        }

        $filename = 'importacao_' . $logId . '_ignorados_' . date('Ymd_His') . '.csv';

        (new DataExportService($this->container))->record(
            'importer.log.export', 'clinicorp_import_log', $logId, 'csv', $filename,
            [
                'import_type' => (string)$log['import_type'],
                'skipped' => count($skippedList),
                'errors' => count($log['errors_list']),
            ],
            $request->ip(), $request->header('user-agent')
        );

        return Response::raw($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
