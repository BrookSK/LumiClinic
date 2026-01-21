<?php

declare(strict_types=1);

namespace App\Controllers\Users;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Users\UserAdminService;

final class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('users.read');

        $service = new UserAdminService($this->container);
        $users = $service->listUsers();

        return $this->view('users/index', ['users' => $users]);
    }

    public function create(Request $request)
    {
        $this->authorize('users.create');

        $service = new UserAdminService($this->container);
        $roles = $service->listRoles();

        return $this->view('users/create', ['roles' => $roles]);
    }

    public function store(Request $request)
    {
        $this->authorize('users.create');

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
}
