<?php
/** @var string $from */
/** @var string $to */
/** @var array<string,mixed> $summary */
/** @var list<array<string,mixed>> $by_material */
/** @var list<array<string,mixed>> $losses_by_reason */
/** @var list<array<string,mixed>> $losses_by_material */
/** @var list<array<string,mixed>> $by_service */
/** @var list<array<string,mixed>> $by_professional */
/** @var string|null $tab */
/** @var list<array<string,mixed>> $movements */
/** @var list<array<string,mixed>> $materials */
/** @var int $page */
/** @var int $per_page */
/** @var bool $has_next */
/** @var int $days */
/** @var list<array<string,mixed>> $low_stock */
/** @var list<array<string,mixed>> $out_of_stock */
/** @var list<array<string,mixed>> $expiring_soon */
/** @var list<array<string,mixed>> $expired */

$title = 'Relatório de Estoque';
$csrf = $_SESSION['_csrf'] ?? '';

$tab = isset($tab) ? (string)$tab : 'summary';
$allowedTabs = ['summary', 'movements', 'alerts'];
if (!in_array($tab, $allowedTabs, true)) $tab = 'summary';

$days = isset($days) ? (int)$days : 30;
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 100;
$hasNext = isset($has_next) ? (bool)$has_next : false;

$tabBase = '/stock/reports';
$tabHref = function (string $t) use ($tabBase, $from, $to, $perPage): string {
    return $tabBase . '?' . http_build_query(['tab' => $t, 'from' => (string)$from, 'to' => (string)$to, 'per_page' => (int)$perPage, 'page' => 1]);
};

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

$exportQuery = ['from' => (string)$from, 'to' => (string)$to];

ob_start();
?>

<style>
.sr-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px}
.sr-head__title{font-weight:850;font-size:20px;color:rgba(31,41,55,.96)}
.sr-head__period{font-size:13px;color:rgba(31,41,55,.50)}
.sr-filters{padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:18px}
.sr-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px}
.sr-tab{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:12px;font-weight:700;font-size:13px;text-decoration:none;border:1px solid rgba(17,24,39,.10);color:rgba(31,41,55,.72);background:var(--lc-surface-3);transition:all 160ms ease}
.sr-tab:hover{border-color:rgba(129,89,1,.22);color:rgba(129,89,1,1)}
.sr-tab.active{background:rgba(238,184,16,.14);border-color:rgba(129,89,1,.24);color:rgba(31,41,55,.96)}
.sr-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:18px}
.sr-kpi{padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06)}
.sr-kpi__label{font-size:12px;color:rgba(31,41,55,.50);font-weight:600;margin-bottom:4px}
.sr-kpi__value{font-size:22px;font-weight:900;color:rgba(31,41,55,.96)}
.sr-kpi__hint{font-size:11px;color:rgba(31,41,55,.40);margin-top:2px}
.sr-section{margin-bottom:16px}
.sr-section summary{list-style:none;cursor:pointer;padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);display:flex;align-items:center;justify-content:space-between;gap:10px;font-weight:750;font-size:14px;color:rgba(31,41,55,.90)}
.sr-section summary::-webkit-details-marker{display:none}
.sr-section summary .sr-chev{transition:transform 160ms ease;color:rgba(31,41,55,.40)}
.sr-section[open] summary .sr-chev{transform:rotate(180deg)}
.sr-section__body{margin-top:8px;padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06)}
.sr-section__hint{font-size:12px;color:rgba(31,41,55,.45);font-weight:400}
.sr-empty{text-align:center;padding:20px;color:rgba(31,41,55,.45);font-size:13px}
.sr-alert-card{padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.08);margin-bottom:10px}
.sr-alert-card--danger{border-color:rgba(185,28,28,.22);background:rgba(185,28,28,.04)}
.sr-alert-card--warn{border-color:rgba(238,184,16,.30);background:rgba(253,229,159,.12)}
.sr-alert-card__title{font-weight:750;font-size:13px;margin-bottom:6px}
.sr-alert-card__count{font-size:12px;color:rgba(31,41,55,.50)}
</style>

