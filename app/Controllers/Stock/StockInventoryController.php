<?php

declare(strict_types=1);

namespace App\Controllers\Stock;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Stock\StockService;

final class StockInventoryController extends Controller
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
        $this->authorize('stock.movements.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $svc = new StockService($this->container);
        return $this->view('stock/inventory', [
            'items' => $svc->listInventories(),
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('stock.movements.create');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $notes = trim((string)$request->input('notes', ''));

        try {
            $svc = new StockService($this->container);
            $id = $svc->createInventory($notes === '' ? null : $notes, $request->ip());
            return $this->redirect('/stock/inventory/edit?id=' . (int)$id);
        } catch (\RuntimeException $e) {
            return $this->redirect('/stock/inventory?error=' . urlencode($e->getMessage()));
        }
    }

    public function edit(Request $request)
    {
        $this->authorize('stock.movements.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/stock/inventory');
        }

        try {
            $svc = new StockService($this->container);
            $data = $svc->getInventory($id);

            return $this->view('stock/inventory_edit', [
                'inventory' => $data['inventory'],
                'items' => $data['items'],
                'error' => trim((string)$request->input('error', '')),
            ]);
        } catch (\RuntimeException $e) {
            return $this->redirect('/stock/inventory?error=' . urlencode($e->getMessage()));
        }
    }

    public function update(Request $request)
    {
        $this->authorize('stock.movements.create');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $qty = $request->input('qty', []);

        if ($id <= 0) {
            return $this->redirect('/stock/inventory');
        }

        if (!is_array($qty)) {
            $qty = [];
        }

        try {
            $svc = new StockService($this->container);
            $svc->updateInventoryCounts($id, $qty, $request->ip());
            return $this->redirect('/stock/inventory/edit?id=' . (int)$id);
        } catch (\RuntimeException $e) {
            return $this->redirect('/stock/inventory/edit?id=' . (int)$id . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function confirm(Request $request)
    {
        $this->authorize('stock.movements.create');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/stock/inventory');
        }

        try {
            $svc = new StockService($this->container);
            $svc->confirmInventory($id, $request->ip());
            return $this->redirect('/stock/inventory/edit?id=' . (int)$id);
        } catch (\RuntimeException $e) {
            return $this->redirect('/stock/inventory/edit?id=' . (int)$id . '&error=' . urlencode($e->getMessage()));
        }
    }
}
