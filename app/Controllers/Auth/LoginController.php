<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\PatientUserRepository;
use App\Repositories\UserRepository;
use App\Services\Auth\AuthService;
use App\Services\Portal\PatientAuthService;

final class LoginController extends Controller
{
    public function show(Request $request)
    {
        if (isset($_SESSION['patient_user_id']) && (int)$_SESSION['patient_user_id'] > 0) {
            return $this->view('auth/login', ['error' => 'Você está logado no Portal do Paciente. Saia do portal para entrar na área da clínica.']);
        }

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
        if (isset($_SESSION['patient_user_id']) && (int)$_SESSION['patient_user_id'] > 0) {
            return $this->view('auth/login', ['error' => 'Você está logado no Portal do Paciente. Saia do portal para entrar na área da clínica.']);
        }

        $email = (string)$request->input('email', '');
        $password = (string)$request->input('password', '');

        $pdo = $this->container->get(\PDO::class);
        $userRepo = new UserRepository($pdo);
        $patientRepo = new PatientUserRepository($pdo);

        $userCandidates = $userRepo->listActiveByEmail($email, 10);
        $patientCandidates = $patientRepo->listActiveByEmailWithClinic($email, 10);

        $options = [];

        foreach ($userCandidates as $u) {
            if (!isset($u['password_hash']) || !password_verify($password, (string)$u['password_hash'])) {
                continue;
            }

            $clinicName = (string)($u['clinic_name'] ?? '');
            $isSuper = isset($u['is_super_admin']) && (int)$u['is_super_admin'] === 1;

            $options[] = [
                'kind' => 'user',
                'id' => (int)$u['id'],
                'label' => $isSuper ? 'Super Admin (Plataforma)' : ($clinicName !== '' ? $clinicName : ('Clínica #' . (int)($u['clinic_id'] ?? 0))),
                'meta' => $isSuper ? 'Área do sistema (admin)' : 'Área do sistema (equipe)',
            ];
        }

        foreach ($patientCandidates as $pu) {
            if (!isset($pu['password_hash']) || !password_verify($password, (string)$pu['password_hash'])) {
                continue;
            }

            $clinicName = (string)($pu['clinic_name'] ?? '');

            $options[] = [
                'kind' => 'patient',
                'id' => (int)$pu['id'],
                'label' => $clinicName !== '' ? $clinicName : ('Clínica #' . (int)($pu['clinic_id'] ?? 0)),
                'meta' => 'Portal do paciente',
            ];
        }

        if ($options === []) {
            return $this->view('auth/login', ['error' => 'Credenciais inválidas.']);
        }

        if (count($options) === 1) {
            $opt = $options[0];
            if ((string)$opt['kind'] === 'user') {
                (new AuthService($this->container))->loginUserByIdForSession((int)$opt['id'], $request->ip(), $request->header('user-agent'));
                $next = isset($_SESSION['auth_next']) ? (string)$_SESSION['auth_next'] : '';
                unset($_SESSION['auth_next']);
                if ($next !== '' && str_starts_with($next, '/') && !str_starts_with($next, '/login') && !str_starts_with($next, '/logout')) {
                    return $this->redirect($next);
                }
                return $this->redirect('/');
            }

            (new PatientAuthService($this->container))->loginPatientUserByIdForSession((int)$opt['id'], $request->ip(), $request->header('user-agent'));
            return $this->redirect('/portal');
        }

        $_SESSION['pending_access'] = [
            'email' => $email,
            'options' => $options,
        ];

        return $this->redirect('/choose-access');
    }

    public function logout(Request $request)
    {
        $auth = new AuthService($this->container);
        $auth->logout($request->ip(), $request->header('user-agent'));

        return $this->redirect('/login');
    }
}
