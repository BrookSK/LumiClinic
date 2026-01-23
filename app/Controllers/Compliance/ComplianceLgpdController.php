<?php

declare(strict_types=1);

namespace App\Controllers\Compliance;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\Compliance\ComplianceLgpdService;

final class ComplianceLgpdController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('compliance.lgpd.read');

        $status = trim((string)$request->input('status', 'pending'));
        if ($status === '') {
            $status = 'pending';
        }
        if (!in_array($status, ['pending', 'processed', 'rejected', 'all'], true)) {
            $status = 'pending';
        }

        $svc = new ComplianceLgpdService($this->container);
        $items = $svc->listRequests($status === 'all' ? null : $status, 200, $request->ip(), $request->header('user-agent'));

        return $this->view('compliance/lgpd_requests', [
            'items' => $items,
            'status' => $status,
        ]);
    }

    public function process(Request $request)
    {
        $this->authorize('compliance.lgpd.process');

        $id = (int)$request->input('id', 0);
        $decision = trim((string)$request->input('decision', ''));
        $note = trim((string)$request->input('note', ''));

        if ($id <= 0) {
            return $this->redirect('/compliance/lgpd-requests');
        }

        try {
            (new ComplianceLgpdService($this->container))->processRequest($id, $decision, $note === '' ? null : $note, $request->ip(), $request->header('user-agent'));
            return $this->redirect('/compliance/lgpd-requests');
        } catch (\RuntimeException $e) {
            return $this->redirect('/compliance/lgpd-requests?status=pending');
        }
    }

    public function export(Request $request)
    {
        $this->authorize('compliance.lgpd.export');

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/compliance/lgpd-requests');
        }

        $data = (new ComplianceLgpdService($this->container))->exportPatientDataJson($id, $request->ip(), $request->header('user-agent'));
        $filename = 'lgpd_export_' . $id . '_' . date('Ymd_His') . '.json';

        return Response::raw(
            (string)json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            200,
            [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    public function anonymize(Request $request)
    {
        $this->authorize('compliance.lgpd.anonymize');

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/compliance/lgpd-requests');
        }

        try {
            (new ComplianceLgpdService($this->container))->anonymizePatientFromRequest($id, $request->ip(), $request->header('user-agent'));
            return $this->redirect('/compliance/lgpd-requests?status=processed');
        } catch (\RuntimeException $e) {
            return $this->redirect('/compliance/lgpd-requests?status=pending');
        }
    }
}
