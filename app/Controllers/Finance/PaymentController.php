<?php

declare(strict_types=1);

namespace App\Controllers\Finance;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Finance\SalesService;

final class PaymentController extends Controller
{
    private function isProfessionalRole(): bool
    {
        $codes = $_SESSION['role_codes'] ?? [];
        return is_array($codes) && in_array('professional', $codes, true);
    }

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

    public function create(Request $request)
    {
        $this->authorize('finance.payments.create');

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $saleId = (int)$request->input('sale_id', 0);
        $method = trim((string)$request->input('method', ''));
        $amount = trim((string)$request->input('amount', ''));
        $status = trim((string)$request->input('status', 'pending'));
        $fees = trim((string)$request->input('fees', '0'));
        $gatewayRef = trim((string)$request->input('gateway_ref', ''));

        if ($saleId <= 0) {
            return $this->redirect('/finance/sales');
        }

        try {
            $service = new SalesService($this->container);
            $service->addPayment($saleId, $method, $amount, $status, $fees, $gatewayRef === '' ? null : $gatewayRef, $request->ip());
            return $this->redirect('/finance/sales/view?id=' . $saleId);
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/sales/view?id=' . $saleId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function refund(Request $request)
    {
        $this->authorize('finance.payments.refund');

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $paymentId = (int)$request->input('payment_id', 0);
        $saleId = (int)$request->input('sale_id', 0);

        if ($paymentId <= 0 || $saleId <= 0) {
            return $this->redirect('/finance/sales');
        }

        try {
            $service = new SalesService($this->container);
            $service->refundPayment($paymentId, $request->ip());
            return $this->redirect('/finance/sales/view?id=' . $saleId);
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/sales/view?id=' . $saleId . '&error=' . urlencode($e->getMessage()));
        }
    }
}
