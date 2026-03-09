<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientPasswordResetRepository;
use App\Repositories\PatientRepository;
use App\Repositories\PatientUserRepository;
use App\Services\Portal\PatientAuthService;

final class RegisterPatientController extends Controller
{
    public function show(Request $request)
    {
        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
            return $this->redirect('/');
        }

        $token = trim((string)$request->input('token', ''));
        if ($token === '') {
            return $this->view('portal/register', ['error' => 'Link inválido ou expirado.']);
        }

        $pdo = $this->container->get(\PDO::class);
        $resets = new PatientPasswordResetRepository($pdo);
        $reset = $resets->findValidByTokenHash(hash('sha256', $token));
        if ($reset === null) {
            return $this->view('portal/register', ['error' => 'Link inválido ou expirado.']);
        }

        $clinicId = (int)$reset['clinic_id'];
        $patientUserId = (int)$reset['patient_user_id'];

        $puRepo = new PatientUserRepository($pdo);
        $pu = $puRepo->findById($clinicId, $patientUserId);
        if ($pu === null) {
            return $this->view('portal/register', ['error' => 'Link inválido ou expirado.']);
        }

        $patient = (new PatientRepository($pdo))->findById($clinicId, (int)$pu['patient_id']);

        return $this->view('portal/register', [
            'token' => $token,
            'email' => (string)($pu['email'] ?? ''),
            'patient_name' => $patient !== null ? (string)($patient['name'] ?? '') : null,
        ]);
    }

    public function submit(Request $request)
    {
        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
            return $this->redirect('/');
        }

        $token = trim((string)$request->input('token', ''));
        $password = (string)$request->input('password', '');

        if ($token === '') {
            return $this->view('portal/register', ['error' => 'Token inválido.']);
        }

        if (strlen($password) < 8) {
            return $this->view('portal/register', ['error' => 'Senha deve ter pelo menos 8 caracteres.', 'token' => $token]);
        }

        $pdo = $this->container->get(\PDO::class);
        $resets = new PatientPasswordResetRepository($pdo);
        $reset = $resets->findValidByTokenHash(hash('sha256', $token));
        if ($reset === null) {
            (new AuditLogRepository($pdo))->log(null, null, 'portal.register.invalid_token', [], $request->ip());
            return $this->view('portal/register', ['error' => 'Link inválido ou expirado.']);
        }

        $clinicId = (int)$reset['clinic_id'];
        $patientUserId = (int)$reset['patient_user_id'];

        $hash = password_hash($password, PASSWORD_DEFAULT);
        (new PatientUserRepository($pdo))->updatePassword($clinicId, $patientUserId, $hash);
        $resets->markUsed((int)$reset['id']);

        (new AuditLogRepository($pdo))->log(null, $clinicId, 'portal.register.success', ['patient_user_id' => $patientUserId], $request->ip());

        (new PatientAuthService($this->container))->loginPatientUserByIdForSession($patientUserId, $request->ip(), $request->header('user-agent'));

        return $this->redirect('/portal');
    }
}
