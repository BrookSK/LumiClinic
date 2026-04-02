<?php
/** @var string $from */
/** @var string $to */
/** @var int $professional_id */
/** @var list<array<string,mixed>> $by_professional */
/** @var list<array<string,mixed>> $by_service */
/** @var float $ticket_medio */
/** @var int $appointments */
/** @var int $paid_sales */
/** @var float $conversion_rate */
/** @var float $recurring_revenue */
/** @var float $kpi_in_total */
/** @var float $kpi_out_total */
/** @var float $kpi_net_total */
/** @var float $kpi_revenue_total */
/** @var list<array<string,mixed>> $recent_sales */
/** @var list<array<string,mixed>> $recent_entries */
/** @var list<array<string,mixed>> $professionals */
/** @var bool $is_professional */

$title = 'Relatório Financeiro';

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) return false;
    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) return false;
        return in_array($permissionCode, $permissions['allow'], true);
    }
    return in_array($permissionCode, $permissions, true);
};

$profMap = [];
foreach ($professionals as $p) {
    $profMap[(int)$p['id']] = $p;
}

$netColor = (float)$kpi_net_total >= 0 ? '#16a34a' : '#b91c1c';

$exportQuery = ['from' => (string)$from, 'to' => (string)$to, 'professional_id' => (int)$professional_id];

ob_start();
?>

<style>
.fr-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px}
.fr-head__title{font-weight:850;font-size:20px;color:rgba(31,41,55,.96)}
.fr-head__period{font-size:13px;color:rgba(31,41,55,.50)}
.fr-filters{padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:18px}
.fr-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:18px}
.fr-kpi{padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06)}
.fr-kpi__label{font-size:12px;color:rgba(31,41,55,.50);font-weight:600;margin-bottom:4px}
.fr-kpi__value{font-size:22px;font-weight:900;color:rgba(31,41,55,.96)}
.fr-kpi__hint{font-size:11px;color:rgba(31,41,55,.40);margin-top:2px}
.fr-section{margin-bottom:16px}
.fr-section summary{list-style:none;cursor:pointer;padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);display:flex;align-items:center;justify-content:space-between;gap:10px;font-weight:750;font-size:14px;color:rgba(31,41,55,.90)}
.fr-section summary::-webkit-details-marker{display:none}
.fr-section summary .fr-chev{transition:transform 160ms ease;color:rgba(31,41,55,.40)}
.fr-section[open] summary .fr-chev{transform:rotate(180deg)}
.fr-section__body{margin-top:8px;padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06)}
.fr-section__hint{font-size:12px;color:rgba(31,41,55,.45);font-weight:400}
.fr-empty{text-align:center;padding:20px;color:rgba(31,41,55,.45);font-size:13px}
</style>

