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

$title = 'Financeiro - Relatórios';

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
        return true;
    }

    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) {
        return false;
    }

    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) {
            return false;
        }
        return in_array($permissionCode, $permissions['allow'], true);
    }

    return in_array($permissionCode, $permissions, true);
};

$profMap = [];
foreach ($professionals as $p) {
    $profMap[(int)$p['id']] = $p;
}

ob_start();
?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Filtros</div>
    <div class="lc-card__body">
        <form method="get" action="/finance/reports" class="lc-form lc-flex lc-gap-md lc-flex--wrap" style="align-items:end;">
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
                            <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$professional_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
            <?php endif; ?>

            <div class="lc-flex lc-gap-sm lc-flex--wrap">
                <button class="lc-btn" type="submit">Gerar</button>

                <?php
                $exportQuery = [
                    'from' => (string)$from,
                    'to' => (string)$to,
                    'professional_id' => (int)$professional_id,
                ];
                ?>
                <?php if ($can('finance.reports.read')): ?>
                    <a class="lc-btn lc-btn--secondary" href="/finance/reports/export.csv?<?= http_build_query($exportQuery) ?>">Exportar planilha</a>
                    <a class="lc-btn lc-btn--secondary" href="/finance/reports/export.pdf?<?= http_build_query($exportQuery) ?>">Exportar PDF</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(4, minmax(180px, 1fr)); margin-bottom: 16px;">
    <div class="lc-card">
        <div class="lc-card__header">Receita (vendas pagas)</div>
        <div class="lc-card__body" style="font-size: 18px; font-weight: 700;">R$ <?= number_format((float)$kpi_revenue_total, 2, ',', '.') ?></div>
        <div class="lc-card__body lc-muted" style="padding-top:0; font-size:12px;">Período selecionado</div>
    </div>

    <div class="lc-card">
        <div class="lc-card__header">Entradas</div>
        <div class="lc-card__body" style="font-size: 18px; font-weight: 700;">R$ <?= number_format((float)$kpi_in_total, 2, ',', '.') ?></div>
        <div class="lc-card__body lc-muted" style="padding-top:0; font-size:12px;">Lançamentos financeiros</div>
    </div>

    <div class="lc-card">
        <div class="lc-card__header">Saídas</div>
        <div class="lc-card__body" style="font-size: 18px; font-weight: 700;">R$ <?= number_format((float)$kpi_out_total, 2, ',', '.') ?></div>
        <div class="lc-card__body lc-muted" style="padding-top:0; font-size:12px;">Lançamentos financeiros</div>
    </div>

    <div class="lc-card">
        <div class="lc-card__header">Saldo (Entradas - Saídas)</div>
        <div class="lc-card__body" style="font-size: 18px; font-weight: 700;">R$ <?= number_format((float)$kpi_net_total, 2, ',', '.') ?></div>
        <div class="lc-card__body lc-muted" style="padding-top:0; font-size:12px;">Não inclui custos por procedimento</div>
    </div>
</div>

<div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(4, minmax(180px, 1fr)); margin-bottom: 16px;">
    <div class="lc-card">
        <div class="lc-card__header">Ticket médio</div>
        <div class="lc-card__body" style="font-size: 18px; font-weight: 700;">R$ <?= number_format((float)$ticket_medio, 2, ',', '.') ?></div>
        <div class="lc-card__body lc-muted" style="padding-top:0; font-size:12px;">Somente vendas pagas</div>
    </div>

    <div class="lc-card">
        <div class="lc-card__header">Agendamentos</div>
        <div class="lc-card__body" style="font-size: 18px; font-weight: 700;"><?= (int)$appointments ?></div>
        <div class="lc-card__body lc-muted" style="padding-top:0; font-size:12px;">Período selecionado</div>
    </div>

    <div class="lc-card">
        <div class="lc-card__header">Vendas pagas</div>
        <div class="lc-card__body" style="font-size: 18px; font-weight: 700;"><?= (int)$paid_sales ?></div>
        <div class="lc-card__body lc-muted" style="padding-top:0; font-size:12px;">Período selecionado</div>
    </div>

    <div class="lc-card">
        <div class="lc-card__header">Conversão</div>
        <div class="lc-card__body" style="font-size: 18px; font-weight: 700;"><?= number_format(((float)$conversion_rate) * 100.0, 2, ',', '.') ?>%</div>
        <div class="lc-card__body lc-muted" style="padding-top:0; font-size:12px;">Agenda → venda paga</div>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Receita recorrente (assinaturas)</div>
    <div class="lc-card__body" style="font-size: 18px; font-weight: 700;">R$ <?= number_format((float)$recurring_revenue, 2, ',', '.') ?></div>
