<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Portal\PatientAuthService;

final class PortalSecurityController extends Controller
{
    public function index(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        $patientUserId = $auth->patientUserId();
        if ($clinicId === null || $patientId === null || $patientUserId === null) {
            return $this->redirect('/portal/login');
        }

        $me = $auth->me($clinicId, $patientUserId);

        return $this->view('portal/security', [
            'me' => $me,
        ]);
    }

    public function sendReset(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        $patientUserId = $auth->patientUserId();
        if ($clinicId === null || $patientId === null || $patientUserId === null) {
            return $this->redirect('/portal/login');
        }

        $me = $auth->me($clinicId, $patientUserId);
        $email = (string)($me['email'] ?? '');

        $out = $auth->createPasswordResetAndNotify($email, $request->ip());

        return $this->view('portal/security', [
            'me' => $me,
            'success' => 'Enviamos um e-mail com o link para redefinir sua senha.',
            'reset_url' => $out['reset_url'] ?? null,
        ]);
    }
}
