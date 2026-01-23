<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Billing\BillingGatewayService;

final class SystemBillingController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
            throw new \RuntimeException('Acesso negado.');
        }
    }

    public function ensureGateway(Request $request)
    {
        $this->ensureSuperAdmin();

        $clinicId = (int)$request->input('clinic_id', 0);
        if ($clinicId <= 0) {
            return $this->redirect('/sys/clinics');
        }

        (new BillingGatewayService($this->container))->ensureGatewaySubscription($clinicId);

        return $this->redirect('/sys/clinics');
    }
}