</div>

<div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(2, minmax(360px, 1fr)); margin-bottom: 16px;">
    <div class="lc-card">
        <div class="lc-card__header">Histórico recente - Vendas pagas</div>
        <div class="lc-card__body">
            <?php if (($recent_sales ?? []) === []): ?>
                <div class="lc-muted">Sem dados no período.</div>
            <?php else: ?>
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Paciente</th>
                        <th>Valor</th>
                        <th>Data</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent_sales as $rs): ?>
                        <?php $sid = (int)($rs['id'] ?? 0); ?>
                        <tr>
                            <td>#<?= (int)$sid ?></td>
                            <td><?= htmlspecialchars((string)($rs['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>R$ <?= number_format((float)($rs['total_liquido'] ?? 0), 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars((string)($rs['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <a class="lc-btn lc-btn--secondary" href="/finance/sales/view?id=<?= (int)$sid ?>">Abrir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="lc-card">
        <div class="lc-card__header">Histórico recente - Lançamentos</div>
        <div class="lc-card__body">
            <?php if (($recent_entries ?? []) === []): ?>
                <div class="lc-muted">Sem dados no período.</div>
            <?php else: ?>
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Descrição</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent_entries as $re): ?>
                        <?php
                            $kind = (string)($re['kind'] ?? '');
                            $kindLbl = $kind === 'out' ? 'Saída' : 'Entrada';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($re['occurred_on'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($kindLbl, ENT_QUOTES, 'UTF-8') ?></td>
                            <td>R$ <?= number_format((float)($re['amount'] ?? 0), 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars((string)($re['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 10px;">
                    <a class="lc-btn lc-btn--secondary" href="/finance/cashflow?from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>">Ver fluxo de caixa</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Receita por profissional (pagamentos pagos)</div>
    <div class="lc-card__body">
        <?php if ($by_professional === []): ?>
            <div class="lc-muted">Sem dados.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Profissional</th>
                    <th>Receita</th>
                    <th>Custo (materiais)</th>
                    <th>Margem</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($by_professional as $r): ?>
                    <?php
                        $pid = $r['professional_id'] === null ? 0 : (int)$r['professional_id'];
                        $name = $pid > 0 && isset($profMap[$pid]) ? (string)$profMap[$pid]['name'] : '-';
                        $cost = (float)($r['cost'] ?? 0);
                        $margin = (float)($r['margin'] ?? ((float)$r['revenue'] - $cost));
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>R$ <?= number_format((float)$r['revenue'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($cost, 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($margin, 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Receita por serviço (procedimentos)</div>
    <div class="lc-card__body">
        <?php if ($by_service === []): ?>
            <div class="lc-muted">Sem dados.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Service ID</th>
                    <th>Receita</th>
                    <th>Custo (materiais)</th>
                    <th>Margem</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($by_service as $r): ?>
                    <?php
                        $cost = (float)($r['cost'] ?? 0);
                        $margin = (float)($r['margin'] ?? ((float)$r['revenue'] - $cost));
                    ?>
                    <tr>
                        <td>#<?= (int)$r['service_id'] ?></td>
                        <td>R$ <?= number_format((float)$r['revenue'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($cost, 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($margin, 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