<!-- Cabeçalho -->
<div class="fr-head">
    <div>
        <div class="fr-head__title">Relatório Financeiro</div>
        <div class="fr-head__period">
            <?= htmlspecialchars(date('d/m/Y', strtotime($from)), ENT_QUOTES, 'UTF-8') ?> até <?= htmlspecialchars(date('d/m/Y', strtotime($to)), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
    <?php if ($can('finance.reports.read')): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/finance/reports/export.csv?<?= http_build_query($exportQuery) ?>">📊 Exportar planilha</a>
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/finance/reports/export.pdf?<?= http_build_query($exportQuery) ?>">📄 Exportar PDF</a>
    </div>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="fr-filters">
    <form method="get" action="/finance/reports" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
        <div class="lc-field">
            <label class="lc-label">De</label>
            <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" />
        </div>
        <div class="lc-field">
            <label class="lc-label">Até</label>
            <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" />
        </div>
        <?php if (!$is_professional): ?>
        <div class="lc-field">
            <label class="lc-label">Profissional</label>
            <select class="lc-select" name="professional_id">
                <option value="0">Todos</option>
                <?php foreach ($professionals as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$professional_id) ? 'selected' : '' ?>><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
            <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
        <?php endif; ?>
        <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Gerar relatório</button>
    </form>
</div>

<!-- KPIs principais -->
<div class="fr-kpis">
    <div class="fr-kpi">
        <div class="fr-kpi__label">Receita (vendas pagas)</div>
        <div class="fr-kpi__value" style="color:#16a34a;">R$ <?= number_format((float)$kpi_revenue_total, 2, ',', '.') ?></div>
        <div class="fr-kpi__hint">Total de vendas com pagamento confirmado</div>
    </div>
    <div class="fr-kpi">
        <div class="fr-kpi__label">Entradas no caixa</div>
        <div class="fr-kpi__value" style="color:#16a34a;">R$ <?= number_format((float)$kpi_in_total, 2, ',', '.') ?></div>
        <div class="fr-kpi__hint">Lançamentos de entrada no fluxo de caixa</div>
    </div>
    <div class="fr-kpi">
        <div class="fr-kpi__label">Saídas do caixa</div>
        <div class="fr-kpi__value" style="color:#b91c1c;">R$ <?= number_format((float)$kpi_out_total, 2, ',', '.') ?></div>
        <div class="fr-kpi__hint">Lançamentos de saída no fluxo de caixa</div>
    </div>
    <div class="fr-kpi">
        <div class="fr-kpi__label">Saldo do período</div>
        <div class="fr-kpi__value" style="color:<?= $netColor ?>;">R$ <?= number_format((float)$kpi_net_total, 2, ',', '.') ?></div>
        <div class="fr-kpi__hint">Entradas menos saídas</div>
    </div>
</div>

<!-- KPIs secundários -->
<div class="fr-kpis" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));">
    <div class="fr-kpi">
        <div class="fr-kpi__label">Ticket médio</div>
        <div class="fr-kpi__value">R$ <?= number_format((float)$ticket_medio, 2, ',', '.') ?></div>
        <div class="fr-kpi__hint">Valor médio por venda paga</div>
    </div>
    <div class="fr-kpi">
        <div class="fr-kpi__label">Agendamentos</div>
        <div class="fr-kpi__value"><?= (int)$appointments ?></div>
        <div class="fr-kpi__hint">Total no período</div>
    </div>
    <div class="fr-kpi">
        <div class="fr-kpi__label">Vendas pagas</div>
        <div class="fr-kpi__value"><?= (int)$paid_sales ?></div>
    </div>
    <div class="fr-kpi">
        <div class="fr-kpi__label">Conversão</div>
        <div class="fr-kpi__value"><?= number_format(((float)$conversion_rate) * 100.0, 1, ',', '.') ?>%</div>
        <div class="fr-kpi__hint">Agendamento → venda paga</div>
    </div>
    <?php if ((float)$recurring_revenue > 0): ?>
    <div class="fr-kpi">
        <div class="fr-kpi__label">Receita recorrente</div>
        <div class="fr-kpi__value">R$ <?= number_format((float)$recurring_revenue, 2, ',', '.') ?></div>
        <div class="fr-kpi__hint">Assinaturas</div>
    </div>
    <?php endif; ?>
</div>

