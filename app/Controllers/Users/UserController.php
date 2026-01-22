<?php

declare(strict_types=1);

namespace App\Controllers\Users;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Users\UserAdminService;
use App\Services\Auth\AuthService;

final class UserController extends Controller
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
        $this->authorize('users.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $service = new UserAdminService($this->container);
        $users = $service->listUsers();

        return $this->view('users/index', ['users' => $users]);
    }

    public function create(Request $request)
    {
        $this->authorize('users.create');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $service = new UserAdminService($this->container);
        $roles = $service->listRoles();

        return $this->view('users/create', ['roles' => $roles]);
    }

    public function store(Request $request)
    {
        $this->authorize('users.create');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $name = trim((string)$request->input('name', ''));
        $email = trim((string)$request->input('email', ''));
        $password = (string)$request->input('password', '');
        $roleId = (int)$request->input('role_id', 0);

        if ($name === '' || $email === '' || $password === '' || $roleId <= 0) {
            $service = new UserAdminService($this->container);
            $roles = $service->listRoles();
            return $this->view('users/create', ['roles' => $roles, 'error' => 'Preencha todos os campos.']);
        }

        $service = new UserAdminService($this->container);
        $service->createUser($name, $email, $password, $roleId, $request->ip());

        return $this->redirect('/users');
    }

    public function edit(Request $request)
    {
        $this->authorize('users.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/users');
        }

        $service = new UserAdminService($this->container);
        $user = $service->getUser($id);
        $roles = $service->listRoles();

        if ($user === null) {
            return $this->redirect('/users');
        }

        return $this->view('users/edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('users.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $name = trim((string)$request->input('name', ''));
        $email = trim((string)$request->input('email', ''));
        $status = trim((string)$request->input('status', 'active'));
        $roleId = (int)$request->input('role_id', 0);
        $newPassword = trim((string)$request->input('new_password', ''));

        if ($id <= 0 || $name === '' || $email === '' || $roleId <= 0) {
            $service = new UserAdminService($this->container);
            return $this->view('users/edit', [
                'user' => $service->getUser($id),
                'roles' => $service->listRoles(),
                'error' => 'Preencha os campos obrigatórios.',
            ]);
        }

        $service = new UserAdminService($this->container);
        $service->updateUser($id, $name, $email, $status, $roleId, ($newPassword === '' ? null : $newPassword), $request->ip());

        return $this->redirect('/users');
    }

    public function disable(Request $request)
    {
        $this->authorize('users.delete');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/users');
        }

        $service = new UserAdminService($this->container);
        $service->disableUser($id, $request->ip());

        return $this->redirect('/users');
    }
}
