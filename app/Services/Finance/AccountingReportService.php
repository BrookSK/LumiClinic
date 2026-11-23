<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Core\Container\Container;
use App\Repositories\CostCenterRepository;
use App\Repositories\FinancialEntryRepository;
use App\Services\Auth\AuthService;

/**
 * Relatórios para a contabilidade montados sobre o Plano de Contas.
 *
 * Regra de classificação de cada lançamento do fluxo de caixa (financial_entries):
 * - Conta de resultado: a conta vinculada ao centro de custo do lançamento (ativa, da mesma clínica);
 *   se não houver, a conta padrão (default_for) 'revenue' para entradas ou 'expense' para saídas;
 *   se ainda não houver, "Não classificado".
 * - Conta financeira (contrapartida): a conta analítica ativa vinculada à forma de pagamento do lançamento;
 *   se não houver (ou o lançamento não tiver forma de pagamento), "Não classificado".
 * - Partidas: entrada => Débito = conta financeira, Crédito = conta de resultado;
 *             saída   => Débito = conta de resultado, Crédito = conta financeira.
 */
final class AccountingReportService
{
    public const UNCLASSIFIED = 'Não classificado';

    /** Códigos antigos de forma de pagamento que não têm conta própria no plano. */
    private const EXTRA_METHOD_LABELS = [
        'card' => 'Cartão',
    ];

    public function __construct(private readonly Container $container) {}

