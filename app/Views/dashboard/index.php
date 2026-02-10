<?php
$title = 'Dashboard';
$has_clinic_context = $has_clinic_context ?? false;
$can_schedule = $can_schedule ?? false;
$can_patients = $can_patients ?? false;
$can_finance = $can_finance ?? false;
$can_stock_alerts = $can_stock_alerts ?? false;
$kpis = $kpis ?? [];
$upcoming_appointments = $upcoming_appointments ?? [];
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

        <div class="lc-card">
            <div class="lc-card__title">Resumo do mês</div>
            <div class="lc-card__body">
                <?php if ($can_patients): ?>
                    <div><strong><?= (int)($kpis['new_patients_month'] ?? 0) ?></strong> novos pacientes</div>
                <?php else: ?>
                    <div>Novos pacientes: <span class="lc-badge">sem permissão</span></div>
                <?php endif; ?>

                <?php if ($can_finance): ?>
                    <div class="lc-mt-sm"><strong>R$ <?= number_format((float)($kpis['revenue_paid_month'] ?? 0.0), 2, ',', '.') ?></strong> recebido</div>
                <?php else: ?>
                    <div class="lc-mt-sm">Recebido: <span class="lc-badge">sem permissão</span></div>
                <?php endif; ?>

                <div class="lc-mt-sm">
                    <?php if ($can_patients): ?>
                        <a class="lc-link" href="/patients">Pacientes</a>
                    <?php endif; ?>
                    <?php if ($can_patients && $can_finance): ?>
                        <span class="lc-sep">|</span>
                    <?php endif; ?>
                    <?php if ($can_finance): ?>
                        <a class="lc-link" href="/finance/sales">Financeiro</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="lc-grid lc-mt-md">
        <?php if ($can_schedule): ?>
            <div class="lc-card">
                <div class="lc-card__title">Próximos atendimentos</div>
                <div class="lc-card__body">
                    <?php if (!is_array($upcoming_appointments) || $upcoming_appointments === []): ?>
                        Nenhum atendimento futuro encontrado.
                    <?php else: ?>
                        <div class="lc-table-wrap">
                            <table class="lc-table">
                                <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Paciente</th>
                                    <th>Serviço</th>
                                    <th>Profissional</th>
                                    <th>Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($upcoming_appointments as $a): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)($a['start_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($a['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($a['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($a['professional_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="lc-badge"><?= htmlspecialchars((string)($a['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
