<?php

declare(strict_types=1);

namespace App\Controllers\Audit;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\AuditLogRepository;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\AuthService;

final class AuditLogController extends Controller
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
        $this->authorize('audit.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $service = new AuditLogService($this->container);

        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 50);
        $page = max(1, $page);
        $perPage = max(25, min(200, $perPage));
        $offset = ($page - 1) * $perPage;

        $filters = [
            'action' => trim((string)$request->input('action', '')),
            'from' => trim((string)$request->input('from', '')),
            'to' => trim((string)$request->input('to', '')),
        ];

        return $this->view('audit/index', [
            'items' => (function () use ($service, $filters, $perPage, $offset) {
                $rows = $service->list($filters, $perPage + 1, $offset);
                $hasNext = count($rows) > $perPage;
                if ($hasNext) {
                    $rows = array_slice($rows, 0, $perPage);
                }
                return ['rows' => $rows, 'has_next' => $hasNext];
            })(),
            'filters' => $filters,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('audit.export');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $service = new AuditLogService($this->container);

        $filters = [
            'action' => trim((string)$request->input('action', '')),
            'from' => trim((string)$request->input('from', '')),
            'to' => trim((string)$request->input('to', '')),
        ];

        $rows = $service->list($filters, 5000);

        $auth = new AuthService($this->container);
        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($auth->userId(), $auth->clinicId(), 'audit.export', $filters, $request->ip(), $roleCodes, null, null, $request->header('user-agent'));

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['id', 'created_at', 'user_id', 'action', 'ip_address', 'meta_json']);

        foreach ($rows as $r) {
            fputcsv($out, [
                (string)($r['id'] ?? ''),
                (string)($r['created_at'] ?? ''),
                (string)($r['user_id'] ?? ''),
                (string)($r['action'] ?? ''),
                (string)($r['ip_address'] ?? ''),
                (string)($r['meta_json'] ?? ''),
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $filename = 'audit_logs_' . date('Ymd_His') . '.csv';

        return Response::raw((string)$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
