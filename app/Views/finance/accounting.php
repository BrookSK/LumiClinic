<?php
/** @var string $from */
/** @var string $to */
/** @var bool $has_chart */
/** @var list<array<string,mixed>> $accounts */
/** @var array{revenue:float,costs:float,result:float} $result */
/** @var list<array<string,mixed>> $by_payment_account */
/** @var array{result_count:int,result_total:float,financial_count:int,financial_total:float} $unclassified */
/** @var array{in:float,out:float,net:float,count:int} $totals */
/** @var string $error */

$title = 'Relatórios para Contabilidade';

$from = (string)($from ?? date('Y-m-01'));
$to = (string)($to ?? date('Y-m-d'));
$accounts = $accounts ?? [];
$by_payment_account = $by_payment_account ?? [];
$result = $result ?? ['revenue' => 0.0, 'costs' => 0.0, 'result' => 0.0];
$unclassified = $unclassified ?? ['result_count' => 0, 'result_total' => 0.0, 'financial_count' => 0, 'financial_total' => 0.0];
$totals = $totals ?? ['in' => 0.0, 'out' => 0.0, 'net' => 0.0, 'count' => 0];
$error = (string)($error ?? '');

$money = static fn ($v): string => 'R$ ' . number_format((float)$v, 2, ',', '.');
$h = static fn ($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$fmtDate = static function (string $ymd): string {
    $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $ymd);
    return $d === false ? $ymd : $d->format('d/m/Y');
};

$natureLabels = \App\Services\Finance\ChartOfAccountsService::NATURES;
$query = http_build_query(['from' => $from, 'to' => $to]);

$resultRows = array_values(array_filter($accounts, static function (array $a): bool {
    $nature = (string)($a['nature'] ?? '');
    return in_array($nature, ['revenue', 'expense'], true) && (int)($a['total_count'] ?? 0) > 0;
}));

$resultColor = (float)$result['result'] >= 0 ? '#16a34a' : '#b91c1c';
$unclassifiedCount = (int)$unclassified['result_count'];
$unclassifiedFinancial = (int)$unclassified['financial_count'];

ob_start();
?>

<style>
.ac-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:16px}
.ac-kpi{padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06)}
.ac-kpi__label{font-size:12px;color:rgba(31,41,55,.50);font-weight:600;margin-bottom:4px}
.ac-kpi__value{font-size:22px;font-weight:900;color:rgba(31,41,55,.96)}
.ac-kpi__hint{font-size:11px;color:rgba(31,41,55,.40);margin-top:2px}
.ac-num{text-align:right;white-space:nowrap}
</style>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= $h($error) ?></div>
<?php endif; ?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;">Relatórios para Contabilidade</div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px; max-width:680px; line-height:1.5;">
            Lançamentos do fluxo de caixa de <?= $h($fmtDate($from)) ?> até <?= $h($fmtDate($to)) ?>, classificados pelo Plano de Contas
            (conta de resultado pelo centro de custo e conta financeira pela forma de pagamento).
        </div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/finance/accounting/entries.csv?<?= $h($query) ?>">Lançamentos (CSV)</a>
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/finance/accounting/summary.csv?<?= $h($query) ?>">Resumo por conta (CSV)</a>
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/finance/chart-of-accounts/export.csv">Plano de contas (CSV)</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:16px;">
    <div class="lc-card__body">
        <form method="get" action="/finance/accounting" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:end;">
            <div class="lc-field">
                <label class="lc-label">De</label>
                <input class="lc-input" type="date" name="from" value="<?= $h($from) ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Até</label>
                <input class="lc-input" type="date" name="to" value="<?= $h($to) ?>" />
            </div>
            <button class="lc-btn lc-btn--primary" type="submit">Atualizar</button>
            <a class="lc-btn lc-btn--secondary" href="/finance/chart-of-accounts">Plano de Contas</a>
            <a class="lc-btn lc-btn--secondary" href="/finance/cost-centers">Centros de Custo</a>
        </form>
    </div>
</div>

<?php if (!$has_chart): ?>
    <div class="lc-card">
        <div class="lc-card__header">A clínica ainda não tem plano de contas</div>
        <div class="lc-card__body">
            <div class="lc-muted" style="font-size:13px; line-height:1.6; max-width:720px;">
                Para gerar os relatórios contábeis, crie o plano de contas (há um modelo padrão para clínicas) e vincule os centros de custo às contas.
                No período há <?= (int)$totals['count'] ?> lançamento(s): entradas <?= $h($money($totals['in'])) ?> e saídas <?= $h($money($totals['out'])) ?>.
            </div>
            <a class="lc-btn lc-btn--primary" style="margin-top:12px;" href="/finance/chart-of-accounts">Criar plano de contas</a>
        </div>
    </div>
