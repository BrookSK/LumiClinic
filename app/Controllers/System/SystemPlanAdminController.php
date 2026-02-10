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

        try {
            (new SystemPlanService($this->container))->createPlan([
                'code' => (string)$request->input('code', ''),
                'name' => (string)$request->input('name', ''),
                'price_cents' => (string)$request->input('price_cents', ''),
                'currency' => (string)$request->input('currency', 'BRL'),
                'interval_unit' => (string)$request->input('interval_unit', 'month'),
                'interval_count' => (string)$request->input('interval_count', '1'),
                'trial_days' => (string)$request->input('trial_days', '0'),
                'limits_json' => (string)$request->input('limits_json', ''),
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

        try {
            (new SystemPlanService($this->container))->updatePlan([
                'id' => (string)$request->input('id', ''),
                'name' => (string)$request->input('name', ''),
                'price_cents' => (string)$request->input('price_cents', ''),
                'currency' => (string)$request->input('currency', 'BRL'),
                'interval_unit' => (string)$request->input('interval_unit', 'month'),
                'interval_count' => (string)$request->input('interval_count', '1'),
                'trial_days' => (string)$request->input('trial_days', '0'),
                'limits_json' => (string)$request->input('limits_json', ''),
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
