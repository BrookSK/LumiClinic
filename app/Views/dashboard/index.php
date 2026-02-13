<?php
$title = 'Dashboard';
$has_clinic_context = $has_clinic_context ?? false;
$can_schedule = $can_schedule ?? false;
$can_patients = $can_patients ?? false;
$can_finance = $can_finance ?? false;
$can_stock_alerts = $can_stock_alerts ?? false;
$kpis = $kpis ?? [];
$upcoming_appointments = $upcoming_appointments ?? [];
$today_patients = $today_patients ?? [];
$stock_alerts = $stock_alerts ?? [];
ob_start();
?>
<?php if (!$has_clinic_context): ?>
    <div class="lc-card">
        <div class="lc-card__title">Selecione uma clínica</div>
        <div class="lc-card__body">
            Para visualizar indicadores e agenda, selecione um contexto de clínica.
        </div>
    </div>
<?php else: ?>
    <div class="lc-grid">
        <?php if ($can_schedule): ?>
            <div class="lc-card">
                <div class="lc-card__title">Agenda hoje</div>
                <div class="lc-card__body">
                    <div><strong><?= (int)($kpis['today_total'] ?? 0) ?></strong> atendimentos</div>
                    <div class="lc-flex lc-flex--wrap lc-gap-sm lc-mt-sm">
                        <div class="lc-badge lc-badge--primary">Confirmados: <?= (int)($kpis['today_confirmed'] ?? 0) ?></div>
                        <div class="lc-badge">Em andamento: <?= (int)($kpis['today_in_progress'] ?? 0) ?></div>
                        <div class="lc-badge">Concluídos: <?= (int)($kpis['today_completed'] ?? 0) ?></div>
                    </div>
                    <div class="lc-mt-sm">
                        <a class="lc-link" href="/schedule">Abrir agenda</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($can_patients): ?>
            <div class="lc-card">
                <div class="lc-card__title">Pacientes do dia</div>
                <div class="lc-card__body">
                    <div><strong><?= (int)($kpis['today_unique_patients'] ?? 0) ?></strong> pacientes</div>
                    <div class="lc-mt-sm">
                        <a class="lc-link" href="/patients">Abrir pacientes</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($can_finance): ?>
            <div class="lc-card">
                <div class="lc-card__title">Financeiro (mês)</div>
                <div class="lc-card__body">
                    <div><strong>R$ <?= number_format((float)($kpis['revenue_paid_month'] ?? 0.0), 2, ',', '.') ?></strong> recebido</div>
                    <div class="lc-mt-sm">
                        <a class="lc-link" href="/finance/sales">Abrir financeiro</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($can_stock_alerts): ?>
            <div class="lc-card">
                <div class="lc-card__title">Alertas de estoque</div>
                <div class="lc-card__body">
                    <div class="lc-flex lc-flex--wrap lc-gap-sm">
                        <div class="lc-badge">Baixo: <?= (int)($stock_alerts['low_stock'] ?? 0) ?></div>
                        <div class="lc-badge">Zerado: <?= (int)($stock_alerts['out_of_stock'] ?? 0) ?></div>
                        <div class="lc-badge">Vencendo: <?= (int)($stock_alerts['expiring_soon'] ?? 0) ?></div>
                        <div class="lc-badge">Vencido: <?= (int)($stock_alerts['expired'] ?? 0) ?></div>
                    </div>
                    <div class="lc-mt-sm">
                        <a class="lc-link" href="/stock/alerts">Ver alertas</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