<!-- Receita por profissional -->
<details class="fr-section" open>
    <summary>
        Receita por profissional
        <span class="fr-section__hint"><?= count($by_professional) ?> profissional(is)</span>
        <svg class="fr-chev" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <div class="fr-section__body">
        <?php if ($by_professional === []): ?>
            <div class="fr-empty">Sem dados no período.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead><tr><th>Profissional</th><th>Receita</th><th>Custo (materiais)</th><th>Margem</th></tr></thead>
                    <tbody>
                    <?php foreach ($by_professional as $r): ?>
                        <?php
                        $pid = $r['professional_id'] === null ? 0 : (int)$r['professional_id'];
                        $pname = $pid > 0 && isset($profMap[$pid]) ? (string)$profMap[$pid]['name'] : '—';
                        $cost = (float)($r['cost'] ?? 0);
                        $margin = (float)($r['margin'] ?? ((float)$r['revenue'] - $cost));
                        $marginColor = $margin >= 0 ? '#16a34a' : '#b91c1c';
                        ?>
                        <tr>
                            <td style="font-weight:700;"><?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?></td>
                            <td>R$ <?= number_format((float)$r['revenue'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($cost, 2, ',', '.') ?></td>
                            <td style="font-weight:700;color:<?= $marginColor ?>;">R$ <?= number_format($margin, 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</details>

<!-- Receita por serviço -->
<details class="fr-section">
    <summary>
        Receita por serviço
        <span class="fr-section__hint"><?= count($by_service) ?> serviço(s)</span>
        <svg class="fr-chev" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <div class="fr-section__body">
        <?php if ($by_service === []): ?>
            <div class="fr-empty">Sem dados no período.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead><tr><th>Serviço</th><th>Receita</th><th>Custo (materiais)</th><th>Margem</th></tr></thead>
                    <tbody>
                    <?php foreach ($by_service as $r): ?>
                        <?php
                        $cost = (float)($r['cost'] ?? 0);
                        $margin = (float)($r['margin'] ?? ((float)$r['revenue'] - $cost));
                        $marginColor = $margin >= 0 ? '#16a34a' : '#b91c1c';
                        ?>
                        <tr>
                            <td style="font-weight:700;">Serviço #<?= (int)$r['service_id'] ?></td>
                            <td>R$ <?= number_format((float)$r['revenue'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($cost, 2, ',', '.') ?></td>
                            <td style="font-weight:700;color:<?= $marginColor ?>;">R$ <?= number_format($margin, 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</details>

<!-- Vendas recentes -->
<details class="fr-section">
    <summary>
        Últimas vendas pagas
        <span class="fr-section__hint"><?= count($recent_sales ?? []) ?> registro(s)</span>
        <svg class="fr-chev" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <div class="fr-section__body">
        <?php if (($recent_sales ?? []) === []): ?>
            <div class="fr-empty">Sem vendas no período.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead><tr><th>#</th><th>Paciente</th><th>Valor</th><th>Data</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($recent_sales as $rs): ?>
                        <?php $sid = (int)($rs['id'] ?? 0); ?>
                        <tr>
                            <td>#<?= $sid ?></td>
                            <td style="font-weight:700;"><?= htmlspecialchars((string)($rs['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>R$ <?= number_format((float)($rs['total_liquido'] ?? 0), 2, ',', '.') ?></td>
                            <td style="font-size:12px;color:rgba(31,41,55,.55);"><?= htmlspecialchars((string)($rs['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="text-align:right;"><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/finance/sales/view?id=<?= $sid ?>">Abrir</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</details>

<!-- Lançamentos recentes -->
<details class="fr-section">
    <summary>
        Últimos lançamentos do caixa
        <span class="fr-section__hint"><?= count($recent_entries ?? []) ?> registro(s)</span>
        <svg class="fr-chev" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <div class="fr-section__body">
        <?php if (($recent_entries ?? []) === []): ?>
            <div class="fr-empty">Sem lançamentos no período.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead><tr><th>Data</th><th>Tipo</th><th>Valor</th><th>Descrição</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent_entries as $re): ?>
                        <?php
                        $kind = (string)($re['kind'] ?? '');
                        $kindLbl = $kind === 'out' ? 'Saída' : 'Entrada';
                        $kindClr = $kind === 'out' ? '#b91c1c' : '#16a34a';
                        ?>
                        <tr>
                            <td style="font-size:12px;white-space:nowrap;"><?= htmlspecialchars((string)($re['occurred_on'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="font-weight:700;color:<?= $kindClr ?>;"><?= $kindLbl ?></td>
                            <td>R$ <?= number_format((float)($re['amount'] ?? 0), 2, ',', '.') ?></td>
                            <td style="font-size:13px;"><?= htmlspecialchars((string)($re['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:10px;">
                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/finance/cashflow?from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>">Ver fluxo de caixa completo</a>
            </div>
        <?php endif; ?>
    </div>
</details>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
