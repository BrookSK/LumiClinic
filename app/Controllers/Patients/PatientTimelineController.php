<?php

declare(strict_types=1);

namespace App\Controllers\Patients;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\AuditLogRepository;
use App\Services\Auth\AuthService;
use App\Services\Compliance\DataExportService;
use App\Services\Compliance\SensitiveDataAuditService;
use App\Services\Patients\PatientTimelineService;

final class PatientTimelineController extends Controller
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
        $this->authorize('patients.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        $types = trim((string)$request->input('types', ''));
        $from = trim((string)$request->input('from', ''));
        $to = trim((string)$request->input('to', ''));
        $limit = (int)$request->input('limit', 200);

        $svc = new PatientTimelineService($this->container);
        $data = $svc->list($patientId, ['types' => $types, 'from' => $from, 'to' => $to, 'limit' => $limit], $request->ip(), $request->header('user-agent'));

        return $this->view('patients/timeline', $data);
    }

    public function exportCsv(Request $request)
    {
        $this->authorize('patients.export');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        $types = trim((string)$request->input('types', ''));
        $from = trim((string)$request->input('from', ''));
        $to = trim((string)$request->input('to', ''));

        $svc = new PatientTimelineService($this->container);
        $data = $svc->list($patientId, ['types' => $types, 'from' => $from, 'to' => $to, 'limit' => 5000], $request->ip(), $request->header('user-agent'));

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        $filtersMeta = [
            'patient_id' => $patientId,
            'types' => $types,
            'from' => $from,
            'to' => $to,
        ];

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'patients.timeline.export', $filtersMeta, $request->ip(), $roleCodes, 'patient', $patientId, $request->header('user-agent'));

        (new SensitiveDataAuditService($this->container))->access(
            'sensitive.export',
            'patient',
            $patientId,
            ['module' => 'patient_timeline', 'action' => 'export_csv', 'patient_id' => $patientId, 'filters' => $filtersMeta],
            $request->ip(),
            $request->header('user-agent')
        );

        $filename = 'patient_timeline_' . $patientId . '_' . date('Ymd_His') . '.csv';

        (new DataExportService($this->container))->record(
            'patients.timeline.export',
            'patient',
            $patientId,
            'csv',
            $filename,
            $filtersMeta,
            $request->ip(),
            $request->header('user-agent')
        );

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['occurred_at', 'type', 'title', 'description', 'link', 'ref_json']);

        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        foreach ($items as $it) {
            $refJson = '';
            if (isset($it['ref']) && is_array($it['ref'])) {
                $enc = json_encode($it['ref'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($enc !== false) {
                    $refJson = $enc;
                }
            }

            fputcsv($out, [
                (string)($it['occurred_at'] ?? ''),
                (string)($it['type'] ?? ''),
                (string)($it['title'] ?? ''),
                (string)($it['description'] ?? ''),
                (string)($it['link'] ?? ''),
                $refJson,
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return Response::raw((string)$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
