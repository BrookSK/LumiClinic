<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;

final class ImpersonationController extends Controller
{
    /**
     * Encerra o "logar como" e volta para a sessão do super admin.
     * Não usa ensureSuperAdmin(): durante a impersonação a sessão é do dono da clínica.
     * A autorização vem apenas de $_SESSION['impersonator'], que só o servidor define.
     */
    public function stop(Request $request)
    {
        $auth = new AuthService($this->container);

        if ($auth->stopImpersonation($request->ip(), $request->header('user-agent'))) {
            return $this->redirect('/sys/clinics');
        }

        if ($auth->userId() === null) {
            return $this->redirect('/login');
        }

        return $this->redirect('/');
    }
}
