<?php
$title = 'Métricas';
$csrf = $_SESSION['_csrf'] ?? '';
$summary = $summary ?? [];
$fmtMoney = function (float $v): string {
    return 'R$ ' . number_format($v, 2, ',', '.');
};
ob_start();
?>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Resumo</div>
        <div class="lc-card__body">
            <div class="lc-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); gap:10px;">
                <div class="lc-card" style="padding:12px;">
                    <div class="lc-muted">Ticket médio</div>
                    <div style="font-size:18px; font-weight:800; margin-top:4px;">
                        <?= htmlspecialchars($fmtMoney((float)($summary['ticket_medio'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="lc-muted" style="margin-top:6px;">Vendas pagas: <?= (int)($summary['paid_sales_count'] ?? 0) ?></div>
                </div>

                <div class="lc-card" style="padding:12px;">
                    <div class="lc-muted">Retenção</div>
                    <div style="font-size:18px; font-weight:800; margin-top:4px;">
                        <?= htmlspecialchars((string)($summary['retencao_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="lc-muted" style="margin-top:6px;">
                        <?php if (($summary['days_since_last'] ?? null) !== null): ?>
                            Dias desde a última consulta: <?= (int)($summary['days_since_last'] ?? 0) ?>
                        <?php else: ?>
                            Sem consultas concluídas ainda.
                        <?php endif; ?>
                    </div>
                </div>

                <div class="lc-card" style="padding:12px;">
                    <div class="lc-muted">Recorrência</div>
                    <div style="font-size:18px; font-weight:800; margin-top:4px;">
                        <?= ((int)($summary['recorrente'] ?? 0) === 1) ? 'recorrente' : 'primeira vez' ?>
                    </div>
                    <div class="lc-muted" style="margin-top:6px;">Consultas concluídas: <?= (int)($summary['completed_appointments'] ?? 0) ?></div>
                </div>

                <div class="lc-card" style="padding:12px;">
                    <div class="lc-muted">Gastos</div>
                    <div style="font-size:18px; font-weight:800; margin-top:4px;">
                        <?= htmlspecialchars($fmtMoney((float)($summary['paid_total'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="lc-muted" style="margin-top:6px;">Total em vendas: <?= htmlspecialchars($fmtMoney((float)($summary['total_liquido'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>

            <div class="lc-muted" style="margin-top:12px;">
                Logins no portal: <?= (int)($summary['portal_logins'] ?? 0) ?>
                | Confirmações de consulta: <?= (int)($summary['appointment_confirms'] ?? 0) ?>
            </div>
        </div>
    </div>

    <div class="lc-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); gap:10px; margin-top:16px;">
        <div class="lc-card" style="padding:16px;">
            <div class="lc-card__title">Ranking por serviço (consultas)</div>
            <div class="lc-card__body">
                <?php $topServices = $summary['top_services'] ?? []; ?>
                <?php if (!is_array($topServices) || $topServices === []): ?>
                    <div class="lc-muted">Sem dados.</div>
                <?php else: ?>
                    <table class="lc-table">
                        <thead>
                        <tr>
                            <th>Serviço</th>
                            <th style="text-align:right;">Qtd</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($topServices as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($r['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="text-align:right;"><?= (int)($r['cnt'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="lc-card" style="padding:16px;">
            <div class="lc-card__title">Ranking por procedimento (receita)</div>
            <div class="lc-card__body">
                <?php $topProc = $summary['top_procedures_by_revenue'] ?? []; ?>
                <?php if (!is_array($topProc) || $topProc === []): ?>
                    <div class="lc-muted">Sem dados.</div>
                <?php else: ?>
                    <table class="lc-table">
                        <thead>
                        <tr>
                            <th>Procedimento/Serviço</th>
                            <th style="text-align:right;">Qtd</th>
                            <th style="text-align:right;">Receita</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($topProc as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($r['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="text-align:right;"><?= (int)($r['qty'] ?? 0) ?></td>
                                <td style="text-align:right;"><?= htmlspecialchars($fmtMoney((float)($r['revenue'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php
$portal_content = (string)ob_get_clean();
$portal_active = null;
require __DIR__ . '/_shell.php';
