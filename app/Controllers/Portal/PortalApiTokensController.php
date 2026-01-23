<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Portal\PatientAuthService;
use App\Services\Portal\PortalApiTokenService;

final class PortalApiTokensController extends Controller
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

        $svc = new PortalApiTokenService($this->container);
        $tokens = $svc->list($clinicId, $patientUserId);

        return $this->view('portal/api_tokens', [
            'tokens' => $tokens,
        ]);
    }

    public function create(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        $patientUserId = $auth->patientUserId();
        if ($clinicId === null || $patientId === null || $patientUserId === null) {
            return $this->redirect('/portal/login');
        }

        $name = trim((string)$request->input('name', ''));

        $svc = new PortalApiTokenService($this->container);
        $out = $svc->create($clinicId, $patientUserId, $patientId, $name === '' ? null : $name, $request->ip());
        $tokens = $svc->list($clinicId, $patientUserId);

        return $this->view('portal/api_tokens', [
            'tokens' => $tokens,
            'created_token' => $out['token'],
        ]);
    }

    public function revoke(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientUserId = $auth->patientUserId();
        if ($clinicId === null || $patientUserId === null) {
            return $this->redirect('/portal/login');
        }

        $id = (int)$request->input('id', 0);
        if ($id > 0) {
            (new PortalApiTokenService($this->container))->revoke($clinicId, $patientUserId, $id, $request->ip());
        }

        return $this->redirect('/portal/api-tokens');
    }
}
