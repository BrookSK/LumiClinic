<?php

declare(strict_types=1);

namespace App\Controllers\Stock;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Stock\MaterialMetaService;
use App\Services\Stock\StockService;

final class MaterialController extends Controller
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
        $this->authorize('stock.materials.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $svc = new StockService($this->container);
        $meta = new MaterialMetaService($this->container);
        return $this->view('stock/materials', [
            'items' => $svc->listMaterials(),
            'categories' => $meta->listActiveCategories(),
            'units' => $meta->listActiveUnits(),
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function createForm(Request $request)
    {
        return $this->redirect('/stock/materials');
    }

    public function create(Request $request)
    {
        $this->authorize('stock.materials.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $name = trim((string)$request->input('name', ''));
        $category = trim((string)$request->input('category', ''));
        $unit = trim((string)$request->input('unit', ''));
        $stockMin = trim((string)$request->input('stock_minimum', '0'));
        $unitCost = trim((string)$request->input('unit_cost', '0'));
        $validity = trim((string)$request->input('validity_date', ''));

        try {
            $svc = new StockService($this->container);
            $svc->createMaterial(
                $name,
                $category === '' ? null : $category,
                $unit,
                $stockMin,
                $unitCost,
                $validity === '' ? null : $validity,
                $request->ip()
            );
            return $this->redirect('/stock/materials');
        } catch (\RuntimeException $e) {
            return $this->redirect('/stock/materials?error=' . urlencode($e->getMessage()));
        }
    }
}
