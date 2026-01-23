<?php

declare(strict_types=1);

namespace App\Controllers\Stock;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Stock\StockService;

final class StockController extends Controller
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

    public function movements(Request $request)
    {
        $this->authorize('stock.movements.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $from = trim((string)$request->input('from', date('Y-m-01')));
        $to = trim((string)$request->input('to', date('Y-m-d')));

        $svc = new StockService($this->container);
        $data = $svc->listMovements($from, $to);

        return $this->view('stock/movements', [
            'from' => $data['from'],
            'to' => $data['to'],
            'movements' => $data['movements'],
            'materials' => $svc->listMaterials(),
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function createMovement(Request $request)
    {
        $this->authorize('stock.movements.create');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $materialId = (int)$request->input('material_id', 0);
        $type = trim((string)$request->input('type', ''));
        $qty = trim((string)$request->input('quantity', ''));
        $lossReason = trim((string)$request->input('loss_reason', ''));
        $notes = trim((string)$request->input('notes', ''));

        try {
            $svc = new StockService($this->container);
            $svc->createMovement(
                $materialId,
                $type,
                $qty,
                $lossReason === '' ? null : $lossReason,
                $notes === '' ? null : $notes,
                null,
                null,
                $request->ip()
            );
            return $this->redirect('/stock/movements');
        } catch (\RuntimeException $e) {
            return $this->redirect('/stock/movements?error=' . urlencode($e->getMessage()));
        }
    }
}
