<?php

declare(strict_types=1);

namespace App\Controllers\Stock;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Stock\StockService;

final class StockAlertsController extends Controller
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
        $this->authorize('stock.alerts.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $days = (int)$request->input('days', 30);
        if ($days <= 0 || $days > 365) {
            $days = 30;
        }

        $svc = new StockService($this->container);
        $data = $svc->alerts($days);

        return $this->view('stock/alerts', [
            'days' => $days,
            'low_stock' => $data['low_stock'],
            'out_of_stock' => $data['out_of_stock'],
            'expiring_soon' => $data['expiring_soon'],
            'expired' => $data['expired'],
        ]);
    }
}
