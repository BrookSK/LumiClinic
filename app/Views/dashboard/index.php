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

$userName = trim((string)($_SESSION['user_name'] ?? ''));
$greeting = '';
date_default_timezone_set('America/Sao_Paulo');
$hour = (int)date('H');
if ($hour < 12) { $greeting = 'Bom dia'; }
elseif ($hour < 18) { $greeting = 'Boa tarde'; }
else { $greeting = 'Boa noite'; }

$diasSemana = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
$meses = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$dataFormatada = $diasSemana[(int)date('w')] . ', ' . date('d') . ' de ' . $meses[(int)date('n')] . ' de ' . date('Y');

ob_start();
?>

<style>
.dash-header {
    margin-bottom: 28px;
}
.dash-header__greeting {
    font-size: 22px;
    font-weight: 850;
    color: rgba(31,41,55,.94);
    letter-spacing: -0.3px;
}
.dash-header__sub {
    font-size: 13px;
    color: rgba(31,41,55,.45);
    margin-top: 3px;
}

/* KPI Cards Grid */
.dash-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}

.dash-kpi {
    position: relative;
    padding: 20px 22px;
    border-radius: 16px;
    border: 1px solid rgba(17,24,39,.06);
    box-shadow: 0 2px 12px rgba(17,24,39,.04);
    overflow: hidden;
    transition: transform .15s ease, box-shadow .15s ease;
}
.dash-kpi:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(17,24,39,.08);
}
.dash-kpi__icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
}
.dash-kpi__icon svg {
    width: 20px;
    height: 20px;
}
.dash-kpi__value {
    font-size: 28px;
    font-weight: 900;
    letter-spacing: -0.5px;
    line-height: 1;
    margin-bottom: 4px;
}
.dash-kpi__label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.dash-kpi__link {
    display: inline-block;
    margin-top: 12px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    transition: opacity .15s;
}
.dash-kpi__link:hover { opacity: .7; }

