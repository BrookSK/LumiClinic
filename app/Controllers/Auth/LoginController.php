<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;

final class LoginController extends Controller
{
    public function show(Request $request)
    {
        return $this->view('auth/login');
    }

    public function login(Request $request)
    {
        $auth = new AuthService($this->container);

        $email = (string)$request->input('email', '');
        $password = (string)$request->input('password', '');

        $result = $auth->attempt($email, $password, $request->ip(), $request->header('user-agent'));

        if (!$result->success) {
            return $this->view('auth/login', ['error' => $result->message]);
        }

        return $this->redirect('/');
    }

    public function logout(Request $request)
    {
        $auth = new AuthService($this->container);
        $auth->logout($request->ip(), $request->header('user-agent'));

        return $this->redirect('/login');
    }
}
