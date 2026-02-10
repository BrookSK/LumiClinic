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

    public function showForgot(Request $request)
    {
        return $this->view('auth/forgot');
    }

    public function forgot(Request $request)
    {
        $email = (string)$request->input('email', '');

        $auth = new AuthService($this->container);
        $data = $auth->createPasswordReset($email, $request->ip());

        return $this->view('auth/forgot', [
            'success' => 'Se o e-mail existir, você receberá instruções para redefinir a senha.',
            'reset_token' => null,
        ]);
    }

    public function showReset(Request $request)
    {
        $token = (string)$request->input('token', '');
        return $this->view('auth/reset', ['token' => $token]);
    }

    public function reset(Request $request)
    {
        $token = (string)$request->input('token', '');
        $password = (string)$request->input('password', '');

        $auth = new AuthService($this->container);
        $result = $auth->resetPassword($token, $password, $request->ip());

        if (!$result->success) {
            return $this->view('auth/reset', ['error' => $result->message, 'token' => $token]);
        }

        return $this->view('auth/login', ['success' => $result->message]);
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
