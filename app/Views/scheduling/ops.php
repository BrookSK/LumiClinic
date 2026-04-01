<?php
$title = 'Recepção';
$csrf = $_SESSION['_csrf'] ?? '';
$date = $date ?? date('Y-m-d');
$items = $items ?? [];
$counts = $counts ?? [];
$professionals = $professionals ?? [];
$patient_name = $patient_name ?? '';
$filter_professional_id = $filter_professional_id ?? 0;
$pendingRequests = $pending_requests ?? [];

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) return false;
    if (isset($permissions['allow'], $permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) return false;
        return in_array($permissionCode, $permissions['allow'], true);
    }
    return in_array($permissionCode, $permissions, true);
};

$statusLabelMap = [
    'scheduled'   => 'Agendado',
    'confirmed'   => 'Confirmado',
    'in_progress' => 'Em atendimento',
    'completed'   => 'Concluído',
    'no_show'     => 'Faltou',
    'cancelled'   => 'Cancelado',
];

$statusBadge = [
    'scheduled'   => 'lc-badge--secondary',
    'confirmed'   => 'lc-badge--primary',
    'in_progress' => 'lc-badge--success',
    'completed'   => 'lc-badge--success',
    'no_show'     => 'lc-badge--danger',
    'cancelled'   => 'lc-badge--danger',
];

// Formatar data para exibição
$dateDt = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
$dateDisplay = $dateDt !== false ? $dateDt->format('d/m/Y') : $date;
$isToday = ($date === date('Y-m-d'));

ob_start();
?>