/* Variantes de cor */
.dash-kpi--agenda {
    background: linear-gradient(135deg, rgba(99,102,241,.06), rgba(99,102,241,.02));
    border-color: rgba(99,102,241,.12);
}
.dash-kpi--agenda .dash-kpi__icon { background: rgba(99,102,241,.12); }
.dash-kpi--agenda .dash-kpi__icon svg { color: #6366f1; }
.dash-kpi--agenda .dash-kpi__value { color: #4338ca; }
.dash-kpi--agenda .dash-kpi__label { color: rgba(99,102,241,.7); }
.dash-kpi--agenda .dash-kpi__link { color: #6366f1; }

.dash-kpi--patients {
    background: linear-gradient(135deg, rgba(16,185,129,.06), rgba(16,185,129,.02));
    border-color: rgba(16,185,129,.12);
}
.dash-kpi--patients .dash-kpi__icon { background: rgba(16,185,129,.12); }
.dash-kpi--patients .dash-kpi__icon svg { color: #10b981; }
.dash-kpi--patients .dash-kpi__value { color: #059669; }
.dash-kpi--patients .dash-kpi__label { color: rgba(16,185,129,.7); }
.dash-kpi--patients .dash-kpi__link { color: #10b981; }

.dash-kpi--finance {
    background: linear-gradient(135deg, rgba(238,184,16,.08), rgba(238,184,16,.02));
    border-color: rgba(238,184,16,.15);
}
.dash-kpi--finance .dash-kpi__icon { background: rgba(238,184,16,.14); }
.dash-kpi--finance .dash-kpi__icon svg { color: #b5841e; }
.dash-kpi--finance .dash-kpi__value { color: #815901; }
.dash-kpi--finance .dash-kpi__label { color: rgba(129,89,1,.6); }
.dash-kpi--finance .dash-kpi__link { color: #b5841e; }

.dash-kpi--stock {
    background: linear-gradient(135deg, rgba(244,63,94,.06), rgba(244,63,94,.02));
    border-color: rgba(244,63,94,.12);
}
.dash-kpi--stock .dash-kpi__icon { background: rgba(244,63,94,.12); }
.dash-kpi--stock .dash-kpi__icon svg { color: #f43f5e; }
.dash-kpi--stock .dash-kpi__value { color: #e11d48; }
.dash-kpi--stock .dash-kpi__label { color: rgba(244,63,94,.7); }
.dash-kpi--stock .dash-kpi__link { color: #f43f5e; }

/* Status badges inline */
.dash-statuses {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 12px;
}
.dash-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
}
.dash-status--confirmed { background: rgba(99,102,241,.10); color: #4338ca; }
.dash-status--progress  { background: rgba(245,158,11,.12); color: #92400e; }
.dash-status--done      { background: rgba(16,185,129,.10); color: #065f46; }

/* Sections */
.dash-sections {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
@media (max-width: 900px) {
    .dash-sections { grid-template-columns: 1fr; }
    .dash-kpis { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 560px) {
    .dash-kpis { grid-template-columns: 1fr; }
}

.dash-section {
    padding: 22px;
    border-radius: 16px;
    border: 1px solid rgba(17,24,39,.06);
    background: var(--lc-surface);
    box-shadow: 0 2px 12px rgba(17,24,39,.04);
}
.dash-section__title {
    font-size: 14px;
    font-weight: 800;
    color: rgba(31,41,55,.85);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.dash-section__title svg {
    width: 16px;
    height: 16px;
    color: rgba(31,41,55,.35);
}

/* Appointment list */
.dash-appt {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 10px;
    transition: background .12s;
}
.dash-appt:hover { background: rgba(99,102,241,.04); }
.dash-appt + .dash-appt { border-top: 1px solid rgba(17,24,39,.04); }
.dash-appt__time {
    font-size: 13px;
    font-weight: 800;
    color: rgba(31,41,55,.75);
    min-width: 48px;
    text-align: center;
}
.dash-appt__info { flex: 1; min-width: 0; }
.dash-appt__name {
    font-size: 13px;
    font-weight: 700;
    color: rgba(31,41,55,.88);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.dash-appt__service {
    font-size: 11px;
    color: rgba(31,41,55,.45);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.dash-appt__status {
    font-size: 10px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    white-space: nowrap;
}

/* Patient list */
.dash-patient {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 10px;
    transition: background .12s;
}
.dash-patient:hover { background: rgba(16,185,129,.04); }
.dash-patient + .dash-patient { border-top: 1px solid rgba(17,24,39,.04); }
.dash-patient__avatar {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba(16,185,129,.15), rgba(16,185,129,.08));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 800;
    color: #059669;
    flex-shrink: 0;
}
.dash-patient__info { flex: 1; min-width: 0; }
.dash-patient__name {
    font-size: 13px;
    font-weight: 700;
    color: rgba(31,41,55,.88);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.dash-patient__contact {
    font-size: 11px;
    color: rgba(31,41,55,.40);
}
.dash-patient__time {
    font-size: 11px;
    font-weight: 700;
    color: rgba(31,41,55,.45);
}

/* Stock alerts */
.dash-stock-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.dash-stock-item {
    padding: 12px;
    border-radius: 10px;
    text-align: center;
}
.dash-stock-item__value {
    font-size: 22px;
    font-weight: 900;
    line-height: 1;
    margin-bottom: 3px;
}
.dash-stock-item__label {
    font-size: 11px;
    font-weight: 600;
}
.dash-stock-item--low { background: rgba(245,158,11,.08); }
.dash-stock-item--low .dash-stock-item__value { color: #92400e; }
.dash-stock-item--low .dash-stock-item__label { color: rgba(245,158,11,.7); }
.dash-stock-item--out { background: rgba(239,68,68,.08); }
.dash-stock-item--out .dash-stock-item__value { color: #dc2626; }
.dash-stock-item--out .dash-stock-item__label { color: rgba(239,68,68,.7); }
.dash-stock-item--expiring { background: rgba(99,102,241,.06); }
.dash-stock-item--expiring .dash-stock-item__value { color: #4338ca; }
.dash-stock-item--expiring .dash-stock-item__label { color: rgba(99,102,241,.7); }
.dash-stock-item--expired { background: rgba(107,114,128,.08); }
.dash-stock-item--expired .dash-stock-item__value { color: #374151; }
.dash-stock-item--expired .dash-stock-item__label { color: rgba(107,114,128,.7); }

.dash-empty {
    text-align: center;
    padding: 24px 16px;
    color: rgba(31,41,55,.35);
    font-size: 13px;
}
</style>

<?php if (!$has_clinic_context): ?>
    <div style="padding:40px 20px;text-align:center;">
        <div style="font-size:40px;margin-bottom:12px;">🏥</div>
        <div style="font-weight:800;font-size:18px;color:rgba(31,41,55,.85);margin-bottom:6px;">Selecione uma clínica</div>
        <div style="font-size:13px;color:rgba(31,41,55,.45);">Para visualizar indicadores e agenda, selecione um contexto de clínica.</div>
    </div>
<?php else: ?>

<!-- Greeting -->
<div class="dash-header">
    <div class="dash-header__greeting"><?= htmlspecialchars($greeting, ENT_QUOTES, 'UTF-8') ?><?= $userName !== '' ? ', ' . htmlspecialchars(explode(' ', $userName)[0], ENT_QUOTES, 'UTF-8') : '' ?> 👋</div>
    <div class="dash-header__sub"><?= $dataFormatada ?> — Aqui está o resumo do dia.</div>
</div>

<!-- KPI Cards -->
<div class="dash-kpis">
    <?php if ($can_schedule): ?>
    <div class="dash-kpi dash-kpi--agenda">
        <div class="dash-kpi__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
        </div>
        <div class="dash-kpi__value"><?= (int)($kpis['today_total'] ?? 0) ?></div>
        <div class="dash-kpi__label">Atendimentos hoje</div>
        <div class="dash-statuses">
            <span class="dash-status dash-status--confirmed">✓ <?= (int)($kpis['today_confirmed'] ?? 0) ?> confirmados</span>
            <span class="dash-status dash-status--progress">● <?= (int)($kpis['today_in_progress'] ?? 0) ?> em andamento</span>
            <span class="dash-status dash-status--done">✓ <?= (int)($kpis['today_completed'] ?? 0) ?> concluídos</span>
        </div>
        <a class="dash-kpi__link" href="/schedule">Abrir agenda →</a>
    </div>
    <?php endif; ?>

    <?php if ($can_patients): ?>
    <div class="dash-kpi dash-kpi--patients">
        <div class="dash-kpi__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-3-3.87"/><path d="M4 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <div class="dash-kpi__value"><?= (int)($kpis['today_unique_patients'] ?? 0) ?></div>
        <div class="dash-kpi__label">Pacientes do dia</div>
        <a class="dash-kpi__link" href="/patients">Ver pacientes →</a>
    </div>
    <?php endif; ?>

    <?php if ($can_finance): ?>
    <div class="dash-kpi dash-kpi--finance">
        <div class="dash-kpi__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="dash-kpi__value">R$ <?= number_format((float)($kpis['revenue_paid_month'] ?? 0.0), 2, ',', '.') ?></div>
        <div class="dash-kpi__label">Receita do mês</div>
        <a class="dash-kpi__link" href="/finance/sales">Ver financeiro →</a>
    </div>
    <?php endif; ?>

    <?php if ($can_stock_alerts): ?>
    <?php
        $totalAlerts = (int)($stock_alerts['low_stock'] ?? 0) + (int)($stock_alerts['out_of_stock'] ?? 0)
            + (int)($stock_alerts['expiring_soon'] ?? 0) + (int)($stock_alerts['expired'] ?? 0);
    ?>
    <div class="dash-kpi dash-kpi--stock">
        <div class="dash-kpi__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
        </div>
        <div class="dash-kpi__value"><?= $totalAlerts ?></div>
        <div class="dash-kpi__label">Alertas de estoque</div>
        <a class="dash-kpi__link" href="/stock/alerts">Ver alertas →</a>
    </div>
    <?php endif; ?>
</div>

<!-- Detail Sections -->
<div class="dash-sections">
    <!-- Próximos atendimentos -->
    <?php if ($can_schedule): ?>
    <div class="dash-section">
        <div class="dash-section__title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            Próximos atendimentos
        </div>
        <?php if (empty($upcoming_appointments)): ?>
            <div class="dash-empty">Nenhum atendimento agendado para hoje.</div>
        <?php else: ?>
            <?php foreach ($upcoming_appointments as $appt):
                $time = date('H:i', strtotime((string)($appt['start_at'] ?? '')));
                $status = (string)($appt['status'] ?? '');
                $statusLabel = match($status) {
                    'confirmed' => 'Confirmado',
                    'in_progress' => 'Em andamento',
                    'completed' => 'Concluído',
                    'pending' => 'Pendente',
                    default => ucfirst($status),
                };
                $statusStyle = match($status) {
                    'confirmed' => 'background:rgba(99,102,241,.10);color:#4338ca;',
                    'in_progress' => 'background:rgba(245,158,11,.12);color:#92400e;',
                    'completed' => 'background:rgba(16,185,129,.10);color:#065f46;',
                    'pending' => 'background:rgba(107,114,128,.10);color:#374151;',
                    default => 'background:rgba(107,114,128,.08);color:#6b7280;',
                };
            ?>
            <div class="dash-appt">
                <div class="dash-appt__time"><?= $time ?></div>
                <div class="dash-appt__info">
                    <div class="dash-appt__name"><?= htmlspecialchars((string)($appt['patient_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="dash-appt__service"><?= htmlspecialchars((string)($appt['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?><?= !empty($appt['professional_name']) ? ' · ' . htmlspecialchars((string)$appt['professional_name'], ENT_QUOTES, 'UTF-8') : '' ?></div>
                </div>
                <span class="dash-appt__status" style="<?= $statusStyle ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Pacientes do dia -->
    <?php if ($can_patients): ?>
    <div class="dash-section">
        <div class="dash-section__title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-3-3.87"/><path d="M4 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            Pacientes do dia
        </div>
        <?php if (empty($today_patients)): ?>
            <div class="dash-empty">Nenhum paciente agendado para hoje.</div>
        <?php else: ?>
            <?php foreach (array_slice($today_patients, 0, 8) as $pat):
                $initials = '';
                $parts = explode(' ', trim((string)($pat['name'] ?? '')));
                if (count($parts) >= 2) {
                    $initials = mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
                } elseif (count($parts) === 1) {
                    $initials = mb_strtoupper(mb_substr($parts[0], 0, 2));
                }
                $patTime = !empty($pat['first_start_at']) ? date('H:i', strtotime((string)$pat['first_start_at'])) : '';
            ?>
            <div class="dash-patient">
                <div class="dash-patient__avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="dash-patient__info">
                    <div class="dash-patient__name"><?= htmlspecialchars((string)($pat['name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="dash-patient__contact"><?= htmlspecialchars((string)($pat['phone'] ?? $pat['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <?php if ($patTime !== ''): ?>
                    <div class="dash-patient__time"><?= $patTime ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Stock alerts detail -->
    <?php if ($can_stock_alerts && $totalAlerts > 0): ?>
    <div class="dash-section">
        <div class="dash-section__title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73L13 2.27a2 2 0 0 0-2 0L4 6.27A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73z"/><path d="M3.3 7L12 12l8.7-5"/><path d="M12 22V12"/></svg>
            Detalhes do estoque
        </div>
        <div class="dash-stock-grid">
            <div class="dash-stock-item dash-stock-item--low">
                <div class="dash-stock-item__value"><?= (int)($stock_alerts['low_stock'] ?? 0) ?></div>
                <div class="dash-stock-item__label">Estoque baixo</div>
            </div>
            <div class="dash-stock-item dash-stock-item--out">
                <div class="dash-stock-item__value"><?= (int)($stock_alerts['out_of_stock'] ?? 0) ?></div>
                <div class="dash-stock-item__label">Zerado</div>
            </div>
            <div class="dash-stock-item dash-stock-item--expiring">
                <div class="dash-stock-item__value"><?= (int)($stock_alerts['expiring_soon'] ?? 0) ?></div>
                <div class="dash-stock-item__label">Vencendo</div>
            </div>
            <div class="dash-stock-item dash-stock-item--expired">
                <div class="dash-stock-item__value"><?= (int)($stock_alerts['expired'] ?? 0) ?></div>
                <div class="dash-stock-item__label">Vencido</div>
            </div>
        </div>
        <div style="text-align:center;margin-top:14px;">
            <a class="dash-kpi__link" href="/stock/alerts" style="color:#f43f5e;">Ver todos os alertas →</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
