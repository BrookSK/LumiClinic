<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\Auth\AuthService;
use App\Services\Portal\PatientAuthService;

final class AccessChoiceController extends Controller
{
    public function show(Request $request): Response
    {
        if (isset($_SESSION['patient_user_id']) && (int)$_SESSION['patient_user_id'] > 0) {
            return $this->redirect('/login?error=' . urlencode('Você está logado no Portal do Paciente. Saia do portal para entrar na área da clínica.'));
        }

        $pending = $_SESSION['pending_access'] ?? null;
        if (!is_array($pending) || !isset($pending['options']) || !is_array($pending['options'])) {
            return $this->redirect('/login');
        }

        return $this->view('auth/choose_access', [
            'email' => (string)($pending['email'] ?? ''),
            'options' => $pending['options'],
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function choose(Request $request): Response
    {
        if (isset($_SESSION['patient_user_id']) && (int)$_SESSION['patient_user_id'] > 0) {
            return $this->redirect('/login?error=' . urlencode('Você está logado no Portal do Paciente. Saia do portal para entrar na área da clínica.'));
        }

        $pending = $_SESSION['pending_access'] ?? null;
        if (!is_array($pending) || !isset($pending['options']) || !is_array($pending['options'])) {
            return $this->redirect('/login');
        }

        $kind = trim((string)$request->input('kind', ''));
        $id = (int)$request->input('id', 0);
        if ($kind === '' || $id <= 0) {
            return $this->redirect('/choose-access?error=' . urlencode('Selecione uma opção para entrar.'));
        }

        unset($_SESSION['pending_access']);

        if ($kind === 'user') {
            try {
                (new AuthService($this->container))->loginUserByIdForSession($id, $request->ip(), $request->header('user-agent'));
                return $this->redirect('/');
            } catch (\RuntimeException $e) {
                return $this->redirect('/login?error=' . urlencode($e->getMessage()));
            }
        }

        if ($kind === 'patient') {
            try {
                (new PatientAuthService($this->container))->loginPatientUserByIdForSession($id, $request->ip(), $request->header('user-agent'));
                return $this->redirect('/portal');
            } catch (\RuntimeException $e) {
                return $this->redirect('/portal/login?error=' . urlencode($e->getMessage()));
            }
        }

        return $this->redirect('/choose-access?error=' . urlencode('Opção inválida.'));
    }
}