<div class="lc-pagehead" style="margin-bottom:16px;">
    <div>
        <div class="lc-pagehead__title">Recepção</div>
        <div class="lc-pagehead__meta">
            <span class="lc-badge lc-badge--primary"><?= htmlspecialchars($dateDisplay, ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($isToday): ?>
                <span class="lc-badge lc-badge--success">Hoje</span>
            <?php endif; ?>
            <?php if ((int)($counts['total'] ?? 0) > 0): ?>
                <span class="lc-badge"><?= (int)$counts['total'] ?> agendamentos</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="lc-pagehead__actions">
        <a class="lc-btn lc-btn--secondary" href="/schedule?date=<?= urlencode($date) ?>">Ver agenda</a>
    </div>
</div>

<!-- Filtros simplificados -->
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <form method="get" action="/schedule/ops" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
            <div class="lc-field">
                <label class="lc-label">Data</label>
                <input class="lc-input" type="date" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-field" style="min-width:220px;">
                <label class="lc-label">Buscar paciente</label>
                <input class="lc-input" type="text" name="patient_name"
                       value="<?= htmlspecialchars((string)$patient_name, ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Nome do paciente..."
                       autofocus />
            </div>

            <?php if (!empty($professionals)): ?>
            <div class="lc-field" style="min-width:180px;">
                <label class="lc-label">Profissional</label>
                <select class="lc-select" name="filter_professional_id">
                    <option value="">Todos</option>
                    <?php foreach ($professionals as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= ((int)$filter_professional_id === (int)$p['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <button class="lc-btn lc-btn--primary" type="submit">Buscar</button>
            <?php if ($patient_name !== '' || (int)$filter_professional_id > 0): ?>
                <a class="lc-btn lc-btn--secondary" href="/schedule/ops?date=<?= urlencode($date) ?>">Limpar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Contadores rápidos -->
<div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom:14px;">
    <?php
    $counterItems = [
        ['label' => 'Aguardando', 'value' => (int)($counts['scheduled'] ?? 0) + (int)($counts['confirmed'] ?? 0), 'color' => '#eeb810'],
        ['label' => 'Em atendimento', 'value' => (int)($counts['in_progress'] ?? 0), 'color' => '#16a34a'],
        ['label' => 'Concluídos', 'value' => (int)($counts['completed'] ?? 0), 'color' => '#6b7280'],
        ['label' => 'Faltou / Cancelado', 'value' => (int)($counts['no_show'] ?? 0) + (int)($counts['cancelled'] ?? 0), 'color' => '#b91c1c'],
    ];
    foreach ($counterItems as $ci):
    ?>
    <div class="lc-card" style="margin:0;">
        <div class="lc-card__body" style="padding:12px;">
            <div class="lc-muted" style="font-size:12px;"><?= htmlspecialchars($ci['label'], ENT_QUOTES, 'UTF-8') ?></div>
            <div style="font-weight:800; font-size:26px; line-height:1.1; margin-top:4px; color:<?= $ci['color'] ?>;">
                <?= $ci['value'] ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Solicitações do portal pendentes -->
<?php if (!empty($pendingRequests)): ?>
<div class="lc-card" style="margin-bottom:14px; border-left:3px solid #eeb810;">
    <div class="lc-card__header">
        Solicitações do portal
        <span class="lc-badge lc-badge--primary" style="margin-left:8px;"><?= count($pendingRequests) ?></span>
    </div>
    <div class="lc-card__body" style="padding:0;">
        <table class="lc-table">
            <thead>
            <tr>
                <th>Tipo</th>
                <th>Paciente</th>
                <th>Agendamento</th>
                <th>Profissional</th>
                <th>Observação</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pendingRequests as $r): ?>
                <?php
                $type = (string)($r['type'] ?? '');
                $typeLabel = $type === 'reschedule' ? 'Reagendamento' : ($type === 'cancel' ? 'Cancelamento' : $type);
                $apptStart = (string)($r['appointment_start_at'] ?? '');
                $apptId = (int)($r['appointment_id'] ?? 0);
                $apptDate = $apptStart !== '' ? substr($apptStart, 0, 10) : $date;
                ?>
                <tr>
                    <td><span class="lc-badge lc-badge--secondary"><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars((string)($r['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <a href="/schedule?date=<?= urlencode($apptDate) ?>">
                            <?= $apptStart !== '' ? htmlspecialchars(substr($apptStart, 11, 5), ENT_QUOTES, 'UTF-8') : '#' . $apptId ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars((string)($r['professional_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="lc-muted" style="font-size:12px;"><?= htmlspecialchars((string)($r['note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="lc-td-actions">
                        <div class="lc-flex lc-gap-sm">
                            <form method="post" action="/schedule/ops/request/resolve">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>" />
                                <input type="hidden" name="action" value="approve" />
                                <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">✓ Aprovar</button>
                            </form>
                            <form method="post" action="/schedule/ops/request/resolve" onsubmit="return confirm('Rejeitar esta solicitação?');">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>" />
                                <input type="hidden" name="action" value="reject" />
                                <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">✕ Rejeitar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Lista de agendamentos do dia -->
<div class="lc-card">
    <div class="lc-card__header">
        Agendamentos — <?= htmlspecialchars($dateDisplay, ENT_QUOTES, 'UTF-8') ?>
        <?php if ($patient_name !== ''): ?>
            <span class="lc-muted" style="font-size:12px; margin-left:8px;">filtrando por "<?= htmlspecialchars($patient_name, ENT_QUOTES, 'UTF-8') ?>"</span>
        <?php endif; ?>
    </div>
    <div class="lc-card__body" style="padding:0;">
        <?php if (empty($items)): ?>
            <div class="lc-muted" style="padding:20px;">
                <?= $patient_name !== '' ? 'Nenhum agendamento encontrado para "' . htmlspecialchars($patient_name, ENT_QUOTES, 'UTF-8') . '".' : 'Nenhum agendamento para este dia.' ?>
            </div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Horário</th>
                    <th>Paciente</th>
                    <th>Serviço</th>
                    <th>Profissional</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <?php
                    $st = (string)($it['status'] ?? '');
                    $apptId = (int)($it['id'] ?? 0);
                    $checkedIn = (string)($it['checked_in_at'] ?? '') !== '';
                    $started = (string)($it['started_at'] ?? '') !== '';
                    $canCheckIn = in_array($st, ['scheduled', 'confirmed'], true) && !$checkedIn;
                    $canStart = in_array($st, ['scheduled', 'confirmed'], true) && !$started;
                    $canNoShow = in_array($st, ['scheduled', 'confirmed', 'in_progress'], true);
                    $patientName = trim((string)($it['patient_name'] ?? ''));
                    ?>
                    <tr id="row-appt-<?= $apptId ?>" style="<?= in_array($st, ['cancelled', 'no_show'], true) ? 'opacity:.5;' : '' ?>">
                        <td style="font-weight:700; white-space:nowrap;">
                            <?= htmlspecialchars(substr((string)($it['start_at'] ?? ''), 11, 5), ENT_QUOTES, 'UTF-8') ?>
                            <span class="lc-muted" style="font-weight:400;">– <?= htmlspecialchars(substr((string)($it['end_at'] ?? ''), 11, 5), ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td style="font-weight:600;">
                            <?= htmlspecialchars($patientName !== '' ? $patientName : '—', ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($checkedIn): ?>
                                <span class="lc-badge lc-badge--success" style="font-size:10px; margin-left:4px;">Chegou</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars((string)($it['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($it['professional_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="lc-badge <?= htmlspecialchars($statusBadge[$st] ?? 'lc-badge--secondary', ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($statusLabelMap[$st] ?? $st, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="lc-td-actions">
                            <div class="lc-flex lc-gap-sm">
                                <?php if ($canCheckIn && $can('scheduling.finalize')): ?>
                                    <form method="post" action="/schedule/check-in">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                        <input type="hidden" name="id" value="<?= $apptId ?>" />
                                        <input type="hidden" name="return_ops" value="1" />
                                        <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                        <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">✓ Chegou</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($canNoShow && $can('scheduling.finalize')): ?>
                                    <form method="post" action="/schedule/status">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                        <input type="hidden" name="id" value="<?= $apptId ?>" />
                                        <input type="hidden" name="status" value="no_show" />
                                        <input type="hidden" name="return_ops" value="1" />
                                        <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                        <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" title="Marcar como faltou">Faltou</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
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