<!-- Cabeçalho -->
<div class="sr-head">
    <div>
        <div class="sr-head__title">Relatório de Estoque</div>
        <div class="sr-head__period"><?= htmlspecialchars(date('d/m/Y', strtotime($from)), ENT_QUOTES, 'UTF-8') ?> até <?= htmlspecialchars(date('d/m/Y', strtotime($to)), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php if ($can('stock.reports.read')): ?>
            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/stock/reports/export.csv?<?= http_build_query($exportQuery) ?>">📊 Planilha</a>
            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/stock/reports/export.pdf?<?= http_build_query($exportQuery) ?>">📄 PDF</a>
        <?php endif; ?>
    </div>
</div>

<!-- Filtros -->
<div class="sr-filters">
    <form method="get" action="/stock/reports" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="per_page" value="<?= (int)$perPage ?>" />
        <input type="hidden" name="page" value="1" />
        <div class="lc-field"><label class="lc-label">De</label><input class="lc-input" type="date" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" /></div>
        <div class="lc-field"><label class="lc-label">Até</label><input class="lc-input" type="date" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" /></div>
        <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Atualizar</button>
    </form>
</div>

<!-- Abas -->
<div class="sr-tabs">
    <a class="sr-tab <?= $tab === 'summary' ? 'active' : '' ?>" href="<?= htmlspecialchars($tabHref('summary'), ENT_QUOTES, 'UTF-8') ?>">Resumo</a>
    <?php if ($can('stock.movements.read')): ?>
        <a class="sr-tab <?= $tab === 'movements' ? 'active' : '' ?>" href="<?= htmlspecialchars($tabHref('movements'), ENT_QUOTES, 'UTF-8') ?>">Movimentações</a>
    <?php endif; ?>
    <?php if ($can('stock.alerts.read')): ?>
        <a class="sr-tab <?= $tab === 'alerts' ? 'active' : '' ?>" href="<?= htmlspecialchars($tabHref('alerts'), ENT_QUOTES, 'UTF-8') ?>">Alertas</a>
    <?php endif; ?>
</div>

<?php if ($tab === 'alerts'): ?>
<?php
    $low_stock = isset($low_stock) && is_array($low_stock) ? $low_stock : [];
    $out_of_stock = isset($out_of_stock) && is_array($out_of_stock) ? $out_of_stock : [];
    $expiring_soon = isset($expiring_soon) && is_array($expiring_soon) ? $expiring_soon : [];
    $expired = isset($expired) && is_array($expired) ? $expired : [];
    $days = max(1, min(365, (int)$days));
    $totalAlerts = count($out_of_stock) + count($low_stock) + count($expired) + count($expiring_soon);
?>

<!-- Filtro de dias -->
<div class="sr-filters" style="margin-bottom:16px;">
    <form method="get" action="/stock/reports" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
        <input type="hidden" name="tab" value="alerts" />
        <input type="hidden" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="per_page" value="<?= (int)$perPage ?>" />
        <input type="hidden" name="page" value="1" />
        <div class="lc-field">
            <label class="lc-label">Dias para validade próxima</label>
            <input class="lc-input" type="number" name="days" min="1" max="365" value="<?= (int)$days ?>" style="max-width:120px;" />
        </div>
        <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Atualizar</button>
    </form>
</div>

<?php if ($totalAlerts === 0): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
        <div style="font-size:32px;margin-bottom:8px;">✅</div>
        <div style="font-size:14px;">Tudo em ordem. Nenhum alerta no momento.</div>
    </div>
<?php else: ?>

<?php if ($out_of_stock !== []): ?>
<div class="sr-alert-card sr-alert-card--danger">
    <div class="sr-alert-card__title" style="color:#b91c1c;">🚨 Estoque zerado (<?= count($out_of_stock) ?>)</div>
    <div class="lc-table-wrap">
        <table class="lc-table"><thead><tr><th>Material</th><th>Estoque</th><th>Mínimo</th><th>Sugestão compra</th></tr></thead><tbody>
        <?php foreach ($out_of_stock as $it): ?>
            <tr>
                <td style="font-weight:700;"><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="color:#b91c1c;font-weight:700;"><?= number_format((float)$it['stock_current'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= number_format((float)$it['stock_minimum'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="font-weight:700;"><?= number_format((float)$it['suggested_buy'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
</div>
<?php endif; ?>

<?php if ($low_stock !== []): ?>
<div class="sr-alert-card sr-alert-card--warn">
    <div class="sr-alert-card__title" style="color:#b5841e;">⚠️ Estoque baixo (<?= count($low_stock) ?>)</div>
    <div class="lc-table-wrap">
        <table class="lc-table"><thead><tr><th>Material</th><th>Estoque</th><th>Mínimo</th><th>Sugestão compra</th></tr></thead><tbody>
        <?php foreach ($low_stock as $it): ?>
            <tr>
                <td style="font-weight:700;"><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="color:#b5841e;font-weight:700;"><?= number_format((float)$it['stock_current'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= number_format((float)$it['stock_minimum'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="font-weight:700;"><?= number_format((float)$it['suggested_buy'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
</div>
<?php endif; ?>

<?php if ($expired !== []): ?>
<div class="sr-alert-card sr-alert-card--danger">
    <div class="sr-alert-card__title" style="color:#b91c1c;">🗓️ Validade vencida (<?= count($expired) ?>)</div>
    <div class="lc-table-wrap">
        <table class="lc-table"><thead><tr><th>Material</th><th>Validade</th><th>Estoque</th></tr></thead><tbody>
        <?php foreach ($expired as $it): ?>
            <tr>
                <td style="font-weight:700;"><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="color:#b91c1c;"><?= htmlspecialchars((string)$it['validity_date'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= number_format((float)$it['stock_current'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
</div>
<?php endif; ?>

<?php if ($expiring_soon !== []): ?>
<div class="sr-alert-card sr-alert-card--warn">
    <div class="sr-alert-card__title" style="color:#b5841e;">⏳ Validade próxima — até <?= (int)$days ?> dias (<?= count($expiring_soon) ?>)</div>
    <div class="lc-table-wrap">
        <table class="lc-table"><thead><tr><th>Material</th><th>Validade</th><th>Estoque</th></tr></thead><tbody>
        <?php foreach ($expiring_soon as $it): ?>
            <tr>
                <td style="font-weight:700;"><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="color:#b5841e;"><?= htmlspecialchars((string)$it['validity_date'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= number_format((float)$it['stock_current'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
</div>
<?php endif; ?>

<?php endif; /* totalAlerts */ ?>

<?php elseif ($tab === 'movements'): ?>
<?php
    $movements = isset($movements) && is_array($movements) ? $movements : [];
    $materials = isset($materials) && is_array($materials) ? $materials : [];
    $matMap = [];
    foreach ($materials as $m) $matMap[(int)$m['id']] = $m;
    $pagerBaseQuery = ['tab' => 'movements', 'from' => (string)$from, 'to' => (string)$to, 'per_page' => (int)$perPage];
    $pagerHref = function (int $targetPage) use ($pagerBaseQuery): string {
        return '/stock/reports?' . http_build_query($pagerBaseQuery + ['page' => $targetPage]);
    };
    $typeLabelMap = ['entry'=>'Entrada','exit'=>'Saída','adjustment'=>'Ajuste','loss'=>'Perda','expiration'=>'Vencimento'];
    $typeColorMap = ['entry'=>'#16a34a','exit'=>'#b91c1c','adjustment'=>'#6b7280','loss'=>'#b91c1c','expiration'=>'#b5841e'];
    $reasonLabelMap = ['expiration'=>'Vencimento','breakage'=>'Quebra','contamination'=>'Contaminação','operational_error'=>'Erro operacional','internal_use'=>'Uso interno'];
?>

<?php if ($can('stock.movements.create')): ?>
<details class="sr-section" style="margin-bottom:16px;">
    <summary>
        + Nova movimentação
        <svg class="sr-chev" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <div class="sr-section__body">
        <form method="post" action="/stock/movements/create" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="return_to" value="/stock/reports?<?= htmlspecialchars(http_build_query($pagerBaseQuery + ['page' => (int)$page]), ENT_QUOTES, 'UTF-8') ?>" />
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px;align-items:end;">
                <div class="lc-field">
                    <label class="lc-label">Material</label>
                    <select class="lc-select" name="material_id">
                        <?php foreach ($materials as $m): ?>
                            <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars((string)$m['name'], ENT_QUOTES, 'UTF-8') ?> (<?= number_format((float)$m['stock_current'], 3, ',', '.') ?> <?= htmlspecialchars((string)$m['unit'], ENT_QUOTES, 'UTF-8') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Tipo</label>
                    <select class="lc-select" name="type">
                        <option value="entry">Entrada</option>
                        <option value="exit">Saída</option>
                        <option value="adjustment">Ajuste</option>
                        <option value="loss">Perda</option>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Quantidade</label>
                    <input class="lc-input" type="text" name="quantity" required />
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:12px;margin-top:4px;align-items:end;">
                <div class="lc-field">
                    <label class="lc-label">Motivo perda (se aplicável)</label>
                    <select class="lc-select" name="loss_reason">
                        <option value="">—</option>
                        <option value="expiration">Vencimento</option>
                        <option value="breakage">Quebra</option>
                        <option value="contamination">Contaminação</option>
                        <option value="operational_error">Erro operacional</option>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Observações</label>
                    <input class="lc-input" type="text" name="notes" placeholder="Opcional..." />
                </div>
            </div>
            <div style="margin-top:12px;"><button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Registrar</button></div>
        </form>
    </div>
</details>
<?php endif; ?>

<?php if ($movements === []): ?>
    <div class="sr-empty" style="padding:40px 20px;">Nenhuma movimentação no período.</div>
<?php else: ?>
<div style="border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);overflow:hidden;">
    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead><tr><th>Data</th><th>Material</th><th>Tipo</th><th>Qtd</th><th>Custo</th><th>Motivo</th><th>Obs</th></tr></thead>
            <tbody>
            <?php foreach ($movements as $mv): ?>
                <?php
                $mid = (int)$mv['material_id'];
                $mname = isset($matMap[$mid]) ? (string)$matMap[$mid]['name'] : '#' . $mid;
                $munit = isset($matMap[$mid]) ? (string)$matMap[$mid]['unit'] : '';
                $type = (string)($mv['type'] ?? '');
                $tLbl = $typeLabelMap[$type] ?? $type;
                $tClr = $typeColorMap[$type] ?? '#6b7280';
                $reason = (string)($mv['loss_reason'] ?? '');
                $rLbl = $reason !== '' ? ($reasonLabelMap[$reason] ?? $reason) : '';
                ?>
                <tr>
                    <td style="font-size:12px;white-space:nowrap;"><?= htmlspecialchars((string)$mv['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight:700;"><?= htmlspecialchars($mname, ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight:700;color:<?= $tClr ?>;"><?= htmlspecialchars($tLbl, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= number_format((float)$mv['quantity'], 3, ',', '.') ?> <?= htmlspecialchars($munit, ENT_QUOTES, 'UTF-8') ?></td>
                    <td>R$ <?= number_format((float)$mv['total_cost_snapshot'], 2, ',', '.') ?></td>
                    <td style="font-size:12px;"><?= htmlspecialchars($rLbl, ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size:12px;color:rgba(31,41,55,.55);"><?= htmlspecialchars((string)($mv['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;flex-wrap:wrap;gap:10px;">
    <span style="font-size:12px;color:rgba(31,41,55,.45);">Página <?= (int)$page ?></span>
    <div style="display:flex;gap:8px;">
        <?php if ($page > 1): ?><a class="lc-btn lc-btn--secondary lc-btn--sm" href="<?= htmlspecialchars($pagerHref($page - 1), ENT_QUOTES, 'UTF-8') ?>">← Anterior</a><?php endif; ?>
        <?php if ($hasNext): ?><a class="lc-btn lc-btn--secondary lc-btn--sm" href="<?= htmlspecialchars($pagerHref($page + 1), ENT_QUOTES, 'UTF-8') ?>">Próxima →</a><?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php else: /* summary tab */ ?>

<!-- KPIs -->
<div class="sr-kpis">
    <div class="sr-kpi">
        <div class="sr-kpi__label">Custo total saídas</div>
        <div class="sr-kpi__value" style="color:#b91c1c;">R$ <?= number_format((float)($summary['total_exit_cost'] ?? 0), 2, ',', '.') ?></div>
        <div class="sr-kpi__hint">Materiais consumidos no período</div>
    </div>
    <div class="sr-kpi">
        <div class="sr-kpi__label">Custo de perdas</div>
        <div class="sr-kpi__value" style="color:#b91c1c;">R$ <?= number_format((float)($summary['total_loss_cost'] ?? 0), 2, ',', '.') ?></div>
        <div class="sr-kpi__hint">Quebras, contaminação, etc.</div>
    </div>
    <div class="sr-kpi">
        <div class="sr-kpi__label">Custo vencimento</div>
        <div class="sr-kpi__value" style="color:#b5841e;">R$ <?= number_format((float)($summary['total_expiration_cost'] ?? 0), 2, ',', '.') ?></div>
        <div class="sr-kpi__hint">Materiais que venceram</div>
    </div>
    <div class="sr-kpi">
        <div class="sr-kpi__label">Custo por sessões</div>
        <div class="sr-kpi__value">R$ <?= number_format((float)($summary['total_session_cost'] ?? 0), 2, ',', '.') ?></div>
        <div class="sr-kpi__hint">Consumo em atendimentos</div>
    </div>
</div>

<!-- Consumo por material -->
<details class="sr-section" open>
    <summary>
        Consumo por material
        <span class="sr-section__hint"><?= count($by_material) ?> material(is)</span>
        <svg class="sr-chev" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <div class="sr-section__body">
        <?php if ($by_material === []): ?>
            <div class="sr-empty">Sem dados no período.</div>
        <?php else: ?>
            <div class="lc-table-wrap"><table class="lc-table">
                <thead><tr><th>Material</th><th>Qtd saída</th><th>Custo</th></tr></thead>
                <tbody>
                <?php foreach ($by_material as $it): ?>
                    <tr>
                        <td style="font-weight:700;"><?= htmlspecialchars((string)$it['material_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$it['qty'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>R$ <?= number_format((float)$it['cost'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
</details>

<!-- Perdas -->
<?php $hasLosses = !empty($losses_by_reason) || !empty($losses_by_material); ?>
<details class="sr-section" <?= $hasLosses ? '' : '' ?>>
    <summary>
        Perdas
        <span class="sr-section__hint">por motivo e por material</span>
        <svg class="sr-chev" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <div class="sr-section__body">
        <?php if (!$hasLosses): ?>
            <div class="sr-empty">Sem perdas no período. 👍</div>
        <?php else: ?>
            <?php if (!empty($losses_by_reason)): ?>
            <div style="font-weight:700;font-size:13px;margin-bottom:8px;">Por motivo</div>
            <div class="lc-table-wrap" style="margin-bottom:16px;"><table class="lc-table">
                <thead><tr><th>Motivo</th><th>Qtd</th><th>Custo</th></tr></thead>
                <tbody>
                <?php
                $reasonLabelMap = [''=>'Não informado','expiration'=>'Vencimento','breakage'=>'Quebra','contamination'=>'Contaminação','operational_error'=>'Erro operacional','internal_use'=>'Uso interno'];
                foreach ($losses_by_reason as $it):
                    $reason = (string)($it['loss_reason'] ?? '');
                ?>
                    <tr>
                        <td style="font-weight:700;"><?= htmlspecialchars($reasonLabelMap[$reason] ?? $reason, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)($it['qty'] ?? 0), 3, ',', '.') ?></td>
                        <td>R$ <?= number_format((float)($it['cost'] ?? 0), 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>

            <?php if (!empty($losses_by_material)): ?>
            <div style="font-weight:700;font-size:13px;margin-bottom:8px;">Por material</div>
            <div class="lc-table-wrap"><table class="lc-table">
                <thead><tr><th>Material</th><th>Qtd</th><th>Custo</th></tr></thead>
                <tbody>
                <?php foreach ($losses_by_material as $it): ?>
                    <tr>
                        <td style="font-weight:700;"><?= htmlspecialchars((string)($it['material_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)($it['qty'] ?? 0), 3, ',', '.') ?> <?= htmlspecialchars((string)($it['unit'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>R$ <?= number_format((float)($it['cost'] ?? 0), 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</details>

<!-- Consumo por serviço -->
<details class="sr-section">
    <summary>
        Consumo por serviço
        <span class="sr-section__hint"><?= count($by_service) ?> serviço(s)</span>
        <svg class="sr-chev" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <div class="sr-section__body">
        <?php if ($by_service === []): ?>
            <div class="sr-empty">Sem dados no período.</div>
        <?php else: ?>
            <div class="lc-table-wrap"><table class="lc-table">
                <thead><tr><th>Serviço</th><th>Sessões</th><th>Custo</th></tr></thead>
                <tbody>
                <?php foreach ($by_service as $it): ?>
                    <tr>
                        <td style="font-weight:700;"><?= htmlspecialchars((string)$it['service_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$it['sessions'] ?></td>
                        <td>R$ <?= number_format((float)$it['cost'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
</details>

<!-- Consumo por profissional -->
<details class="sr-section">
    <summary>
        Consumo por profissional
        <span class="sr-section__hint"><?= count($by_professional) ?> profissional(is)</span>
        <svg class="sr-chev" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <div class="sr-section__body">
        <?php if ($by_professional === []): ?>
            <div class="sr-empty">Sem dados no período.</div>
        <?php else: ?>
            <div class="lc-table-wrap"><table class="lc-table">
                <thead><tr><th>Profissional</th><th>Sessões</th><th>Custo</th></tr></thead>
                <tbody>
                <?php foreach ($by_professional as $it): ?>
                    <tr>
                        <td style="font-weight:700;"><?= htmlspecialchars((string)$it['professional_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$it['sessions'] ?></td>
                        <td>R$ <?= number_format((float)$it['cost'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
</details>

<?php endif; /* tab */ ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