    private function clinicIdOrFail(): int
    {
        $clinicId = (new AuthService($this->container))->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }
        return $clinicId;
    }

    private function normalizeDate(string $value, string $default): string
    {
        $value = trim($value);
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($d === false || $d->format('Y-m-d') !== $value) {
            return $default;
        }
        return $value;
    }

    /**
     * Valida o período (Y-m-d). Padrão: primeiro dia do mês até hoje. Inverte se "de" > "até".
     * Limite de 3 anos.
     *
     * @return array{0:string,1:string}
     */
    public function normalizePeriod(string $from, string $to): array
    {
        $from = $this->normalizeDate($from, date('Y-m-01'));
        $to = $this->normalizeDate($to, date('Y-m-d'));
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $limit = (new \DateTimeImmutable($from))->modify('+3 years');
        if (new \DateTimeImmutable($to) > $limit) {
            throw new \RuntimeException('O período máximo do relatório é de 3 anos. Reduza o intervalo de datas.');
        }

        return [$from, $to];
    }

    public static function methodLabel(?string $method): string
    {
        $method = trim((string)$method);
        if ($method === '') {
            return '';
        }
        return ChartOfAccountsService::PAYMENT_METHODS[$method] ?? (self::EXTRA_METHOD_LABELS[$method] ?? $method);
    }

    /**
     * @param array<string,mixed>|null $account
     * @return array{id:int,code:string,name:string,external_code:string}
     */
    private static function party(?array $account): array
    {
        if ($account === null) {
            return ['id' => 0, 'code' => '', 'name' => self::UNCLASSIFIED, 'external_code' => ''];
        }
        return [
            'id' => (int)$account['id'],
            'code' => (string)($account['code'] ?? ''),
            'name' => (string)($account['name'] ?? ''),
            'external_code' => (string)($account['external_code'] ?? ''),
        ];
    }

    /**
     * @return array{
     *   from:string,to:string,has_chart:bool,
     *   entries:list<array<string,mixed>>,
     *   accounts:list<array<string,mixed>>,
     *   result:array{revenue:float,costs:float,result:float},
     *   by_payment_account:list<array<string,mixed>>,
     *   unclassified:array{result_count:int,result_total:float,financial_count:int,financial_total:float},
     *   totals:array{in:float,out:float,net:float,count:int}
     * }
     */
    public function build(string $from, string $to): array
    {
        [$from, $to] = $this->normalizePeriod($from, $to);
        $clinicId = $this->clinicIdOrFail();
        $pdo = $this->container->get(\PDO::class);

        $accounts = (new ChartOfAccountsService($this->container))->listAccounts();

        $byId = [];
        $parentById = [];
        $paymentAccounts = [];
        $defaultRevenue = null;
        $defaultExpense = null;
        foreach ($accounts as $a) {
            $id = (int)$a['id'];
            $byId[$id] = $a;
            $parentById[$id] = isset($a['parent_id']) && $a['parent_id'] !== null ? (int)$a['parent_id'] : null;

            $active = (string)($a['status'] ?? '') === 'active';
            $analytic = (string)($a['account_type'] ?? '') === 'analytic';
            if (!$active || !$analytic) {
                continue;
            }
            $method = trim((string)($a['payment_method'] ?? ''));
            if ($method !== '' && !isset($paymentAccounts[$method])) {
                $paymentAccounts[$method] = $a;
            }
            $def = (string)($a['default_for'] ?? '');
            if ($def === 'revenue' && $defaultRevenue === null) {
                $defaultRevenue = $a;
            } elseif ($def === 'expense' && $defaultExpense === null) {
                $defaultExpense = $a;
            }
        }

        $rawEntries = (new FinancialEntryRepository($pdo))->listForAccounting($clinicId, $from, $to);

        $entries = [];
        $own = [];
        $payment = [];
        $totals = ['in' => 0.0, 'out' => 0.0, 'net' => 0.0, 'count' => 0];
        $unclassified = ['result_count' => 0, 'result_total' => 0.0, 'financial_count' => 0, 'financial_total' => 0.0];
        $revenue = 0.0;
        $costs = 0.0;

        foreach ($rawEntries as $e) {
            $kind = (string)($e['kind'] ?? '');
            if ($kind !== 'in' && $kind !== 'out') {
                continue; // fora do fluxo de caixa (summarizeTotalsByClinicRange também ignora)
            }
            $amount = (float)($e['amount'] ?? 0);
            $method = trim((string)($e['method'] ?? ''));

            // Conta de resultado
            $resultAccount = null;
            $linkedId = isset($e['chart_account_id']) && $e['chart_account_id'] !== null ? (int)$e['chart_account_id'] : 0;
            if ($linkedId > 0 && isset($byId[$linkedId]) && (string)($byId[$linkedId]['status'] ?? '') === 'active') {
                $resultAccount = $byId[$linkedId];
            }
            if ($resultAccount === null) {
                $resultAccount = $kind === 'in' ? $defaultRevenue : $defaultExpense;
            }

            // Conta financeira (contrapartida)
            $financialAccount = ($method !== '' && isset($paymentAccounts[$method])) ? $paymentAccounts[$method] : null;

            $resultParty = self::party($resultAccount);
            $financialParty = self::party($financialAccount);

            $entries[] = [
                'id' => (int)($e['id'] ?? 0),
                'kind' => $kind,
                'occurred_on' => (string)($e['occurred_on'] ?? ''),
                'amount' => $amount,
                'method' => $method,
                'method_label' => self::methodLabel($method),
                'status' => (string)($e['status'] ?? ''),
                'description' => (string)($e['description'] ?? ''),
                'sale_id' => isset($e['sale_id']) && $e['sale_id'] !== null ? (int)$e['sale_id'] : null,
                'payment_id' => isset($e['payment_id']) && $e['payment_id'] !== null ? (int)$e['payment_id'] : null,
                'cost_center_code' => (string)($e['cost_center_code'] ?? ''),
                'cost_center_name' => (string)($e['cost_center_name'] ?? ''),
                'result_account' => $resultParty,
                'financial_account' => $financialParty,
                'debit' => $kind === 'in' ? $financialParty : $resultParty,
                'credit' => $kind === 'in' ? $resultParty : $financialParty,
            ];

            $totals[$kind] += $amount;
            $totals['count']++;

            if ($resultAccount === null) {
                $unclassified['result_count']++;
                $unclassified['result_total'] += $amount;
            }
            if ($financialAccount === null) {
                $unclassified['financial_count']++;
                $unclassified['financial_total'] += $amount;
            }

            // Movimento próprio de cada conta envolvida (uma vez por conta).
            $touched = [];
            if ($resultAccount !== null) {
                $touched[(int)$resultAccount['id']] = true;
            }
            if ($financialAccount !== null) {
                $touched[(int)$financialAccount['id']] = true;
            }
            foreach (array_keys($touched) as $aid) {
                if (!isset($own[$aid])) {
                    $own[$aid] = ['in' => 0.0, 'out' => 0.0, 'count' => 0];
                }
                $own[$aid][$kind] += $amount;
                $own[$aid]['count']++;
            }

            // Resultado do período, com sinal pelo tipo do lançamento (entrada = +, saída = -):
            //   receitas = soma(entradas - saídas) nas contas de natureza Receita (inclui 4.3 Deduções da Receita,
            //              que normalmente recebem saídas e por isso reduzem a receita);
            //   custos e despesas = soma(saídas - entradas) nas contas de natureza Custo / Despesa;
            //   resultado = receitas - custos e despesas.
            // Lançamentos sem conta de resultado (ou em contas patrimoniais) não entram no resultado.
            $signed = $kind === 'in' ? $amount : -$amount;
            $nature = $resultAccount !== null ? (string)($resultAccount['nature'] ?? '') : '';
            if ($nature === 'revenue') {
                $revenue += $signed;
            } elseif ($nature === 'expense') {
                $costs -= $signed;
            }

            // Movimento por conta financeira
            $pid = $financialParty['id'];
            if (!isset($payment[$pid])) {
                $payment[$pid] = [
                    'account_id' => $pid,
                    'code' => $financialParty['code'],
                    'name' => $financialParty['name'],
                    'in' => 0.0,
                    'out' => 0.0,
                    'balance' => 0.0,
                    'count' => 0,
                ];
            }
            $payment[$pid][$kind] += $amount;
            $payment[$pid]['balance'] += $signed;
            $payment[$pid]['count']++;
        }
        $totals['net'] = $totals['in'] - $totals['out'];

        // Totais acumulados: cada conta soma o próprio movimento e o de todas as descendentes.
        $rolled = [];
        foreach ($own as $aid => $mov) {
            $cursor = $aid;
            $guard = 0;
            while ($cursor !== null && isset($byId[$cursor]) && $guard < 30) {
                if (!isset($rolled[$cursor])) {
                    $rolled[$cursor] = ['in' => 0.0, 'out' => 0.0, 'count' => 0];
                }
                $rolled[$cursor]['in'] += $mov['in'];
                $rolled[$cursor]['out'] += $mov['out'];
                $rolled[$cursor]['count'] += $mov['count'];
                $cursor = $parentById[$cursor] ?? null;
                $guard++;
            }
        }

        $accountRows = [];
        foreach ($accounts as $a) {
            $aid = (int)$a['id'];
            $o = $own[$aid] ?? ['in' => 0.0, 'out' => 0.0, 'count' => 0];
            $r = $rolled[$aid] ?? ['in' => 0.0, 'out' => 0.0, 'count' => 0];
            $a['own_in'] = $o['in'];
            $a['own_out'] = $o['out'];
            $a['own_count'] = $o['count'];
            $a['total_in'] = $r['in'];
            $a['total_out'] = $r['out'];
            $a['total_balance'] = $r['in'] - $r['out'];
            $a['total_count'] = $r['count'];
            $accountRows[] = $a;
        }

        // Contas financeiras na ordem do plano; "Não classificado" por último.
        $byPayment = [];
        foreach ($accounts as $a) {
            $aid = (int)$a['id'];
            if (isset($payment[$aid])) {
                $byPayment[] = $payment[$aid];
            }
        }
        if (isset($payment[0])) {
            $byPayment[] = $payment[0];
        }

        return [
            'from' => $from,
            'to' => $to,
            'has_chart' => $accounts !== [],
            'entries' => $entries,
            'accounts' => $accountRows,
            'result' => ['revenue' => $revenue, 'costs' => $costs, 'result' => $revenue - $costs],
            'by_payment_account' => $byPayment,
            'unclassified' => $unclassified,
            'totals' => $totals,
        ];
    }

    /**
     * Plano de contas para exportação, com código da conta pai e nomes dos centros de custo vinculados.
     *
     * @return list<array<string,mixed>>
     */
    public function chartExportRows(): array
    {
        $clinicId = $this->clinicIdOrFail();
        $accounts = (new ChartOfAccountsService($this->container))->listAccounts();
        $costCenters = (new CostCenterRepository($this->container->get(\PDO::class)))->listByClinic($clinicId);

        $codeById = [];
        foreach ($accounts as $a) {
            $codeById[(int)$a['id']] = (string)($a['code'] ?? '');
        }

        $ccNames = [];
        foreach ($costCenters as $cc) {
            $aid = isset($cc['chart_account_id']) && $cc['chart_account_id'] !== null ? (int)$cc['chart_account_id'] : 0;
            if ($aid > 0) {
                $ccNames[$aid][] = (string)($cc['name'] ?? '');
            }
        }

        $rows = [];
        foreach ($accounts as $a) {
            $aid = (int)$a['id'];
            $pid = isset($a['parent_id']) && $a['parent_id'] !== null ? (int)$a['parent_id'] : 0;
            $a['parent_code'] = $pid > 0 ? ($codeById[$pid] ?? '') : '';
            $a['cost_center_names'] = $ccNames[$aid] ?? [];
            $rows[] = $a;
        }

        return $rows;
    }
}
