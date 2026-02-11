<?php

declare(strict_types=1);

namespace App\Controllers\Finance;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\ServiceCatalogRepository;
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
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 100);

        $page = max(1, $page);
        $perPage = max(25, min(200, $perPage));
        $offset = ($page - 1) * $perPage;

        $service = new FinancialService($this->container);
        $data = $service->listEntries($from, $to, $perPage + 1, $offset);

        $hasNext = count($data['entries']) > $perPage;
        if ($hasNext) {
            $data['entries'] = array_slice($data['entries'], 0, $perPage);
        }

        return $this->view('finance/cashflow', [
            'from' => $data['from'],
            'to' => $data['to'],
            'entries' => $data['entries'],
            'totals' => $data['totals'],
            'cost_centers' => $service->listCostCenters(),
            'error' => trim((string)$request->input('error', '')),
            'page' => $page,
            'per_page' => $perPage,
            'has_next' => $hasNext,
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
                $request->ip(),
                $request->header('user-agent')
            );

            return $this->redirect('/finance/cashflow?from=' . urlencode($request->input('from', date('Y-m-01'))) . '&to=' . urlencode($request->input('to', date('Y-m-d'))));
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/cashflow?error=' . urlencode($e->getMessage()));
        }
    }

    public function costCenters(Request $request)
    {
        $this->authorize('finance.cost_centers.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $svc = new FinancialService($this->container);
        return $this->view('finance/cost_centers', [
            'rows' => $svc->listAllCostCenters(),
            'error' => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
        ]);
    }

    public function createCostCenter(Request $request)
    {
        $this->authorize('finance.cost_centers.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $name = trim((string)$request->input('name', ''));
        try {
            (new FinancialService($this->container))->createCostCenter($name, $request->ip(), $request->header('user-agent'));
            return $this->redirect('/finance/cost-centers?success=' . urlencode('Criado.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/cost-centers?error=' . urlencode($e->getMessage()));
        }
    }

    public function editCostCenter(Request $request)
    {
        $this->authorize('finance.cost_centers.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/finance/cost-centers');
        }

        $svc = new FinancialService($this->container);
        $row = $svc->getCostCenter($id);
        if ($row === null) {
            return $this->redirect('/finance/cost-centers?error=' . urlencode('Centro de custo inválido.'));
        }

        return $this->view('finance/cost_centers_edit', [
            'row' => $row,
            'error' => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
        ]);
    }

    public function updateCostCenter(Request $request)
    {
        $this->authorize('finance.cost_centers.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $id = (int)$request->input('id', 0);
        $name = trim((string)$request->input('name', ''));

        try {
            (new FinancialService($this->container))->updateCostCenter($id, $name, $request->ip(), $request->header('user-agent'));
            return $this->redirect('/finance/cost-centers/edit?id=' . $id . '&success=' . urlencode('Salvo.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/cost-centers/edit?id=' . $id . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function setCostCenterStatus(Request $request)
    {
        $this->authorize('finance.cost_centers.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $id = (int)$request->input('id', 0);
        $status = trim((string)$request->input('status', ''));

        try {
            (new FinancialService($this->container))->setCostCenterStatus($id, $status, $request->ip(), $request->header('user-agent'));
            return $this->redirect('/finance/cost-centers?success=' . urlencode('Status atualizado.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/cost-centers?error=' . urlencode($e->getMessage()));
        }
    }

    public function deleteCostCenter(Request $request)
    {
        $this->authorize('finance.cost_centers.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $id = (int)$request->input('id', 0);
        try {
            (new FinancialService($this->container))->deleteCostCenter($id, $request->ip(), $request->header('user-agent'));
            return $this->redirect('/finance/cost-centers?success=' . urlencode('Excluído.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/cost-centers?error=' . urlencode($e->getMessage()));
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
            $service->deleteEntry($entryId, $request->ip(), $request->header('user-agent'));
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

    public function reportsExportCsv(Request $request): Response
    {
        $this->authorize('finance.reports.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return Response::html('Contexto inválido.', 403);
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
        $professionals = $sales->listReferenceProfessionals();
        $profNameMap = [];
        foreach ($professionals as $p) {
            $profNameMap[(int)$p['id']] = (string)($p['name'] ?? '');
        }

        $services = (new ServiceCatalogRepository($this->container->get(\PDO::class)))->listActiveByClinic($clinicId);
        $svcNameMap = [];
        foreach ($services as $s) {
            $svcNameMap[(int)$s['id']] = (string)($s['name'] ?? '');
        }

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['relatorio', 'de', 'ate', 'profissional_id']);
        fputcsv($out, ['financeiro', (string)$data['from'], (string)$data['to'], (string)$professionalId]);
        fputcsv($out, []);

        fputcsv($out, ['indicador', 'valor']);
        fputcsv($out, ['ticket_medio', (string)$data['ticket_medio']]);
        fputcsv($out, ['agendamentos', (string)$data['appointments']]);
        fputcsv($out, ['vendas_pagas', (string)$data['paid_sales']]);
        fputcsv($out, ['taxa_conversao', (string)$data['conversion_rate']]);
        fputcsv($out, ['receita_recorrente', (string)$data['recurring_revenue']]);
        fputcsv($out, []);

        fputcsv($out, ['receita_por_profissional']);
        fputcsv($out, ['profissional', 'receita']);
        foreach (($data['by_professional'] ?? []) as $r) {
            $pid = $r['professional_id'] === null ? 0 : (int)$r['professional_id'];
            $pname = $pid > 0 ? (string)($profNameMap[$pid] ?? '') : '';
            fputcsv($out, [$pname !== '' ? $pname : ($pid > 0 ? ('Profissional #' . $pid) : ''), (string)($r['revenue'] ?? '')]);
        }
        fputcsv($out, []);

        fputcsv($out, ['receita_por_servico']);
        fputcsv($out, ['servico', 'receita']);
        foreach (($data['by_service'] ?? []) as $r) {
            $sid = (int)($r['service_id'] ?? 0);
            $sname = $sid > 0 ? (string)($svcNameMap[$sid] ?? '') : '';
            fputcsv($out, [$sname !== '' ? $sname : ($sid > 0 ? ('Serviço #' . $sid) : ''), (string)($r['revenue'] ?? '')]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $filename = 'finance_reports_' . date('Ymd_His') . '.csv';
        return Response::raw((string)$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function reportsExportPdf(Request $request): Response
    {
        $this->authorize('finance.reports.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $dompdfClass = 'Dompdf\\Dompdf';
        if (!class_exists($dompdfClass)) {
            return Response::html('Exportação em PDF indisponível. Instale a dependência dompdf/dompdf via Composer.', 501);
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return Response::html('Contexto inválido.', 403);
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
        $professionals = $sales->listReferenceProfessionals();
        $profNameMap = [];
        foreach ($professionals as $p) {
            $profNameMap[(int)$p['id']] = (string)($p['name'] ?? '');
        }

        $services = (new ServiceCatalogRepository($this->container->get(\PDO::class)))->listActiveByClinic($clinicId);
        $svcNameMap = [];
        foreach ($services as $s) {
            $svcNameMap[(int)$s['id']] = (string)($s['name'] ?? '');
        }

        $html = '<!doctype html><html><head><meta charset="utf-8" />'
            . '<style>body{font-family:DejaVu Sans, sans-serif;font-size:12px}h1{font-size:16px;margin:0 0 8px}h2{font-size:13px;margin:14px 0 6px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px}th{background:#f5f5f5;text-align:left}</style>'
            . '</head><body>';

        $html .= '<h1>Relatório Financeiro</h1>';
        $html .= '<div>Período: ' . htmlspecialchars((string)$data['from'], ENT_QUOTES, 'UTF-8') . ' a ' . htmlspecialchars((string)$data['to'], ENT_QUOTES, 'UTF-8') . '</div>';

        $html .= '<h2>Indicadores</h2><table><thead><tr><th>Indicador</th><th>Valor</th></tr></thead><tbody>';
        $html .= '<tr><td>Ticket médio</td><td>R$ ' . number_format((float)$data['ticket_medio'], 2, ',', '.') . '</td></tr>';
        $html .= '<tr><td>Agendamentos</td><td>' . (int)$data['appointments'] . '</td></tr>';
        $html .= '<tr><td>Vendas pagas</td><td>' . (int)$data['paid_sales'] . '</td></tr>';
        $html .= '<tr><td>Taxa de conversão</td><td>' . number_format(((float)$data['conversion_rate']) * 100.0, 2, ',', '.') . '%</td></tr>';
        $html .= '<tr><td>Receita recorrente</td><td>R$ ' . number_format((float)$data['recurring_revenue'], 2, ',', '.') . '</td></tr>';
        $html .= '</tbody></table>';

        $html .= '<h2>Receita por profissional</h2><table><thead><tr><th>Profissional</th><th>Receita</th></tr></thead><tbody>';
        foreach (($data['by_professional'] ?? []) as $r) {
            $pid = $r['professional_id'] === null ? 0 : (int)$r['professional_id'];
            $pname = $pid > 0 ? (string)($profNameMap[$pid] ?? '') : '';
            $label = $pname !== '' ? $pname : ($pid > 0 ? ('Profissional #' . $pid) : '-');
            $html .= '<tr><td>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td><td>R$ ' . number_format((float)($r['revenue'] ?? 0), 2, ',', '.') . '</td></tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<h2>Receita por serviço</h2><table><thead><tr><th>Serviço</th><th>Receita</th></tr></thead><tbody>';
        foreach (($data['by_service'] ?? []) as $r) {
            $sid = (int)($r['service_id'] ?? 0);
            $sname = $sid > 0 ? (string)($svcNameMap[$sid] ?? '') : '';
            $label = $sname !== '' ? $sname : ($sid > 0 ? ('Serviço #' . $sid) : '-');
            $html .= '<tr><td>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td><td>R$ ' . number_format((float)($r['revenue'] ?? 0), 2, ',', '.') . '</td></tr>';
        }
        $html .= '</tbody></table>';

        $html .= '</body></html>';

        /** @var object $dompdf */
        $dompdf = new $dompdfClass();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf = $dompdf->output();

        $filename = 'finance_reports_' . date('Ymd_His') . '.pdf';
        return Response::raw((string)$pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
