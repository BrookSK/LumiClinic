<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\System\SystemBillingService;

final class SystemBillingAdminController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
            throw new \RuntimeException('Acesso negado.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemBillingService($this->container);

        return $this->view('system/billing/index', [
            'items' => $service->listClinicsWithBilling(),
            'plans' => $service->listActivePlans(),
            'error' => (string)$request->input('error', ''),
        ]);
    }

    public function details(Request $request)
    {
        $this->ensureSuperAdmin();

        $clinicId = (int)$request->input('clinic_id', 0);
        if ($clinicId <= 0) {
            return $this->redirect('/sys/billing');
        }

        $service = new SystemBillingService($this->container);
        $row = $service->getClinicWithBilling($clinicId);
        if ($row === null) {
            return $this->redirect('/sys/billing?error=' . urlencode('Assinatura não encontrada.'));
        }

        return $this->view('system/billing/view', [
            'row' => $row,
            'ok' => (string)$request->input('ok', ''),
            'error' => (string)$request->input('error', ''),
        ]);
    }

    public function setPlan(Request $request)
    {
        $this->ensureSuperAdmin();

        $clinicId = (int)$request->input('clinic_id', 0);
        $planId = (int)$request->input('plan_id', 0);

        (new SystemBillingService($this->container))->setPlan($clinicId, $planId, $request->ip());

        return $this->redirect('/sys/billing');
    }

    public function setStatus(Request $request)
    {
        $this->ensureSuperAdmin();

        $clinicId = (int)$request->input('clinic_id', 0);
        $status = (string)$request->input('status', '');

        (new SystemBillingService($this->container))->setStatus($clinicId, $status, $request->ip());

        return $this->redirect('/sys/billing');
    }

    public function setGateway(Request $request)
    {
        $this->ensureSuperAdmin();

        $clinicId = (int)$request->input('clinic_id', 0);
        $provider = (string)$request->input('gateway_provider', '');

        (new SystemBillingService($this->container))->setGatewayProvider($clinicId, $provider, $request->ip());

        return $this->redirect('/sys/billing');
    }

    public function ensureGateway(Request $request)
    {
        $this->ensureSuperAdmin();

        $clinicId = (int)$request->input('clinic_id', 0);

        try {
            (new SystemBillingService($this->container))->ensureGateway($clinicId, $request->ip());
            return $this->redirect('/sys/billing');
        } catch (\RuntimeException $e) {
            return $this->redirect('/sys/billing?error=' . urlencode($e->getMessage()));
        }
    }

    public function grantMonth(Request $request)
    {
        $this->ensureSuperAdmin();

        $clinicId = (int)$request->input('clinic_id', 0);
        try {
            (new SystemBillingService($this->container))->grantOneMonth($clinicId, $request->ip());
            return $this->redirect('/sys/billing/view?clinic_id=' . $clinicId . '&ok=' . urlencode('Mais 1 mês concedido com sucesso.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/sys/billing/view?clinic_id=' . $clinicId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function skipMonth(Request $request)
    {
        $this->ensureSuperAdmin();

        $clinicId = (int)$request->input('clinic_id', 0);
        try {
            (new SystemBillingService($this->container))->skipOneMonth($clinicId, $request->ip());
            return $this->redirect('/sys/billing/view?clinic_id=' . $clinicId . '&ok=' . urlencode('Mais 1 mês concedido com sucesso.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/sys/billing/view?clinic_id=' . $clinicId . '&error=' . urlencode($e->getMessage()));
        }
    }
}
