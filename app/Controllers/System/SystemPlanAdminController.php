<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\System\SystemPlanService;

final class SystemPlanAdminController extends Controller
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

        $svc = new SystemPlanService($this->container);

        return $this->view('system/plans/index', [
            'items' => $svc->listPlans(),
            'error' => (string)$request->input('error', ''),
            'ok' => (string)$request->input('ok', ''),
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureSuperAdmin();

        $billingPeriod = (string)$request->input('billing_period', 'monthly');
        $intervalUnit = 'month';
        $intervalCount = '1';
        if ($billingPeriod === 'annual') {
            $intervalUnit = 'year';
            $intervalCount = '1';
        } elseif ($billingPeriod === 'semiannual') {
            $intervalUnit = 'month';
            $intervalCount = '6';
        }

        $limits = [];
        $portalEnabled = (string)$request->input('portal_enabled', '1');
        $limits['portal'] = ($portalEnabled === '1');

        $users = (int)$request->input('limit_users', 0);
        $patients = (int)$request->input('limit_patients', 0);
        $storageMb = (int)$request->input('limit_storage_mb', 0);
        if ($users > 0) {
            $limits['users'] = $users;
        }
        if ($patients > 0) {
            $limits['patients'] = $patients;
        }
        if ($storageMb > 0) {
            $limits['storage_mb'] = $storageMb;
        }

        $limitsJson = (string)json_encode($limits, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            (new SystemPlanService($this->container))->createPlan([
                'code' => '',
                'name' => (string)$request->input('name', ''),
                'price_cents' => (string)$request->input('price_cents', ''),
                'currency' => 'BRL',
                'interval_unit' => $intervalUnit,
                'interval_count' => $intervalCount,
                'trial_days' => (string)$request->input('trial_days', '0'),
                'limits_json' => $limitsJson,
                'status' => (string)$request->input('status', 'active'),
            ], $request->ip());

            return $this->redirect('/sys/plans?ok=' . urlencode('Plano criado com sucesso.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/sys/plans?error=' . urlencode($e->getMessage()));
        }
    }

    public function update(Request $request)
    {
        $this->ensureSuperAdmin();

        $billingPeriod = (string)$request->input('billing_period', 'monthly');
        $intervalUnit = 'month';
        $intervalCount = '1';
        if ($billingPeriod === 'annual') {
            $intervalUnit = 'year';
            $intervalCount = '1';
        } elseif ($billingPeriod === 'semiannual') {
            $intervalUnit = 'month';
            $intervalCount = '6';
        }

        $limits = [];
        $portalEnabled = (string)$request->input('portal_enabled', '1');
        $limits['portal'] = ($portalEnabled === '1');

        $users = (int)$request->input('limit_users', 0);
        $patients = (int)$request->input('limit_patients', 0);
        $storageMb = (int)$request->input('limit_storage_mb', 0);
        if ($users > 0) {
            $limits['users'] = $users;
        }
        if ($patients > 0) {
            $limits['patients'] = $patients;
        }
        if ($storageMb > 0) {
            $limits['storage_mb'] = $storageMb;
        }

        $limitsJson = (string)json_encode($limits, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            (new SystemPlanService($this->container))->updatePlan([
                'id' => (string)$request->input('id', ''),
                'name' => (string)$request->input('name', ''),
                'price_cents' => (string)$request->input('price_cents', ''),
                'currency' => 'BRL',
                'interval_unit' => $intervalUnit,
                'interval_count' => $intervalCount,
                'trial_days' => (string)$request->input('trial_days', '0'),
                'limits_json' => $limitsJson,
            ], $request->ip());

            return $this->redirect('/sys/plans?ok=' . urlencode('Plano atualizado com sucesso.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/sys/plans?error=' . urlencode($e->getMessage()));
        }
    }

    public function setStatus(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        $status = (string)$request->input('status', '');

        try {
            (new SystemPlanService($this->container))->setStatus($id, $status, $request->ip());
            return $this->redirect('/sys/plans?ok=' . urlencode('Status atualizado.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/sys/plans?error=' . urlencode($e->getMessage()));
        }
    }
}
