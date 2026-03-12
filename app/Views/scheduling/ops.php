<?php
/** @var string $date */
/** @var array<string,int> $counts */
/** @var string|null $category */
/** @var list<array<string, mixed>>|null $items */
/** @var string|null $patient_name */
/** @var string|null $patient_cpf */
/** @var string|null $time_from */
/** @var string|null $time_to */
/** @var int|null $filter_professional_id */
/** @var int|null $filter_service_id */
/** @var int|null $filter_service_category_id */
/** @var list<array<string,mixed>>|null $professionals */
/** @var list<array<string,mixed>>|null $services */
/** @var list<array<string,mixed>>|null $service_categories */
$title = 'Operação (Agenda)';

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
    <div class="lc-card__header">Indicadores do dia</div>
    <div class="lc-card__body">
        <form method="get" action="/schedule/ops" class="lc-form" style="display:grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap:12px; align-items:end;">
            <div class="lc-field" style="grid-column: span 2; min-width: 170px;">
                <label class="lc-label">Data</label>
                <input class="lc-input" type="date" name="date" value="<?= htmlspecialchars((string)$date, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-field" style="grid-column: span 2; min-width: 160px;">
                <label class="lc-label">Categoria</label>
                <select class="lc-input" name="category">
                    <option value="all" <?= (($category ?? 'all') === 'all') ? 'selected' : '' ?>>Todos</option>
                    <option value="pending" <?= (($category ?? 'all') === 'pending') ? 'selected' : '' ?>>Pendentes</option>
                    <option value="finalized" <?= (($category ?? 'all') === 'finalized') ? 'selected' : '' ?>>Finalizados</option>
                </select>
            </div>

            <div class="lc-field" style="grid-column: span 3; min-width: 220px;">
                <label class="lc-label">Paciente</label>
                <input class="lc-input" type="text" name="patient_name" value="<?= htmlspecialchars((string)($patient_name ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome" />
            </div>

            <div class="lc-field" style="grid-column: span 2; min-width: 160px;">
                <label class="lc-label">CPF</label>
                <input class="lc-input" type="text" name="patient_cpf" value="<?= htmlspecialchars((string)($patient_cpf ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="CPF" />
            </div>

            <div class="lc-field" style="grid-column: span 1; min-width: 120px;">
                <label class="lc-label">Horário (de)</label>
                <input class="lc-input" type="time" name="time_from" value="<?= htmlspecialchars((string)($time_from ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-field" style="grid-column: span 1; min-width: 120px;">
                <label class="lc-label">Horário (até)</label>
                <input class="lc-input" type="time" name="time_to" value="<?= htmlspecialchars((string)($time_to ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <?php $profItems = is_array($professionals ?? null) ? (array)$professionals : []; ?>
            <div class="lc-field" style="grid-column: span 3; min-width: 220px;">
                <label class="lc-label">Profissional</label>
                <select class="lc-input" name="filter_professional_id">
                    <option value="">Todos</option>
                    <?php foreach ($profItems as $p): ?>
                        <?php $pid = (int)($p['id'] ?? 0); ?>
                        <option value="<?= $pid ?>" <?= ((int)($filter_professional_id ?? 0) === $pid) ? 'selected' : '' ?>><?= htmlspecialchars((string)($p['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php $svcItems = is_array($services ?? null) ? (array)$services : []; ?>
            <div class="lc-field" style="grid-column: span 3; min-width: 220px;">
                <label class="lc-label">Serviço</label>
                <select class="lc-input" name="filter_service_id">
                    <option value="">Todos</option>
                    <?php foreach ($svcItems as $s): ?>
                        <?php $sid = (int)($s['id'] ?? 0); ?>
                        <option value="<?= $sid ?>" <?= ((int)($filter_service_id ?? 0) === $sid) ? 'selected' : '' ?>><?= htmlspecialchars((string)($s['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php $catItems = is_array($service_categories ?? null) ? (array)$service_categories : []; ?>
            <div class="lc-field" style="grid-column: span 3; min-width: 220px;">
                <label class="lc-label">Categoria do serviço</label>
                <select class="lc-input" name="filter_service_category_id">
                    <option value="">Todas</option>
                    <?php foreach ($catItems as $c): ?>
                        <?php $cid = (int)($c['id'] ?? 0); ?>
                        <option value="<?= $cid ?>" <?= ((int)($filter_service_category_id ?? 0) === $cid) ? 'selected' : '' ?>><?= htmlspecialchars((string)($c['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lc-form__actions" style="grid-column: span 3; display:flex; gap:10px; justify-content:flex-end;">
                <button class="lc-btn" type="submit">Ver</button>
                <a class="lc-btn lc-btn--secondary" href="/schedule?date=<?= urlencode((string)$date) ?>">Voltar à agenda</a>
            </div>
        </form>

        <div style="display:grid; grid-template-columns: repeat(8, minmax(0, 1fr)); gap:10px; margin-top:14px;">
            <div class="lc-card" style="margin:0;"><div class="lc-card__body" style="padding:10px; display:flex; flex-direction:column; gap:4px;"><div class="lc-muted" style="font-size:12px;">Total</div><div style="font-weight:800; font-size:18px; line-height:1;"><?= (int)($counts['total'] ?? 0) ?></div></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body" style="padding:10px; display:flex; flex-direction:column; gap:4px;"><div class="lc-muted" style="font-size:12px;">Pendentes</div><div style="font-weight:800; font-size:18px; line-height:1;"><?= (int)($counts['pending'] ?? 0) ?></div></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body" style="padding:10px; display:flex; flex-direction:column; gap:4px;"><div class="lc-muted" style="font-size:12px;">Finalizados</div><div style="font-weight:800; font-size:18px; line-height:1;"><?= (int)($counts['finalized'] ?? 0) ?></div></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body" style="padding:10px; display:flex; flex-direction:column; gap:4px;"><div class="lc-muted" style="font-size:12px;">Agendados</div><div style="font-weight:800; font-size:18px; line-height:1;"><?= (int)($counts['scheduled'] ?? 0) ?></div></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body" style="padding:10px; display:flex; flex-direction:column; gap:4px;"><div class="lc-muted" style="font-size:12px;">Confirmados</div><div style="font-weight:800; font-size:18px; line-height:1;"><?= (int)($counts['confirmed'] ?? 0) ?></div></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body" style="padding:10px; display:flex; flex-direction:column; gap:4px;"><div class="lc-muted" style="font-size:12px;">Em atendimento</div><div style="font-weight:800; font-size:18px; line-height:1;"><?= (int)($counts['in_progress'] ?? 0) ?></div></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body" style="padding:10px; display:flex; flex-direction:column; gap:4px;"><div class="lc-muted" style="font-size:12px;">Concluídos</div><div style="font-weight:800; font-size:18px; line-height:1;"><?= (int)($counts['completed'] ?? 0) ?></div></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body" style="padding:10px; display:flex; flex-direction:column; gap:4px;"><div class="lc-muted" style="font-size:12px;">Cancelados</div><div style="font-weight:800; font-size:18px; line-height:1;"><?= (int)($counts['cancelled'] ?? 0) ?></div></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body" style="padding:10px; display:flex; flex-direction:column; gap:4px;"><div class="lc-muted" style="font-size:12px;">Faltou</div><div style="font-weight:800; font-size:18px; line-height:1;"><?= (int)($counts['no_show'] ?? 0) ?></div></div></div>
        </div>

        <?php $pendingRequests = $pending_requests ?? []; ?>
        <div class="lc-card" style="margin-top:16px;">
            <div class="lc-card__header">Solicitações do portal (pendentes)</div>
            <div class="lc-card__body">
                <?php if (!is_array($pendingRequests) || $pendingRequests === []): ?>
                    <div class="lc-muted">Nenhuma solicitação pendente.</div>
                <?php else: ?>
                    <div class="lc-table-wrap">
                        <table class="lc-table" style="margin:0;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Paciente</th>
                                    <th>Agendamento</th>
                                    <th>Profissional</th>
                                    <th>Serviço</th>
                                    <th>Data solicitada</th>
                                    <th>Obs.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingRequests as $r): ?>
                                    <?php
                                        $type = (string)($r['type'] ?? '');
                                        $typeLabel = $type === 'reschedule' ? 'Reagendamento' : ($type === 'cancel' ? 'Cancelamento' : $type);
                                        $apptStart = (string)($r['appointment_start_at'] ?? '');
                                        $apptDate = $apptStart !== '' ? substr($apptStart, 0, 10) : (string)$date;
                                        $apptId = (int)($r['appointment_id'] ?? 0);
                                    ?>
                                    <tr>
                                        <td>#<?= (int)($r['id'] ?? 0) ?></td>
                                        <td><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($r['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <a href="/schedule?date=<?= urlencode($apptDate) ?>&created=<?= $apptId ?>">
                                                #<?= $apptId ?>
                                                <?= $apptStart !== '' ? (' • ' . htmlspecialchars(substr($apptStart, 11, 5), ENT_QUOTES, 'UTF-8')) : '' ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars((string)($r['professional_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($r['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($r['requested_start_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($r['note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($items) && is_array($items)): ?>
            <?php
                $statusLabelMap = [
                    'scheduled' => 'Agendado',
                    'confirmed' => 'Confirmado',
                    'in_progress' => 'Em atendimento',
                    'completed' => 'Concluído',
                    'no_show' => 'Faltou',
                    'cancelled' => 'Cancelado',
                ];
            ?>
            <div class="lc-card" style="margin-top:16px;">
                <div class="lc-card__header">Agendamentos (<?= htmlspecialchars((string)$date, ENT_QUOTES, 'UTF-8') ?>)</div>
                <div class="lc-card__body" style="padding:0;">
                    <table class="lc-table" style="margin:0;">
                        <thead>
                            <tr>
                                <th>Início</th>
                                <th>Fim</th>
                                <th>ID</th>
                                <th>Status</th>
                                <th>Paciente</th>
                                <th>CPF</th>
                                <th>Profissional</th>
                                <th>Serviço</th>
                                <th>Categoria</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($items === []): ?>
                                <tr><td colspan="10" class="lc-muted" style="padding:12px;">Nenhum agendamento encontrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($items as $it): ?>
                                    <?php $st = (string)($it['status'] ?? ''); ?>
                                    <?php $apptId = (int)($it['id'] ?? 0); ?>
                                    <tr>
                                        <td><?= htmlspecialchars(substr((string)($it['start_at'] ?? ''), 11, 5), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(substr((string)($it['end_at'] ?? ''), 11, 5), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <a href="/schedule?date=<?= urlencode((string)$date) ?>&created=<?= $apptId ?>">
                                                #<?= $apptId ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars((string)($statusLabelMap[$st] ?? $st), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($it['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($it['patient_cpf'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($it['professional_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($it['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($it['service_category_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td style="white-space:nowrap;">
                                            <?php if ($can('scheduling.logs')): ?>
                                                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/schedule/logs?appointment_id=<?= $apptId ?>">Logs</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
