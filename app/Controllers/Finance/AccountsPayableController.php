<?php

declare(strict_types=1);

namespace App\Controllers\Finance;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Finance\AccountsPayableService;
use App\Services\Finance\FinancialService;

final class AccountsPayableController extends Controller
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

    public function index(Request $request)
    {
        $this->authorize('finance.ap.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $from = trim((string)$request->input('from', date('Y-m-01')));
        $to = trim((string)$request->input('to', (new \DateTimeImmutable('today'))->modify('+90 days')->format('Y-m-d')));
        $status = trim((string)$request->input('status', 'open'));

        $svc = new AccountsPayableService($this->container);
        $items = $svc->listProjectedInstallments($from, $to, $status);

        $total = 0.0;
        foreach ($items as $it) {
            $total += (float)($it['amount'] ?? 0);
        }

        $fin = new FinancialService($this->container);

        return $this->view('finance/accounts_payable', [
            'from' => $from,
            'to' => $to,
            'status' => $status,
            'items' => $items,
            'total' => $total,
            'cost_centers' => $fin->listCostCenters(),
            'error' => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('finance.ap.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $from = trim((string)$request->input('from', date('Y-m-01')));
        $to = trim((string)$request->input('to', (new \DateTimeImmutable('today'))->modify('+90 days')->format('Y-m-d')));
        $status = trim((string)$request->input('status', 'open'));

        try {
            (new AccountsPayableService($this->container))->create([
                'vendor_name' => trim((string)$request->input('vendor_name', '')),
                'title' => trim((string)$request->input('title', '')),
                'description' => trim((string)$request->input('description', '')),
                'cost_center_id' => (int)$request->input('cost_center_id', 0),
                'payable_type' => trim((string)$request->input('payable_type', 'single')),
                'start_due_date' => trim((string)$request->input('start_due_date', date('Y-m-d'))),
                'amount' => trim((string)$request->input('amount', '')),
                'total_installments' => (int)$request->input('total_installments', 1),
                'recurrence_until' => trim((string)$request->input('recurrence_until', '')),
            ], $request->ip(), $request->header('user-agent'));

            return $this->redirect('/finance/accounts-payable?from=' . urlencode($from) . '&to=' . urlencode($to) . '&status=' . urlencode($status) . '&success=' . urlencode('Criado.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/accounts-payable?from=' . urlencode($from) . '&to=' . urlencode($to) . '&status=' . urlencode($status) . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function pay(Request $request)
    {
        $this->authorize('finance.ap.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $installmentId = (int)$request->input('installment_id', 0);
        $paidOn = trim((string)$request->input('paid_on', date('Y-m-d')));
        $method = trim((string)$request->input('method', ''));

        $from = trim((string)$request->input('from', date('Y-m-01')));
        $to = trim((string)$request->input('to', (new \DateTimeImmutable('today'))->modify('+90 days')->format('Y-m-d')));
        $status = trim((string)$request->input('status', 'open'));

        if ($installmentId <= 0) {
            return $this->redirect('/finance/accounts-payable?from=' . urlencode($from) . '&to=' . urlencode($to) . '&status=' . urlencode($status) . '&error=' . urlencode('Parcela inválida.'));
        }

        try {
            (new AccountsPayableService($this->container))->markInstallmentPaid($installmentId, $paidOn, ($method === '' ? null : $method), $request->ip(), $request->header('user-agent'));
            return $this->redirect('/finance/accounts-payable?from=' . urlencode($from) . '&to=' . urlencode($to) . '&status=' . urlencode($status) . '&success=' . urlencode('Pagamento registrado.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/accounts-payable?from=' . urlencode($from) . '&to=' . urlencode($to) . '&status=' . urlencode($status) . '&error=' . urlencode($e->getMessage()));
        }
    }
}
