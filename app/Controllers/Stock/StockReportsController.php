<?php

declare(strict_types=1);

namespace App\Controllers\Stock;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Stock\StockService;

final class StockReportsController extends Controller
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
        $this->authorize('stock.reports.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $from = trim((string)$request->input('from', date('Y-m-01')));
        $to = trim((string)$request->input('to', date('Y-m-d')));

        $svc = new StockService($this->container);
        $data = $svc->reports($from, $to);

        return $this->view('stock/reports', [
            'from' => $data['from'],
            'to' => $data['to'],
            'summary' => $data['summary'],
            'by_material' => $data['by_material'],
            'by_service' => $data['by_service'],
            'by_professional' => $data['by_professional'],
        ]);
    }
}
