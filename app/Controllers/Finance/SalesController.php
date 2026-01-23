<?php

declare(strict_types=1);

namespace App\Controllers\Finance;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Finance\SalesService;

final class SalesController extends Controller
{
    private function isProfessionalRole(): bool
    {
        $codes = $_SESSION['role_codes'] ?? [];
        return is_array($codes) && in_array('professional', $codes, true);
    }

    private function forceProfessionalIdForCurrentUser(int $clinicId): int
    {
        $auth = new AuthService($this->container);
        $userId = $auth->userId();
        if ($userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new \App\Repositories\ProfessionalRepository($this->container->get(\PDO::class));
        $prof = $repo->findByUserId($clinicId, $userId);
        if ($prof === null) {
            throw new \RuntimeException('Profissional não vinculado ao usuário.');
        }

        return (int)$prof['id'];
    }

    private function assertProfessionalOwnsSale(int $clinicId, int $saleId): void
    {
        if (!$this->isProfessionalRole()) {
            return;
        }

        $ownProfessionalId = $this->forceProfessionalIdForCurrentUser($clinicId);
        $items = new \App\Repositories\SaleItemRepository($this->container->get(\PDO::class));
        if (!$items->saleHasProfessional($clinicId, $saleId, $ownProfessionalId)) {
            throw new \RuntimeException('Acesso negado.');
        }
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

    public function index(Request $request)
    {
        $this->authorize('finance.sales.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $service = new SalesService($this->container);

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $professionalId = null;
        if ($this->isProfessionalRole()) {
            $professionalId = $this->forceProfessionalIdForCurrentUser($clinicId);
        }

        return $this->view('finance/sales', [
            'sales' => $service->listSales($professionalId),
            'professionals' => $service->listReferenceProfessionals(),
            'services' => $service->listServices(),
            'packages' => $service->listPackages(),
            'plans' => $service->listSubscriptionPlans(),
            'error' => trim((string)$request->input('error', '')),
            'created' => (int)$request->input('created', 0),
            'is_professional' => $this->isProfessionalRole(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('finance.sales.create');

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $origin = trim((string)$request->input('origin', 'reception'));
        $desconto = trim((string)$request->input('desconto', '0'));
        $notes = trim((string)$request->input('notes', ''));

        try {
            $service = new SalesService($this->container);
            $saleId = $service->createSale($patientId > 0 ? $patientId : null, $origin, $desconto, $notes === '' ? null : $notes, $request->ip());
            return $this->redirect('/finance/sales/view?id=' . $saleId);
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/sales?error=' . urlencode($e->getMessage()));
        }
    }

    public function show(Request $request)
    {
        $this->authorize('finance.sales.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/finance/sales');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $this->assertProfessionalOwnsSale($clinicId, $id);

        $service = new SalesService($this->container);
        $data = $service->getSale($id);
        if ($data === null) {
            return $this->redirect('/finance/sales');
        }

        return $this->view('finance/sale_view', [
            'sale' => $data['sale'],
            'items' => $data['items'],
            'payments' => $data['payments'],
            'logs' => $data['logs'],
            'professionals' => $service->listReferenceProfessionals(),
            'services' => $service->listServices(),
            'packages' => $service->listPackages(),
            'plans' => $service->listSubscriptionPlans(),
            'error' => trim((string)$request->input('error', '')),
            'is_professional' => $this->isProfessionalRole(),
        ]);
    }

    public function addItem(Request $request)
    {
        $this->authorize('finance.sales.update');

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $saleId = (int)$request->input('sale_id', 0);
        $type = trim((string)$request->input('type', ''));
        $referenceId = (int)$request->input('reference_id', 0);
        $professionalId = (int)$request->input('professional_id', 0);
        $quantity = (int)$request->input('quantity', 1);
        $unitPrice = trim((string)$request->input('unit_price', '0'));

        if ($saleId <= 0) {
            return $this->redirect('/finance/sales');
        }

        try {
            $service = new SalesService($this->container);
            $service->addItem(
                $saleId,
                $type,
                $referenceId,
                $professionalId > 0 ? $professionalId : null,
                $quantity,
                $unitPrice,
                $request->ip()
            );
            return $this->redirect('/finance/sales/view?id=' . $saleId);
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/sales/view?id=' . $saleId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function cancel(Request $request)
    {
        $this->authorize('finance.sales.cancel');

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $saleId = (int)$request->input('sale_id', 0);
        if ($saleId <= 0) {
            return $this->redirect('/finance/sales');
        }

        try {
            $service = new SalesService($this->container);
            $service->cancelSale($saleId, $request->ip());
            return $this->redirect('/finance/sales/view?id=' . $saleId);
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/sales/view?id=' . $saleId . '&error=' . urlencode($e->getMessage()));
        }
    }
}
