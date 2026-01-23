<?php

declare(strict_types=1);

namespace App\Controllers\Finance;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Finance\FinancialService;
use App\Services\Finance\SalesService;

final class FinancialController extends Controller
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

    public function cashflow(Request $request)
    {
        $this->authorize('finance.entries.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $from = trim((string)$request->input('from', date('Y-m-01')));
        $to = trim((string)$request->input('to', date('Y-m-d')));

        $service = new FinancialService($this->container);
        $data = $service->listEntries($from, $to);

        return $this->view('finance/cashflow', [
            'from' => $data['from'],
            'to' => $data['to'],
            'entries' => $data['entries'],
            'totals' => $data['totals'],
            'cost_centers' => $service->listCostCenters(),
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function createEntry(Request $request)
    {
        $this->authorize('finance.entries.create');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $kind = trim((string)$request->input('kind', 'in'));
        $occurredOn = trim((string)$request->input('occurred_on', date('Y-m-d')));
        $amount = trim((string)$request->input('amount', ''));
        $method = trim((string)$request->input('method', ''));
        $costCenterId = (int)$request->input('cost_center_id', 0);
        $desc = trim((string)$request->input('description', ''));

        try {
            $service = new FinancialService($this->container);
            $service->createEntry(
                $kind,
                $occurredOn,
                $amount,
                $method === '' ? null : $method,
                $costCenterId > 0 ? $costCenterId : null,
                $desc === '' ? null : $desc,
                $request->ip()
            );

            return $this->redirect('/finance/cashflow?from=' . urlencode($request->input('from', date('Y-m-01'))) . '&to=' . urlencode($request->input('to', date('Y-m-d'))));
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/cashflow?error=' . urlencode($e->getMessage()));
        }
    }

    public function deleteEntry(Request $request)
    {
        $this->authorize('finance.entries.delete');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $entryId = (int)$request->input('entry_id', 0);
        if ($entryId <= 0) {
            return $this->redirect('/finance/cashflow');
        }

        try {
            $service = new FinancialService($this->container);
            $service->deleteEntry($entryId, $request->ip());
            return $this->redirect('/finance/cashflow');
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/cashflow?error=' . urlencode($e->getMessage()));
        }
    }

    public function reports(Request $request)
    {
        $this->authorize('finance.reports.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $from = trim((string)$request->input('from', date('Y-m-01')));
        $to = trim((string)$request->input('to', date('Y-m-d')));
        $professionalId = (int)$request->input('professional_id', 0);

        if ($this->isProfessionalRole()) {
            $professionalId = $this->forceProfessionalIdForCurrentUser($clinicId);
        }

        $svc = new FinancialService($this->container);
        $data = $svc->reports($from, $to, $professionalId > 0 ? $professionalId : null);

        $sales = new SalesService($this->container);

        return $this->view('finance/reports', [
            'from' => $data['from'],
            'to' => $data['to'],
            'professional_id' => $professionalId,
            'by_professional' => $data['by_professional'],
            'by_service' => $data['by_service'],
            'ticket_medio' => $data['ticket_medio'],
            'appointments' => $data['appointments'],
            'paid_sales' => $data['paid_sales'],
            'conversion_rate' => $data['conversion_rate'],
            'recurring_revenue' => $data['recurring_revenue'],
            'professionals' => $sales->listReferenceProfessionals(),
            'is_professional' => $this->isProfessionalRole(),
        ]);
    }
}
