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

$title = 'Relatórios de Estoque e Custos';
$csrf = $_SESSION['_csrf'] ?? '';

$tab = isset($tab) ? (string)$tab : 'summary';
$allowedTabs = ['summary', 'movements', 'alerts'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'summary';
}

$days = isset($days) ? (int)$days : 30;

$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 100;
$hasNext = isset($has_next) ? (bool)$has_next : false;

$tabBase = '/stock/reports';
$tabHref = function (string $t) use ($tabBase, $from, $to, $perPage): string {
    return $tabBase . '?' . http_build_query([
        'tab' => $t,
        'from' => (string)$from,
        'to' => (string)$to,
        'per_page' => (int)$perPage,
        'page' => 1,
    ]);
};

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
ob_start();
?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Filtros</div>
    <div class="lc-card__body">
        <form method="get" action="/stock/reports" class="lc-form lc-flex lc-gap-md lc-flex--wrap" style="align-items:end;">
            <input type="hidden" name="tab" value="<?= htmlspecialchars((string)$tab, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="per_page" value="<?= (int)$perPage ?>" />
            <input type="hidden" name="page" value="1" />
            <div class="lc-field">
                <label class="lc-label">De</label>
                <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Até</label>
                <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div>
                <button class="lc-btn" type="submit">Atualizar</button>
                <?php
                $exportQuery = [
                    'from' => (string)$from,
                    'to' => (string)$to,
                ];
                ?>
                <?php if ($can('stock.reports.read')): ?>
                    <a class="lc-btn lc-btn--secondary" href="/stock/reports/export.csv?<?= http_build_query($exportQuery) ?>">Exportar planilha</a>
                    <a class="lc-btn lc-btn--secondary" href="/stock/reports/export.pdf?<?= http_build_query($exportQuery) ?>">Exportar PDF</a>
                <?php endif; ?>
                <?php if ($can('stock.alerts.read')): ?>
                    <a class="lc-btn lc-btn--secondary" href="/stock/alerts">Alertas</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="lc-flex lc-flex--wrap lc-gap-sm" style="margin-bottom: 12px;">
    <a class="lc-btn <?= $tab === 'summary' ? 'lc-btn--primary' : 'lc-btn--secondary' ?>" href="<?= htmlspecialchars($tabHref('summary'), ENT_QUOTES, 'UTF-8') ?>">Resumo</a>
    <?php if ($can('stock.movements.read')): ?>
        <a class="lc-btn <?= $tab === 'movements' ? 'lc-btn--primary' : 'lc-btn--secondary' ?>" href="<?= htmlspecialchars($tabHref('movements'), ENT_QUOTES, 'UTF-8') ?>">Movimentações</a>
    <?php endif; ?>
    <?php if ($can('stock.alerts.read')): ?>
        <a class="lc-btn <?= $tab === 'alerts' ? 'lc-btn--primary' : 'lc-btn--secondary' ?>" href="<?= htmlspecialchars($tabHref('alerts'), ENT_QUOTES, 'UTF-8') ?>">Alertas</a>
    <?php endif; ?>
</div>

