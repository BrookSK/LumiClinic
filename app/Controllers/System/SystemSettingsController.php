<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\System\SystemSettingsService;

final class SystemSettingsController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
            throw new \RuntimeException('Acesso negado.');
        }
    }

    public function billing(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);

        return $this->view('system/settings/billing', $service->getBillingSettings());
    }

    public function billingSubmit(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);
        $service->saveBillingSettings($_POST);

        return $this->redirect('/sys/settings/billing');
    }
}
