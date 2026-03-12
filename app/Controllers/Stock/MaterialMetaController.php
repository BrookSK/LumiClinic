<?php

declare(strict_types=1);

namespace App\Controllers\Stock;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Stock\MaterialMetaService;

final class MaterialMetaController extends Controller
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

    public function categories(Request $request)
    {
        $this->authorize('stock.materials.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $svc = new MaterialMetaService($this->container);
        return $this->view('stock/material_categories', [
            'items' => $svc->listCategories(),
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function createCategory(Request $request)
    {
        $this->authorize('stock.materials.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $name = trim((string)$request->input('name', ''));
        $returnTo = trim((string)$request->input('return_to', ''));

        try {
            (new MaterialMetaService($this->container))->createCategory($name, $request->ip());
            if ($returnTo !== '' && str_starts_with($returnTo, '/')) {
                return $this->redirect($returnTo);
            }
            return $this->redirect('/stock/categories');
        } catch (\RuntimeException $e) {
            $err = urlencode($e->getMessage());
            if ($returnTo !== '' && str_starts_with($returnTo, '/')) {
                $sep = str_contains($returnTo, '?') ? '&' : '?';
                return $this->redirect($returnTo . $sep . 'error=' . $err);
            }
            return $this->redirect('/stock/categories?error=' . $err);
        }
    }

    public function deleteCategory(Request $request)
    {
        $this->authorize('stock.materials.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $returnTo = trim((string)$request->input('return_to', ''));

        try {
            (new MaterialMetaService($this->container))->deleteCategory($id, $request->ip());
            if ($returnTo !== '' && str_starts_with($returnTo, '/')) {
                return $this->redirect($returnTo);
            }
            return $this->redirect('/stock/categories');
        } catch (\RuntimeException $e) {
            $err = urlencode($e->getMessage());
            if ($returnTo !== '' && str_starts_with($returnTo, '/')) {
                $sep = str_contains($returnTo, '?') ? '&' : '?';
                return $this->redirect($returnTo . $sep . 'error=' . $err);
            }
            return $this->redirect('/stock/categories?error=' . $err);
        }
    }

    public function units(Request $request)
    {
        $this->authorize('stock.materials.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $svc = new MaterialMetaService($this->container);
        return $this->view('stock/material_units', [
            'items' => $svc->listUnits(),
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function createUnit(Request $request)
    {
        $this->authorize('stock.materials.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $code = trim((string)$request->input('code', ''));
        $name = trim((string)$request->input('name', ''));
        $returnTo = trim((string)$request->input('return_to', ''));

        try {
            (new MaterialMetaService($this->container))->createUnit($code, $name === '' ? null : $name, $request->ip());
            if ($returnTo !== '' && str_starts_with($returnTo, '/')) {
                return $this->redirect($returnTo);
            }
            return $this->redirect('/stock/units');
        } catch (\RuntimeException $e) {
            $err = urlencode($e->getMessage());
            if ($returnTo !== '' && str_starts_with($returnTo, '/')) {
                $sep = str_contains($returnTo, '?') ? '&' : '?';
                return $this->redirect($returnTo . $sep . 'error=' . $err);
            }
            return $this->redirect('/stock/units?error=' . $err);
        }
    }

    public function deleteUnit(Request $request)
    {
        $this->authorize('stock.materials.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $returnTo = trim((string)$request->input('return_to', ''));

        try {
            (new MaterialMetaService($this->container))->deleteUnit($id, $request->ip());
            if ($returnTo !== '' && str_starts_with($returnTo, '/')) {
                return $this->redirect($returnTo);
            }
            return $this->redirect('/stock/units');
        } catch (\RuntimeException $e) {
            $err = urlencode($e->getMessage());
            if ($returnTo !== '' && str_starts_with($returnTo, '/')) {
                $sep = str_contains($returnTo, '?') ? '&' : '?';
                return $this->redirect($returnTo . $sep . 'error=' . $err);
            }
            return $this->redirect('/stock/units?error=' . $err);
        }
    }
}