<?php if ($tab === 'alerts'): ?>
    <?php
        $low_stock = isset($low_stock) && is_array($low_stock) ? $low_stock : [];
        $out_of_stock = isset($out_of_stock) && is_array($out_of_stock) ? $out_of_stock : [];
        $expiring_soon = isset($expiring_soon) && is_array($expiring_soon) ? $expiring_soon : [];
        $expired = isset($expired) && is_array($expired) ? $expired : [];
        $days = max(1, min(365, (int)$days));

        $alertsQuery = [
            'tab' => 'alerts',
            'from' => (string)$from,
            'to' => (string)$to,
            'per_page' => (int)$perPage,
            'page' => 1,
        ];
    ?>

    <div class="lc-card" style="margin-bottom: 16px;">
        <div class="lc-card__header">Filtros (alertas)</div>
        <div class="lc-card__body">
            <form method="get" action="/stock/reports" class="lc-form lc-flex lc-gap-md lc-flex--wrap" style="align-items:end;">
                <input type="hidden" name="tab" value="alerts" />
                <input type="hidden" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="per_page" value="<?= (int)$perPage ?>" />
                <input type="hidden" name="page" value="1" />

                <div class="lc-field">
                    <label class="lc-label">Dias para validade próxima</label>
                    <input class="lc-input" type="number" name="days" min="1" max="365" value="<?= (int)$days ?>" />
                </div>
                <div>
                    <button class="lc-btn" type="submit">Atualizar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="lc-card" style="margin-bottom: 16px;">
        <div class="lc-card__header">Ruptura (estoque zerado)</div>
        <div class="lc-card__body">
            <?php if ($out_of_stock === []): ?>
                <div class="lc-muted">Nenhum material em ruptura.</div>
            <?php else: ?>
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Material</th>
                        <th>Estoque</th>
                        <th>Mínimo</th>
                        <th>Sugestão compra</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($out_of_stock as $it): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)$it['stock_current'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)$it['stock_minimum'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)$it['suggested_buy'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="lc-card" style="margin-bottom: 16px;">
        <div class="lc-card__header">Estoque baixo (abaixo do mínimo)</div>
        <div class="lc-card__body">
            <?php if ($low_stock === []): ?>
                <div class="lc-muted">Nenhum material abaixo do mínimo.</div>
            <?php else: ?>
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Material</th>
                        <th>Estoque</th>
                        <th>Mínimo</th>
                        <th>Sugestão compra</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($low_stock as $it): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)$it['stock_current'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)$it['stock_minimum'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)$it['suggested_buy'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="lc-card" style="margin-bottom: 16px;">
        <div class="lc-card__header">Validade vencida</div>
        <div class="lc-card__body">
            <?php if ($expired === []): ?>
                <div class="lc-muted">Nenhum material vencido.</div>
            <?php else: ?>
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Material</th>
                        <th>Validade</th>
                        <th>Estoque</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($expired as $it): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$it['validity_date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)$it['stock_current'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="lc-card">
        <div class="lc-card__header">Validade próxima (até <?= (int)$days ?> dias)</div>
        <div class="lc-card__body">
            <?php if ($expiring_soon === []): ?>
                <div class="lc-muted">Nenhum material com validade próxima.</div>
            <?php else: ?>
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Material</th>
                        <th>Validade</th>
                        <th>Estoque</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($expiring_soon as $it): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$it['validity_date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)$it['stock_current'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($tab === 'movements'): ?>
    <?php
        $movements = isset($movements) && is_array($movements) ? $movements : [];
        $materials = isset($materials) && is_array($materials) ? $materials : [];
        $matMap = [];
        foreach ($materials as $m) {
            $matMap[(int)$m['id']] = $m;
        }

        $pagerBaseQuery = [
            'tab' => 'movements',
            'from' => (string)$from,
            'to' => (string)$to,
            'per_page' => (int)$perPage,
        ];
        $pagerHref = function (int $targetPage) use ($pagerBaseQuery): string {
            return '/stock/reports?' . http_build_query($pagerBaseQuery + ['page' => $targetPage]);
        };
    ?>

    <?php if ($can('stock.movements.create')): ?>
        <div class="lc-card" style="margin-bottom: 16px;">
            <div class="lc-card__header">Nova movimentação</div>
            <div class="lc-card__body">
                <form method="post" action="/stock/movements/create" class="lc-form lc-grid lc-gap-grid lc-grid--end" style="grid-template-columns: 2fr 1fr 1fr 1fr 2fr;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="return_to" value="/stock/reports?<?= htmlspecialchars(http_build_query($pagerBaseQuery + ['page' => (int)$page]), ENT_QUOTES, 'UTF-8') ?>" />

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
                            <option value="adjustment">Ajuste (define estoque)</option>
                            <option value="loss">Perda</option>
                            <option value="expiration">Vencimento</option>
                        </select>
                    </div>

                    <div class="lc-field">
                        <label class="lc-label">Quantidade</label>
                        <input class="lc-input" type="text" name="quantity" required />
                    </div>

                    <div class="lc-field">
                        <label class="lc-label">Motivo perda</label>
                        <select class="lc-select" name="loss_reason">
                            <option value="">-</option>
                            <option value="expiration">Vencimento</option>
                            <option value="breakage">Quebra</option>
                            <option value="contamination">Contaminação</option>
                            <option value="operational_error">Erro operacional</option>
                        </select>
                    </div>

                    <div class="lc-field">
                        <label class="lc-label">Observações</label>
                        <input class="lc-input" type="text" name="notes" />
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <button class="lc-btn" type="submit">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="lc-card">
        <div class="lc-card__header">Movimentações</div>
        <div class="lc-card__body">
            <?php if ($movements === []): ?>
                <div class="lc-muted">Nenhuma movimentação no período.</div>
            <?php else: ?>
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Material</th>
                        <th>Tipo</th>
                        <th>Qtd</th>
                        <th>Custo total</th>
                        <th>Motivo</th>
                        <th>Obs</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($movements as $mv): ?>
                        <?php
                            $mid = (int)$mv['material_id'];
                            $mname = isset($matMap[$mid]) ? (string)$matMap[$mid]['name'] : ('#' . $mid);
                            $munit = isset($matMap[$mid]) ? (string)$matMap[$mid]['unit'] : '';

                            $type = (string)($mv['type'] ?? '');
                            $typeLabelMap = [
                                'entry' => 'Entrada',
                                'exit' => 'Saída',
                                'adjustment' => 'Ajuste',
                                'loss' => 'Perda',
                                'expiration' => 'Vencimento',
                            ];
                            $typeLabel = (string)($typeLabelMap[$type] ?? $type);

                            $reason = (string)($mv['loss_reason'] ?? '');
                            $reasonLabelMap = [
                                'expiration' => 'Vencimento',
                                'breakage' => 'Quebra',
                                'contamination' => 'Contaminação',
                                'operational_error' => 'Erro operacional',
                            ];
                            $reasonLabel = $reason !== '' ? (string)($reasonLabelMap[$reason] ?? $reason) : '';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$mv['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($mname, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)$mv['quantity'], 3, ',', '.') ?> <?= htmlspecialchars($munit, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)$mv['total_cost_snapshot'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($reasonLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($mv['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-sm" style="margin-top:12px;">
                <div class="lc-muted">Página <?= (int)$page ?></div>
                <div class="lc-flex lc-gap-sm">
                    <?php if ($page > 1): ?>
                        <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars($pagerHref((int)($page - 1)), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
                    <?php endif; ?>
                    <?php if ($hasNext): ?>
                        <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars($pagerHref((int)($page + 1)), ENT_QUOTES, 'UTF-8') ?>">Próxima</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Resumo do período</div>
    <div class="lc-card__body">
        <div class="lc-grid lc-grid--4 lc-gap-grid">
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Custo total saídas: <strong>R$ <?= number_format((float)($summary['total_exit_cost'] ?? 0), 2, ',', '.') ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Custo perdas: <strong>R$ <?= number_format((float)($summary['total_loss_cost'] ?? 0), 2, ',', '.') ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Custo vencimento: <strong>R$ <?= number_format((float)($summary['total_expiration_cost'] ?? 0), 2, ',', '.') ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Custo consumo (sessões): <strong>R$ <?= number_format((float)($summary['total_session_cost'] ?? 0), 2, ',', '.') ?></strong></div></div>
        </div>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Consumo por material</div>
    <div class="lc-card__body">
        <?php if ($by_material === []): ?>
            <div class="lc-muted">Sem dados no período.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Material</th>
                    <th>Qtd saída</th>
                    <th>Custo</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($by_material as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['material_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$it['qty'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>R$ <?= number_format((float)$it['cost'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Perdas por motivo</div>
    <div class="lc-card__body">
        <?php if (($losses_by_reason ?? []) === []): ?>
            <div class="lc-muted">Sem dados no período.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Motivo</th>
                    <th>Qtd</th>
                    <th>Custo</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (($losses_by_reason ?? []) as $it): ?>
                    <?php
                        $reason = (string)($it['loss_reason'] ?? '');
                        $reasonLabelMap = [
                            '' => 'Não informado',
                            'expiration' => 'Vencimento',
                            'breakage' => 'Quebra',
                            'contamination' => 'Contaminação',
                            'operational_error' => 'Erro operacional',
                            'internal_use' => 'Uso interno',
                        ];
                        $reasonLabel = (string)($reasonLabelMap[$reason] ?? $reason);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($reasonLabel, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)($it['qty'] ?? 0), 3, ',', '.') ?></td>
                        <td>R$ <?= number_format((float)($it['cost'] ?? 0), 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Perdas por material</div>
    <div class="lc-card__body">
        <?php if (($losses_by_material ?? []) === []): ?>
            <div class="lc-muted">Sem dados no período.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Material</th>
                    <th>Qtd</th>
                    <th>Custo</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (($losses_by_material ?? []) as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($it['material_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)($it['qty'] ?? 0), 3, ',', '.') ?> <?= htmlspecialchars((string)($it['unit'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>R$ <?= number_format((float)($it['cost'] ?? 0), 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Consumo por serviço</div>
    <div class="lc-card__body">
        <?php if ($by_service === []): ?>
            <div class="lc-muted">Sem dados no período.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Serviço</th>
                    <th>Qtd sessões</th>
                    <th>Custo</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($by_service as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['service_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$it['sessions'] ?></td>
                        <td>R$ <?= number_format((float)$it['cost'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Consumo por profissional</div>
    <div class="lc-card__body">
        <?php if ($by_professional === []): ?>
            <div class="lc-muted">Sem dados no período.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Profissional</th>
                    <th>Qtd sessões</th>
                    <th>Custo</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($by_professional as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['professional_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$it['sessions'] ?></td>
                        <td>R$ <?= number_format((float)$it['cost'], 2, ',', '.') ?></td>
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
?>
