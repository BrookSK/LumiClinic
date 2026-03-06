<?php

declare(strict_types=1);

namespace App\Controllers\Compliance;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\AuditLogRepository;
use App\Services\Auth\AuthService;
use App\Services\Compliance\DataExportService;
use App\Services\Compliance\LgpdAuditService;

final class ComplianceLgpdAuditController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('compliance.lgpd.read');

        $from = trim((string)$request->input('from', ''));
        $to = trim((string)$request->input('to', ''));
        $patientId = (int)$request->input('patient_id', 0);
        $userId = (int)$request->input('user_id', 0);

        $svc = new LgpdAuditService($this->container);
        $data = $svc->list([
            'from' => $from,
            'to' => $to,
            'patient_id' => $patientId,
            'user_id' => $userId,
        ], 200, 0);

        $auth = new AuthService($this->container);
        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            $auth->userId(),
            $auth->clinicId(),
            'compliance.lgpd.audit.view',
            ['from' => $from, 'to' => $to, 'patient_id' => $patientId, 'user_id' => $userId],
            $request->ip(),
            isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null,
            null,
            null,
            $request->header('user-agent')
        );

        return $this->view('compliance/lgpd_audit', [
            'from' => $from,
            'to' => $to,
            'patient_id' => $patientId,
            'user_id' => $userId,
            'sensitive' => $data['sensitive'] ?? [],
            'exports' => $data['exports'] ?? [],
        ]);
    }

    public function export(Request $request): Response
    {
        $this->authorize('compliance.lgpd.export');

        $from = trim((string)$request->input('from', ''));
        $to = trim((string)$request->input('to', ''));
        $patientId = (int)$request->input('patient_id', 0);
        $userId = (int)$request->input('user_id', 0);

        $svc = new LgpdAuditService($this->container);
        $data = $svc->list([
            'from' => $from,
            'to' => $to,
            'patient_id' => $patientId,
            'user_id' => $userId,
        ], 1000, 0);

        $out = fopen('php://temp', 'r+');

        fputcsv($out, ['tipo', 'id', 'data_hora', 'user_id', 'action', 'entity_type', 'entity_id', 'ip', 'user_agent', 'meta']);

        foreach (($data['sensitive'] ?? []) as $r) {
            fputcsv($out, [
                'sensitive',
                (string)($r['id'] ?? ''),
                (string)($r['occurred_at'] ?? $r['created_at'] ?? ''),
                (string)($r['user_id'] ?? ''),
                (string)($r['action'] ?? ''),
                (string)($r['entity_type'] ?? ''),
                (string)($r['entity_id'] ?? ''),
                (string)($r['ip_address'] ?? ''),
                (string)($r['user_agent'] ?? ''),
                (string)($r['meta_json'] ?? ''),
            ]);
        }

        foreach (($data['exports'] ?? []) as $r) {
            fputcsv($out, [
                'export',
                (string)($r['id'] ?? ''),
                (string)($r['created_at'] ?? ''),
                (string)($r['user_id'] ?? ''),
                (string)($r['action'] ?? ''),
                (string)($r['entity_type'] ?? ''),
                (string)($r['entity_id'] ?? ''),
                (string)($r['ip_address'] ?? ''),
                (string)($r['user_agent'] ?? ''),
                (string)($r['meta_json'] ?? ''),
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        (new DataExportService($this->container))->record(
            'compliance.lgpd.audit.export',
            $patientId > 0 ? 'patient' : null,
            $patientId > 0 ? $patientId : null,
            'csv',
            null,
            ['from' => $from, 'to' => $to, 'patient_id' => $patientId, 'user_id' => $userId],
            $request->ip(),
            $request->header('user-agent')
        );

        $filename = 'lgpd_audit_' . date('Ymd_His') . '.csv';
        return Response::raw((string)$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