<?php else: ?>

    <div class="ac-kpis">
        <div class="ac-kpi">
            <div class="ac-kpi__label">Receitas</div>
            <div class="ac-kpi__value" style="color:#16a34a;"><?= $h($money($result['revenue'])) ?></div>
            <div class="ac-kpi__hint">Contas de receita (já com deduções)</div>
        </div>
        <div class="ac-kpi">
            <div class="ac-kpi__label">Custos e despesas</div>
            <div class="ac-kpi__value" style="color:#b91c1c;"><?= $h($money($result['costs'])) ?></div>
            <div class="ac-kpi__hint">Contas de custo / despesa</div>
        </div>
        <div class="ac-kpi">
            <div class="ac-kpi__label">Resultado</div>
            <div class="ac-kpi__value" style="color:<?= $resultColor ?>;"><?= $h($money($result['result'])) ?></div>
            <div class="ac-kpi__hint">Receitas - custos e despesas</div>
        </div>
        <div class="ac-kpi">
            <div class="ac-kpi__label">Fluxo de caixa do período</div>
            <div class="ac-kpi__value" style="font-size:16px;">
                <span style="color:#16a34a;"><?= $h($money($totals['in'])) ?></span> /
                <span style="color:#b91c1c;"><?= $h($money($totals['out'])) ?></span>
            </div>
            <div class="ac-kpi__hint"><?= (int)$totals['count'] ?> lançamento(s): entradas / saídas</div>
        </div>
    </div>

    <?php if ($unclassifiedCount > 0 || $unclassifiedFinancial > 0): ?>
        <div class="lc-alert" style="margin-bottom:16px; line-height:1.6; border-color:rgba(217,119,6,.55); background:rgba(254,243,199,.6);">
            <?php if ($unclassifiedCount > 0): ?>
                <div>
                    <?= $unclassifiedCount ?> lançamentos (<?= $h($money($unclassified['result_total'])) ?>) sem conta contábil.
                    Vincule os centros de custo às contas em <a href="/finance/cost-centers">Centros de Custo</a>
                    ou defina contas padrão no <a href="/finance/chart-of-accounts">Plano de Contas</a>.
                </div>
            <?php endif; ?>
            <?php if ($unclassifiedFinancial > 0): ?>
                <div>
                    <?= $unclassifiedFinancial ?> lançamentos (<?= $h($money($unclassified['financial_total'])) ?>) sem conta financeira.
                    Vincule cada forma de pagamento a uma conta analítica do Ativo no <a href="/finance/chart-of-accounts">Plano de Contas</a>.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="lc-card" style="margin-bottom:16px;">
        <div class="lc-card__header">Resultado por conta</div>
        <div class="lc-card__body" style="padding:0; overflow-x:auto;">
            <?php if ($resultRows === []): ?>
                <div class="lc-muted" style="padding:16px; font-size:13px;">Nenhum lançamento classificado em contas de receita ou despesa no período.</div>
            <?php else: ?>
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Código</th>
                        <th>Conta</th>
                        <th>Natureza</th>
                        <th class="ac-num">Entradas</th>
                        <th class="ac-num">Saídas</th>
                        <th class="ac-num">Saldo</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($resultRows as $a): ?>
                        <?php
                        $depth = (int)($a['depth'] ?? 0);
                        $synthetic = (string)($a['account_type'] ?? '') === 'synthetic';
                        $nature = (string)($a['nature'] ?? '');
                        $balance = (float)($a['total_balance'] ?? 0);
                        ?>
                        <tr>
                            <td style="white-space:nowrap; <?= $synthetic ? 'font-weight:700;' : '' ?>"><?= $h($a['code'] ?? '') ?></td>
                            <td style="padding-left:<?= 12 + ($depth * 20) ?>px; <?= $synthetic ? 'font-weight:700;' : '' ?>"><?= $h($a['name'] ?? '') ?></td>
                            <td><?= $h($natureLabels[$nature] ?? $nature) ?></td>
                            <td class="ac-num"><?= $h($money($a['total_in'] ?? 0)) ?></td>
                            <td class="ac-num"><?= $h($money($a['total_out'] ?? 0)) ?></td>
                            <td class="ac-num" style="font-weight:700; color:<?= $balance >= 0 ? '#16a34a' : '#b91c1c' ?>;"><?= $h($money($balance)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="lc-card">
        <div class="lc-card__header">Movimento por conta financeira</div>
        <div class="lc-card__body" style="padding:0; overflow-x:auto;">
            <?php if ($by_payment_account === []): ?>
                <div class="lc-muted" style="padding:16px; font-size:13px;">Nenhum lançamento no período.</div>
            <?php else: ?>
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Código</th>
                        <th>Conta</th>
                        <th class="ac-num">Lançamentos</th>
                        <th class="ac-num">Entradas</th>
                        <th class="ac-num">Saídas</th>
                        <th class="ac-num">Saldo do período</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($by_payment_account as $p): ?>
                        <?php $balance = (float)($p['balance'] ?? 0); ?>
                        <tr>
                            <td style="white-space:nowrap;"><?= (string)($p['code'] ?? '') !== '' ? $h($p['code']) : '<span class="lc-muted">-</span>' ?></td>
                            <td><?= $h($p['name'] ?? '') ?></td>
                            <td class="ac-num"><?= (int)($p['count'] ?? 0) ?></td>
                            <td class="ac-num"><?= $h($money($p['in'] ?? 0)) ?></td>
                            <td class="ac-num"><?= $h($money($p['out'] ?? 0)) ?></td>
                            <td class="ac-num" style="font-weight:700; color:<?= $balance >= 0 ? '#16a34a' : '#b91c1c' ?>;"><?= $h($money($balance)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
