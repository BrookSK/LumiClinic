<?php

declare(strict_types=1);

namespace App\Controllers\Billing;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Billing\ClinicSubscriptionSelfService;

final class ClinicSubscriptionController extends Controller
{
    private function ensureOwnerOrSuperAdmin(): void
    {
        if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
            return;
        }

        $auth = new AuthService($this->container);
        if ($auth->clinicId() === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $roles = $_SESSION['role_codes'] ?? [];
        if (!is_array($roles) || !in_array('owner', $roles, true)) {
            throw new \RuntimeException('Acesso negado.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureOwnerOrSuperAdmin();

        $svc = new ClinicSubscriptionSelfService($this->container);
        $data = $svc->getDashboard();

        return $this->view('billing/subscription', [
            'subscription' => $data['subscription'],
            'plan' => $data['plan'],
            'plans' => $data['plans'],
            'payments' => $data['payments'],
            'ok' => (string)$request->input('ok', ''),
            'error' => (string)$request->input('error', ''),
        ]);
    }

    public function changePlan(Request $request)
    {
        $this->ensureOwnerOrSuperAdmin();

        $planId = (int)$request->input('plan_id', 0);

        try {
            $res = (new ClinicSubscriptionSelfService($this->container))->changePlan($planId, $request->ip());
            if (($res['gateway_synced'] ?? false) === true) {
                return $this->redirect('/billing/subscription?ok=' . urlencode('Plano atualizado e cobrança sincronizada.'));
            }

            return $this->redirect('/billing/subscription?error=' . urlencode('Não foi possível atualizar o plano.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/billing/subscription?error=' . urlencode($e->getMessage()));
        }
    }

    public function cancel(Request $request)
    {
        $this->ensureOwnerOrSuperAdmin();

        try {
            (new ClinicSubscriptionSelfService($this->container))->cancel($request->ip());
            return $this->redirect('/billing/subscription?ok=' . urlencode('Assinatura cancelada.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/billing/subscription?error=' . urlencode($e->getMessage()));
        }
    }

    public function ensureGateway(Request $request)
    {
        $this->ensureOwnerOrSuperAdmin();

        try {
            (new ClinicSubscriptionSelfService($this->container))->ensureGateway($request->ip());
            return $this->redirect('/billing/subscription?ok=' . urlencode('Cobrança atualizada no provedor.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/billing/subscription?error=' . urlencode($e->getMessage()));
        }
    }
}
