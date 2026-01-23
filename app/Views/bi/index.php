<?php
$title = 'BI Executivo';
$csrf = $_SESSION['_csrf'] ?? '';
$period_start = $period_start ?? date('Y-m-01');
$period_end = $period_end ?? date('Y-m-d');
$metrics = $metrics ?? [];
$computed_at = $computed_at ?? null;
ob_start();
?>
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Período</div>
    <div class="lc-card__body">
        <form method="get" action="/bi" class="lc-form">
            <label class="lc-label">De</label>
            <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars((string)$period_start, ENT_QUOTES, 'UTF-8') ?>" />

            <label class="lc-label">Até</label>
            <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars((string)$period_end, ENT_QUOTES, 'UTF-8') ?>" />

            <button class="lc-btn lc-btn--primary" type="submit">Aplicar</button>
        </form>

        <form method="post" action="/bi/refresh" style="margin-top:10px;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="from" value="<?= htmlspecialchars((string)$period_start, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="to" value="<?= htmlspecialchars((string)$period_end, ENT_QUOTES, 'UTF-8') ?>" />
            <button class="lc-btn lc-btn--secondary" type="submit">Atualizar snapshot</button>
            <?php if ($computed_at): ?>
                <span style="margin-left:10px; opacity:0.8;">Última atualização: <?= htmlspecialchars((string)$computed_at, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="lc-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px;">
    <div class="lc-card" style="padding:16px;">
        <div class="lc-card__title">Receita (paga)</div>
        <div style="font-size:22px; font-weight:700; margin-top:8px;">R$ <?= htmlspecialchars(number_format((float)($metrics['revenue_paid'] ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="lc-card" style="padding:16px;">
        <div class="lc-card__title">Vendas pagas</div>
        <div style="font-size:22px; font-weight:700; margin-top:8px;"><?= (int)($metrics['sales_paid'] ?? 0) ?></div>
    </div>

    <div class="lc-card" style="padding:16px;">
        <div class="lc-card__title">Novos pacientes</div>
        <div style="font-size:22px; font-weight:700; margin-top:8px;"><?= (int)($metrics['new_patients'] ?? 0) ?></div>
    </div>
</div>

<div class="lc-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top:12px;">
    <div class="lc-card" style="padding:16px;">
        <div class="lc-card__title">Consultas (total)</div>
        <div style="font-size:22px; font-weight:700; margin-top:8px;"><?= (int)($metrics['appointments_total'] ?? 0) ?></div>
    </div>

    <div class="lc-card" style="padding:16px;">
        <div class="lc-card__title">Consultas confirmadas</div>
        <div style="font-size:22px; font-weight:700; margin-top:8px;"><?= (int)($metrics['appointments_confirmed'] ?? 0) ?></div>
    </div>
</div>

<div class="lc-card" style="margin-top:12px;">
    <div class="lc-card__title">Eventos do Portal (patient_events)</div>
    <div class="lc-card__body">
        <?php $events = $metrics['patient_events'] ?? []; ?>
        <?php if (!is_array($events) || $events === []): ?>
            <div>Sem eventos no período.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Evento</th>
                        <th>Qtd</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($events as $k => $v): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int)$v ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
