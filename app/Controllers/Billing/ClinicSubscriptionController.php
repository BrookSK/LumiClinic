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

        // Dados de transcrição
        $auth = new \App\Services\Auth\AuthService($this->container);
        $clinicId = $auth->clinicId();
        $transcription = ['limit' => null, 'used' => 0, 'remaining' => null, 'blocked' => false];
        if ($clinicId !== null) {
            $transcription = (new \App\Services\Billing\PlanEntitlementsService($this->container))->transcriptionStatus($clinicId);
        }

        return $this->view('billing/subscription', [
            'subscription' => $data['subscription'],
            'plan' => $data['plan'],
            'plans' => $data['plans'],
            'payments' => $data['payments'],
            'transcription' => $transcription,
            'ok' => (string)$request->input('ok', ''),
            'error' => (string)$request->input('error', ''),
        ]);
    }

    public function changePlan(Request $request)
    {
        $this->ensureOwnerOrSuperAdmin();

        $planId = (int)$request->input('plan_id', 0);

        try {
            $res = (new ClinicSubscriptionSelfService($this->container))->beginPlanChange($planId, $request->ip());

            if (($res['type'] ?? '') === 'noop') {
                return $this->redirect('/billing/subscription?ok=' . urlencode('Plano já está selecionado.'));
            }

            if (($res['type'] ?? '') === 'downgrade_scheduled') {
                return $this->redirect('/billing/subscription?ok=' . urlencode('Downgrade agendado para o próximo ciclo.'));
            }

            if (($res['type'] ?? '') === 'upgrade_checkout') {
                $url = (string)($res['checkout_url'] ?? '');
                if ($url !== '') {
                    return $this->redirect($url);
                }
                return $this->redirect('/billing/subscription?error=' . urlencode('Não foi possível iniciar o checkout.'));
            }

            return $this->redirect('/billing/subscription?error=' . urlencode('Não foi possível atualizar o plano.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/billing/subscription?error=' . urlencode($e->getMessage()));
        }
    }

    public function checkout(Request $request)
    {
        $this->ensureOwnerOrSuperAdmin();

        $planId = (int)$request->input('plan_id', 0);
        try {
            $data = (new ClinicSubscriptionSelfService($this->container))->getCheckoutData($planId);
            return $this->view('billing/subscription_checkout', [
                'subscription' => $data['subscription'],
                'plan' => $data['plan'],
                'error' => (string)$request->input('error', ''),
            ]);
        } catch (\RuntimeException $e) {
            return $this->redirect('/billing/subscription?error=' . urlencode($e->getMessage()));
        }
    }

    public function checkoutSubmit(Request $request)
    {
        $this->ensureOwnerOrSuperAdmin();

        $planId = (int)$request->input('plan_id', 0);
        try {
            (new ClinicSubscriptionSelfService($this->container))->payUpgradeAndApply($planId, [
                'cc_holder' => (string)$request->input('cc_holder', ''),
                'cc_number' => (string)$request->input('cc_number', ''),
                'cc_exp_month' => (string)$request->input('cc_exp_month', ''),
                'cc_exp_year' => (string)$request->input('cc_exp_year', ''),
                'cc_cvv' => (string)$request->input('cc_cvv', ''),
                'cpf' => (string)$request->input('cpf', ''),
                'postal_code' => (string)$request->input('postal_code', ''),
                'address_number' => (string)$request->input('address_number', ''),
                'phone' => (string)$request->input('phone', ''),
                'mobile' => (string)$request->input('mobile', ''),
            ], $request->ip(), $request->header('user-agent'));

            return $this->redirect('/billing/subscription?ok=' . urlencode('Pagamento aprovado e plano alterado.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/billing/subscription/checkout?plan_id=' . $planId . '&error=' . urlencode($e->getMessage()));
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

    public function cancelDowngrade(Request $request)
    {
        $this->ensureOwnerOrSuperAdmin();

        $auth = new \App\Services\Auth\AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/billing/subscription?error=' . urlencode('Contexto inválido.'));
        }

        try {
            $pdo = $this->container->get(\PDO::class);
            $pdo->prepare("
                UPDATE clinic_subscriptions
                SET pending_plan_id = NULL,
                    pending_plan_effective_at = NULL,
                    updated_at = NOW()
                WHERE clinic_id = :clinic_id
                LIMIT 1
            ")->execute(['clinic_id' => $clinicId]);

            return $this->redirect('/billing/subscription?ok=' . urlencode('Downgrade cancelado. Você permanece no plano atual.'));
        } catch (\Throwable $e) {
            return $this->redirect('/billing/subscription?error=' . urlencode('Erro ao cancelar downgrade.'));
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
