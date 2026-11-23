<?php

declare(strict_types=1);

namespace App\Controllers\Finance;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Finance\ChartOfAccountsService;

final class ChartOfAccountsController extends Controller
{
    private function redirectSuperAdminWithoutClinicContext(): ?\App\Core\Http\Response
    {
        $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;
        if (!$isSuperAdmin) {
            return null;
        }

        $auth = new AuthService($this->container);
        if ($auth->clinicId() === null) {
            return $this->redirect('/sys/clinics');
        }

        return null;
    }

    private function isProfessionalRole(): bool
    {
        $codes = $_SESSION['role_codes'] ?? [];
        return is_array($codes) && in_array('professional', $codes, true);
    }

    /** Leitura: mesma regra da lista de centros de custo. */
    private function authorizeRead(): void
    {
        try { $this->authorize('finance.cost_centers.manage'); } catch (\Throwable $e) { $this->authorize('finance.sales.read'); }

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }
    }

    /** Alterações: exige finance.cost_centers.manage e bloqueia profissionais. */
    private function authorizeManage(): void
    {
        $this->authorize('finance.cost_centers.manage');

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }
    }

    /** @return array<string,mixed> */
    private function accountInput(Request $request): array
    {
        return [
            'parent_id' => (int)$request->input('parent_id', 0),
            'code' => (string)$request->input('code', ''),
            'name' => (string)$request->input('name', ''),
            'nature' => (string)$request->input('nature', ''),
            'account_type' => (string)$request->input('account_type', 'analytic'),
            'payment_method' => (string)$request->input('payment_method', ''),
            'default_for' => (string)$request->input('default_for', ''),
            'external_code' => (string)$request->input('external_code', ''),
            'description' => (string)$request->input('description', ''),
        ];
    }

    public function index(Request $request)
    {
        $this->authorizeRead();

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $svc = new ChartOfAccountsService($this->container);
        return $this->view('finance/chart_of_accounts', [
            'rows' => $svc->listAccounts(),
            'error' => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeManage();

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        try {
            (new ChartOfAccountsService($this->container))->createAccount($this->accountInput($request), $request->ip(), $request->header('user-agent'));
            return $this->redirect('/finance/chart-of-accounts?success=' . urlencode('Conta criada.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/chart-of-accounts?error=' . urlencode($e->getMessage()));
        }
    }

    public function edit(Request $request)
    {
        $this->authorizeRead();

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $svc = new ChartOfAccountsService($this->container);
        $row = $svc->getAccount($id);
        if ($row === null) {
            return $this->redirect('/finance/chart-of-accounts?error=' . urlencode('Conta inválida.'));
        }

        return $this->view('finance/chart_of_accounts_edit', [
            'row' => $row,
            'rows' => $svc->listAccounts(),
            'error' => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorizeManage();

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        try {
            (new ChartOfAccountsService($this->container))->updateAccount($id, $this->accountInput($request), $request->ip(), $request->header('user-agent'));
            return $this->redirect('/finance/chart-of-accounts/edit?id=' . $id . '&success=' . urlencode('Salvo.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/chart-of-accounts/edit?id=' . $id . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function setStatus(Request $request)
    {
        $this->authorizeManage();

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $status = trim((string)$request->input('status', ''));
        try {
            (new ChartOfAccountsService($this->container))->setAccountStatus($id, $status, $request->ip(), $request->header('user-agent'));
            return $this->redirect('/finance/chart-of-accounts?success=' . urlencode('Status atualizado.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/chart-of-accounts?error=' . urlencode($e->getMessage()));
        }
    }

    public function delete(Request $request)
    {
        $this->authorizeManage();

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        try {
            (new ChartOfAccountsService($this->container))->deleteAccount($id, $request->ip(), $request->header('user-agent'));
            return $this->redirect('/finance/chart-of-accounts?success=' . urlencode('Conta excluída.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/chart-of-accounts?error=' . urlencode($e->getMessage()));
        }
    }

    public function applyTemplate(Request $request)
    {
        $this->authorizeManage();

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        try {
            $res = (new ChartOfAccountsService($this->container))->applyDefaultTemplate($request->ip(), $request->header('user-agent'));
            $msg = 'Modelo padrão aplicado: ' . (int)$res['accounts_created'] . ' conta(s) criada(s)';
            $msg .= (int)$res['cost_centers_linked'] > 0
                ? ' e ' . (int)$res['cost_centers_linked'] . ' centro(s) de custo vinculado(s) automaticamente pelo nome.'
                : '.';
            return $this->redirect('/finance/chart-of-accounts?success=' . urlencode($msg));
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/chart-of-accounts?error=' . urlencode($e->getMessage()));
        }
    }
}
