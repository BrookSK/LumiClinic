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

        $tab = trim((string)$request->input('tab', 'materials'));
        $allowedTabs = ['materials', 'movements', 'categories', 'units'];
        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'materials';
        }

        $svc = new StockService($this->container);
        $meta = new MaterialMetaService($this->container);

        $data = [
            'tab' => $tab,
            'items' => $svc->listMaterials(),
            'categories' => $meta->listActiveCategories(),
            'units' => $meta->listActiveUnits(),
            'error' => trim((string)$request->input('error', '')),
            'from' => null,
            'to' => null,
            'movements' => [],
            'categories_all' => [],
            'units_all' => [],
            'page' => 1,
            'per_page' => 100,
            'has_next' => false,
        ];

        if ($tab === 'movements') {
            $from = trim((string)$request->input('from', date('Y-m-01')));
            $to = trim((string)$request->input('to', date('Y-m-d')));
            $page = (int)$request->input('page', 1);
            $perPage = (int)$request->input('per_page', 100);

            $page = max(1, $page);
            $perPage = max(25, min(200, $perPage));
            $offset = ($page - 1) * $perPage;

            $mvData = $svc->listMovements($from, $to, $perPage + 1, $offset);
            $hasNext = count($mvData['movements']) > $perPage;
            if ($hasNext) {
                $mvData['movements'] = array_slice($mvData['movements'], 0, $perPage);
            }

            $data['from'] = $mvData['from'];
            $data['to'] = $mvData['to'];
            $data['movements'] = $mvData['movements'];
            $data['page'] = $page;
            $data['per_page'] = $perPage;
            $data['has_next'] = $hasNext;
        } elseif ($tab === 'categories') {
            $data['categories_all'] = $meta->listCategories();
        } elseif ($tab === 'units') {
            $data['units_all'] = $meta->listUnits();
        }

        return $this->view('stock/materials', $data);
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
        $initialStock = trim((string)$request->input('initial_stock', '0'));
        $unitCost = trim((string)$request->input('unit_cost', '0'));
        $validity = trim((string)$request->input('validity_date', ''));

        try {
            $svc = new StockService($this->container);
            $svc->createMaterial(
                $name,
                $category === '' ? null : $category,
                $unit,
                $stockMin,
                $initialStock,
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
