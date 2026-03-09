<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\UserRepository;
use App\Services\Auth\AuthService;
use App\Services\System\SystemClinicService;

final class ClinicSignupController extends Controller
{
    public function show(Request $request)
    {
        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
            return $this->redirect('/');
        }

        return $this->view('public/clinic_signup');
    }

    public function store(Request $request)
    {
        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
            return $this->redirect('/');
        }

        $clinicName = trim((string)$request->input('clinic_name', ''));
        $tenantKey = trim((string)$request->input('tenant_key', ''));
        $primaryDomain = trim((string)$request->input('primary_domain', ''));

        $ownerName = trim((string)$request->input('owner_name', ''));
        $ownerEmail = strtolower(trim((string)$request->input('owner_email', '')));
        $ownerPassword = (string)$request->input('owner_password', '');

        if ($clinicName === '' || $ownerName === '' || $ownerEmail === '' || $ownerPassword === '') {
            return $this->view('public/clinic_signup', ['error' => 'Preencha todos os campos obrigatórios.']);
        }

        if (!filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->view('public/clinic_signup', ['error' => 'E-mail inválido.']);
        }

        if (strlen($ownerPassword) < 8) {
            return $this->view('public/clinic_signup', ['error' => 'Senha deve ter pelo menos 8 caracteres.']);
        }

        $pdo = $this->container->get(\PDO::class);
        $existing = (new UserRepository($pdo))->listActiveByEmail($ownerEmail, 1);
        if ($existing !== []) {
            return $this->view('public/clinic_signup', ['error' => 'Já existe uma conta com este e-mail.']);
        }

        try {
            $svc = new SystemClinicService($this->container);
            $result = $svc->createClinicWithOwnerAndReturnIds(
                $clinicName,
                ($tenantKey === '' ? null : $tenantKey),
                ($primaryDomain === '' ? null : $primaryDomain),
                $ownerName,
                $ownerEmail,
                $ownerPassword,
                $request->ip()
            );

            (new AuthService($this->container))->loginUserByIdForSession((int)$result['owner_user_id'], $request->ip(), $request->header('user-agent'));

            return $this->redirect('/billing/subscription');
        } catch (\RuntimeException $e) {
            return $this->view('public/clinic_signup', ['error' => $e->getMessage()]);
        }
    }
}
