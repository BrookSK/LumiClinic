<?php

declare(strict_types=1);

namespace App\Controllers\Finance;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\Auth\AuthService;
use App\Services\Compliance\DataExportService;
use App\Services\Finance\AccountingReportService;
use App\Services\Finance\ChartOfAccountsService;

final class AccountingReportsController extends Controller
{
    private function authFinance(string $perm): void
    {
        try { $this->authorize($perm); } catch (\Throwable $e) { $this->authorize('finance.sales.read'); }
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

    private function isProfessionalRole(): bool
    {
        $codes = $_SESSION['role_codes'] ?? [];
        return is_array($codes) && in_array('professional', $codes, true);
    }

    /** Mesma regra de /finance/reports, sem acesso para profissionais. */
    private function authorizeReports(): void
    {
        $this->authFinance('finance.reports.read');

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }
    }

    /** Texto para CSV: remove quebras de linha e neutraliza fórmulas (=, +, -, @). */
    private static function csvText(?string $value): string
    {
        $value = str_replace(["\r\n", "\n", "\r"], ' ', (string)$value);
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t"], true)) {
            $value = "'" . $value;
        }
        return $value;
    }

    private static function csvMoney(float $value): string
    {
        return number_format($value, 2, ',', '');
    }

    private static function csvDate(string $ymd): string
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', substr($ymd, 0, 10));
        return $d === false ? $ymd : $d->format('d/m/Y');
    }

    /** @param list<string|int> $fields */
    private static function csvLine(array $fields): string
    {
        return implode(';', array_map(static fn ($v) => '"' . str_replace('"', '""', (string)$v) . '"', $fields)) . "\n";
    }

    private static function csvResponse(string $csv, string $filename): Response
    {
        return Response::raw("\xEF\xBB\xBF" . $csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function index(Request $request)
    {
        $this->authorizeReports();

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $svc = new AccountingReportService($this->container);
        try {
            $data = $svc->build((string)$request->input('from', ''), (string)$request->input('to', ''));
        } catch (\RuntimeException $e) {
            if (trim((string)$request->input('error', '')) !== '') {
                throw $e;
            }
            return $this->redirect('/finance/accounting?error=' . urlencode($e->getMessage()));
        }

        unset($data['entries']);

        return $this->view('finance/accounting', $data + [
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function entriesCsv(Request $request): Response
    {
        $this->authorizeReports();

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        try {
            $data = (new AccountingReportService($this->container))->build((string)$request->input('from', ''), (string)$request->input('to', ''));
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/accounting?error=' . urlencode($e->getMessage()));
        }

        $csv = self::csvLine([
            'Data', 'Tipo', 'Descrição', 'Valor', 'Forma de pagamento',
            'Centro de custo - código', 'Centro de custo',
            'Conta débito - código', 'Conta débito', 'Conta crédito - código', 'Conta crédito',
            'Código externo débito', 'Código externo crédito',
            'Conta de resultado - código', 'Conta de resultado',
            'ID lançamento', 'ID venda', 'ID pagamento',
        ]);

        foreach ($data['entries'] as $e) {
            $csv .= self::csvLine([
                self::csvDate((string)$e['occurred_on']),
                $e['kind'] === 'in' ? 'Receita' : 'Despesa',
                self::csvText((string)$e['description']),
                self::csvMoney((float)$e['amount']),
                self::csvText((string)$e['method_label']),
                self::csvText((string)$e['cost_center_code']),
                self::csvText((string)$e['cost_center_name']),
                self::csvText((string)$e['debit']['code']),
                self::csvText((string)$e['debit']['name']),
                self::csvText((string)$e['credit']['code']),
                self::csvText((string)$e['credit']['name']),
                self::csvText((string)$e['debit']['external_code']),
                self::csvText((string)$e['credit']['external_code']),
                self::csvText((string)$e['result_account']['code']),
                self::csvText((string)$e['result_account']['name']),
                (int)$e['id'],
                $e['sale_id'] !== null ? (int)$e['sale_id'] : '',
                $e['payment_id'] !== null ? (int)$e['payment_id'] : '',
            ]);
        }

        (new DataExportService($this->container))->record(
            'finance.accounting.export_entries', null, null, 'csv', null,
            ['from' => $data['from'], 'to' => $data['to']],
            $request->ip(), $request->header('user-agent')
        );

        return self::csvResponse($csv, 'lancamentos_contabeis_' . $data['from'] . '_' . $data['to'] . '.csv');
    }

    public function summaryCsv(Request $request): Response
    {
        $this->authorizeReports();

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        try {
            $data = (new AccountingReportService($this->container))->build((string)$request->input('from', ''), (string)$request->input('to', ''));
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/accounting?error=' . urlencode($e->getMessage()));
        }

        $natures = ChartOfAccountsService::NATURES;
        $types = ['synthetic' => 'Sintética', 'analytic' => 'Analítica'];

        $csv = self::csvLine(['Código', 'Conta', 'Natureza', 'Tipo', 'Código externo', 'Entradas', 'Saídas', 'Saldo']);
        foreach ($data['accounts'] as $a) {
            $nature = (string)($a['nature'] ?? '');
            $type = (string)($a['account_type'] ?? '');
            $csv .= self::csvLine([
                self::csvText((string)($a['code'] ?? '')),
                self::csvText((string)($a['name'] ?? '')),
                $natures[$nature] ?? self::csvText($nature),
                $types[$type] ?? self::csvText($type),
                self::csvText((string)($a['external_code'] ?? '')),
                self::csvMoney((float)$a['total_in']),
                self::csvMoney((float)$a['total_out']),
                self::csvMoney((float)$a['total_balance']),
            ]);
        }

        $un = $data['unclassified'];
        $csv .= "\n";
        $csv .= self::csvLine(['Total receitas', '', '', '', '', '', '', self::csvMoney((float)$data['result']['revenue'])]);
        $csv .= self::csvLine(['Total custos e despesas', '', '', '', '', '', '', self::csvMoney((float)$data['result']['costs'])]);
        $csv .= self::csvLine(['Resultado do período', '', '', '', '', '', '', self::csvMoney((float)$data['result']['result'])]);
        $csv .= self::csvLine(['Lançamentos sem conta de resultado', (int)$un['result_count'] . ' lançamento(s)', '', '', '', '', '', self::csvMoney((float)$un['result_total'])]);
        $csv .= self::csvLine(['Lançamentos sem conta financeira', (int)$un['financial_count'] . ' lançamento(s)', '', '', '', '', '', self::csvMoney((float)$un['financial_total'])]);

        (new DataExportService($this->container))->record(
            'finance.accounting.export_summary', null, null, 'csv', null,
            ['from' => $data['from'], 'to' => $data['to']],
            $request->ip(), $request->header('user-agent')
        );

        return self::csvResponse($csv, 'resumo_contabil_' . $data['from'] . '_' . $data['to'] . '.csv');
    }

    public function chartCsv(Request $request): Response
    {
        try { $this->authorize('finance.cost_centers.manage'); } catch (\Throwable $e) { $this->authFinance('finance.reports.read'); }

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $rows = (new AccountingReportService($this->container))->chartExportRows();

        $natures = ChartOfAccountsService::NATURES;
        $types = ['synthetic' => 'Sintética', 'analytic' => 'Analítica'];
        $methods = ChartOfAccountsService::PAYMENT_METHODS;
        $defaults = ChartOfAccountsService::DEFAULT_FOR;
        $statuses = ['active' => 'Ativa', 'disabled' => 'Inativa', 'inactive' => 'Inativa'];

        $csv = self::csvLine([
            'Código', 'Conta', 'Conta pai - código', 'Natureza', 'Tipo', 'Forma de pagamento',
            'Conta padrão para', 'Código externo', 'Descrição', 'Status', 'Centros de custo vinculados',
        ]);
        foreach ($rows as $a) {
            $nature = (string)($a['nature'] ?? '');
            $type = (string)($a['account_type'] ?? '');
            $method = (string)($a['payment_method'] ?? '');
            $default = (string)($a['default_for'] ?? '');
            $status = (string)($a['status'] ?? '');
            $csv .= self::csvLine([
                self::csvText((string)($a['code'] ?? '')),
                self::csvText((string)($a['name'] ?? '')),
                self::csvText((string)($a['parent_code'] ?? '')),
                $natures[$nature] ?? self::csvText($nature),
                $types[$type] ?? self::csvText($type),
                $method === '' ? '' : ($methods[$method] ?? self::csvText($method)),
                $default === '' ? '' : ($defaults[$default] ?? self::csvText($default)),
                self::csvText((string)($a['external_code'] ?? '')),
                self::csvText((string)($a['description'] ?? '')),
                $statuses[$status] ?? self::csvText($status),
                self::csvText(implode(', ', (array)$a['cost_center_names'])),
            ]);
        }

        (new DataExportService($this->container))->record(
            'finance.chart_accounts.export', null, null, 'csv', null,
            ['accounts' => count($rows)],
            $request->ip(), $request->header('user-agent')
        );

        return self::csvResponse($csv, 'plano_de_contas_' . date('Ymd') . '.csv');
    }
}
