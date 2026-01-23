<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Portal\PatientAuthService;

final class AuthPatientController extends Controller
{
    public function showLogin(Request $request)
    {
        return $this->view('portal/login');
    }

    public function login(Request $request)
    {
        $email = (string)$request->input('email', '');
        $password = (string)$request->input('password', '');

        $auth = new PatientAuthService($this->container);
        $result = $auth->attempt($email, $password, $request->ip());

        if (!$result->success) {
            return $this->view('portal/login', ['error' => $result->message]);
        }

        return $this->redirect('/portal');
    }

    public function logout(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $auth->logout($request->ip());

        return $this->redirect('/portal/login');
    }

    public function showForgot(Request $request)
    {
        return $this->view('portal/forgot');
    }

    public function forgot(Request $request)
    {
        $email = (string)$request->input('email', '');

        $auth = new PatientAuthService($this->container);
        $data = $auth->createPasswordReset($email, $request->ip());

        return $this->view('portal/forgot', [
            'success' => 'Se o e-mail existir, você receberá instruções para redefinir a senha.',
            'reset_token' => $data['reset_token'],
        ]);
    }

    public function showReset(Request $request)
    {
        $token = (string)$request->input('token', '');
        return $this->view('portal/reset', ['token' => $token]);
    }

    public function reset(Request $request)
    {
        $token = (string)$request->input('token', '');
        $password = (string)$request->input('password', '');

        $auth = new PatientAuthService($this->container);
        $result = $auth->resetPassword($token, $password, $request->ip());

        if (!$result->success) {
            return $this->view('portal/reset', ['error' => $result->message, 'token' => $token]);
        }

        return $this->view('portal/login', ['success' => $result->message]);
    }
}
