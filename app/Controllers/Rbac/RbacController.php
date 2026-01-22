<?php

declare(strict_types=1);

namespace App\Controllers\Rbac;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Rbac\RbacService;

final class RbacController extends Controller
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
        $this->authorize('rbac.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $service = new RbacService($this->container);

        return $this->view('rbac/index', [
            'roles' => $service->listRoles(),
        ]);
    }

    public function edit(Request $request)
    {
        $this->authorize('rbac.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $roleId = (int)$request->input('id', 0);
        if ($roleId <= 0) {
            return $this->redirect('/rbac');
        }

        $service = new RbacService($this->container);

        return $this->view('rbac/edit', [
            'role' => $service->getRole($roleId),
            'catalog' => $service->listPermissionsCatalog(),
            'decisions' => $service->getRoleDecisions($roleId),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('rbac.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $roleId = (int)$request->input('id', 0);
        if ($roleId <= 0) {
            return $this->redirect('/rbac');
        }

        $name = trim((string)$request->input('name', ''));
        $allow = $request->input('allow', []);
        $deny = $request->input('deny', []);

        $service = new RbacService($this->container);

        try {
            $service->updateRole(
                $roleId,
                $name,
                is_array($allow) ? $allow : [],
                is_array($deny) ? $deny : [],
                $request->ip()
            );
        } catch (\RuntimeException $e) {
            return $this->view('rbac/edit', [
                'error' => $e->getMessage(),
                'role' => $service->getRole($roleId),
                'catalog' => $service->listPermissionsCatalog(),
                'decisions' => $service->getRoleDecisions($roleId),
            ]);
        }

        return $this->redirect('/rbac');
    }

    public function clone(Request $request)
    {
        $this->authorize('rbac.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $fromRoleId = (int)$request->input('from_role_id', 0);
        if ($fromRoleId <= 0) {
            return $this->redirect('/rbac');
        }

        $newName = trim((string)$request->input('name', ''));
        if ($newName === '') {
            return $this->redirect('/rbac');
        }

        $service = new RbacService($this->container);
        $service->createRoleFromClone($fromRoleId, $newName, $request->ip());

        return $this->redirect('/rbac');
    }

    public function reset(Request $request)
    {
        $this->authorize('rbac.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $roleId = (int)$request->input('id', 0);
        if ($roleId <= 0) {
            return $this->redirect('/rbac');
        }

        $service = new RbacService($this->container);
        $service->resetRoleToDefaults($roleId, $request->ip());

        return $this->redirect('/rbac/edit?id=' . $roleId);
    }
}
