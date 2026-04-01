<?php
/** @var string $date */
/** @var list<array<string,mixed>> $items */
/** @var list<array<string,mixed>> $professionals */
/** @var list<array<string,mixed>> $services */
$funnelStages = $funnel_stages ?? [];
/** @var string $created */
/** @var string $error */
$statusClassMap = isset($status_class_map) && is_array($status_class_map) ? $status_class_map : [];
$view = isset($view) ? (string)$view : 'day';
$professionalId = isset($professional_id) ? (int)$professional_id : 0;
$isProfessional = isset($is_professional) ? (bool)$is_professional : false;
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 100;
$hasNext = isset($has_next) ? (bool)$has_next : false;
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Agenda';

$dateDisplay = $date;
$dateDt = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
if ($dateDt !== false) {
    $dateDisplay = $dateDt->format('d/m/Y');
}

$svcMap = [];
foreach ($services as $s) {
    $svcMap[(int)$s['id']] = $s;
}

$profMap = [];
foreach ($professionals as $p) {
    $profMap[(int)$p['id']] = $p;
}

$blocks = $blocks ?? [];
$workingHours = $working_hours ?? [];
$closedMap = isset($closed_map) && is_array($closed_map) ? $closed_map : [];
$slotMinutes = isset($slot_minutes) && is_array($slot_minutes) ? $slot_minutes : [];

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

<div class="lc-schedule">
    <div class="lc-pagehead">
        <div>
            <div class="lc-pagehead__title">Agenda</div>
            <div class="lc-pagehead__meta">
                <span class="lc-badge lc-badge--primary"><?= htmlspecialchars($dateDisplay, ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!$isProfessional && $professionalId > 0 && isset($profMap[$professionalId])): ?>
                    <span class="lc-badge"><?= htmlspecialchars((string)$profMap[$professionalId]['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php elseif (!$isProfessional && $professionalId === 0): ?>
                    <span class="lc-badge">Todos os profissionais</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="lc-pagehead__actions">
            <div class="lc-segmented" role="tablist" aria-label="Visão da agenda">
                <a class="lc-segmented__item <?= $view === 'day' ? 'lc-segmented__item--active' : '' ?>" role="tab" aria-selected="<?= $view === 'day' ? 'true' : 'false' ?>" href="/schedule?view=day&date=<?= urlencode($date) ?><?= $professionalId > 0 ? ('&professional_id=' . (int)$professionalId) : '' ?>">Dia</a>
                <a class="lc-segmented__item <?= $view === 'week' ? 'lc-segmented__item--active' : '' ?>" role="tab" aria-selected="<?= $view === 'week' ? 'true' : 'false' ?>" href="/schedule?view=week&date=<?= urlencode($date) ?><?= $professionalId > 0 ? ('&professional_id=' . (int)$professionalId) : '' ?>">Semana</a>
                <a class="lc-segmented__item <?= $view === 'month' ? 'lc-segmented__item--active' : '' ?>" role="tab" aria-selected="<?= $view === 'month' ? 'true' : 'false' ?>" href="/schedule?view=month&date=<?= urlencode($date) ?><?= $professionalId > 0 ? ('&professional_id=' . (int)$professionalId) : '' ?>">Mês</a>
            </div>

            <?php if ($can('scheduling.ops')): ?>
                <a class="lc-btn lc-btn--secondary" href="/schedule/ops?date=<?= urlencode($date) ?>">Operação</a>
            <?php endif; ?>

            <?php if (!$isProfessional && $can('scheduling.create')): ?>
                <button class="lc-btn lc-btn--primary" type="button" id="openCreateAppointment" data-open-modal="createAppointmentModal">Novo agendamento</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($error) && $error !== ''): ?>
        <div class="lc-alert lc-alert--danger">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (isset($created) && $created !== ''): ?>
        <div class="lc-alert lc-alert--success">
            Agendamento criado. ID: <?= htmlspecialchars($created, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="lc-card lc-card--soft lc-toolbar">
        <div class="lc-card__header">
            <div class="lc-card__title">Filtros</div>
        </div>
        <div class="lc-card__body">
            <form method="get" action="/schedule" class="lc-toolbar__form">
                <input type="hidden" name="view" value="day" />
                <input type="hidden" name="per_page" value="<?= (int)$perPage ?>" />
                <input type="hidden" name="page" value="1" />

                <div class="lc-field">
                    <label class="lc-label">Data</label>
                    <input class="lc-input" id="filter_date" type="date" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                </div>

                <?php if (!$isProfessional): ?>
                    <div class="lc-field lc-field--wide">
                        <label class="lc-label">Profissional</label>
                        <select class="lc-select" name="professional_id">
                            <option value="0">Todos</option>
                            <?php foreach ($professionals as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$professionalId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="professional_id" value="<?= (int)$professionalId ?>" />
                <?php endif; ?>

                <div class="lc-toolbar__actions">
                    <button class="lc-btn lc-btn--primary" type="submit">Aplicar filtros</button>
                </div>
            </form>
        </div>
    </div>

    <div class="lc-card" style="margin-bottom:14px;">
        <div class="lc-card__title">Agendamentos</div>
        <div class="lc-card__actions">
            <span class="lc-muted">Página <?= (int)$page ?></span>
        </div>
        <div class="lc-card__body">
    <?php if ($items === []): ?>
        <div class="lc-muted">Nenhum agendamento.</div>
    <?php else: ?>
        <div>
            <table class="lc-table">
            <thead>
            <tr>
                <th>Início</th>
                <th>Fim</th>
                <th>Paciente</th>
                <th>Profissional</th>
                <th>Serviço</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <?php
                    $pid = (int)$it['professional_id'];
                    $sid = (int)$it['service_id'];
                    $pname = isset($profMap[$pid]) ? (string)$profMap[$pid]['name'] : ('#' . $pid);
                    $sname = isset($svcMap[$sid]) ? (string)$svcMap[$sid]['name'] : ('#' . $sid);
                    $patientName = trim((string)($it['patient_name'] ?? ''));
                    $status = (string)$it['status'];
                    $statusClass = isset($statusClassMap[$status]) ? (string)$statusClassMap[$status] : 'scheduled';

                    $checkedInAt = isset($it['checked_in_at']) ? (string)$it['checked_in_at'] : '';
                    $startedAt = isset($it['started_at']) ? (string)$it['started_at'] : '';

                    $statusLabelMap = [
                        'scheduled' => 'Agendado',
                        'confirmed' => 'Confirmado',
                        'in_progress' => 'Em atendimento',
                        'completed' => 'Concluído',
                        'no_show' => 'Faltou',
                        'cancelled' => 'Cancelado',
                    ];
                    $statusLabel = $statusLabelMap[$status] ?? $status;

                    $canConfirm = in_array($status, ['scheduled'], true);
                    $canCheckIn = in_array($status, ['scheduled', 'confirmed', 'in_progress'], true) && $checkedInAt === '';
                    $canStart = in_array($status, ['scheduled', 'confirmed'], true) && $startedAt === '';
                    $canInProgress = in_array($status, ['scheduled', 'confirmed', 'completed'], true);
                    $canComplete = in_array($status, ['in_progress'], true);
                    $canNoShow = in_array($status, ['scheduled', 'confirmed', 'in_progress'], true);
                ?>
                <tr>
                    <td><?= htmlspecialchars(substr((string)$it['start_at'], 11, 5), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(substr((string)$it['end_at'], 11, 5), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $patientName !== '' ? htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') : '<span class="lc-muted">-</span>' ?></td>
                    <td><?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($sname, ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="lc-badge lc-badge--status lc-badge--status-<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string)$statusLabel, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="lc-td-actions">
                        <div class="lc-actions lc-actions--compact">
                            <?php if ($can('scheduling.update')): ?>
                                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/schedule/reschedule?id=<?= (int)$it['id'] ?>">Reagendar</a>
                            <?php endif; ?>

                            <details class="lc-actions__more">
                                <summary class="lc-btn lc-btn--secondary lc-btn--sm">Ações</summary>
                                <div class="lc-actions__menu">
                                    <?php if ($canConfirm && $can('scheduling.finalize')): ?>
                                        <form method="post" action="/schedule/status">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                            <input type="hidden" name="status" value="confirmed" />
                                            <input type="hidden" name="view" value="day" />
                                            <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="professional_id" value="<?= (int)$professionalId ?>" />
                                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Confirmar</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($canCheckIn && ($can('scheduling.finalize') || (!$isProfessional && $can('scheduling.update')))): ?>
                                        <form method="post" action="/schedule/check-in">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                            <input type="hidden" name="view" value="day" />
                                            <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="professional_id" value="<?= (int)$professionalId ?>" />
                                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Chegou (check-in)</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($canStart && $can('scheduling.finalize')): ?>
                                        <form method="post" action="/schedule/start">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                            <input type="hidden" name="view" value="day" />
                                            <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="professional_id" value="<?= (int)$professionalId ?>" />
                                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Iniciar atendimento</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($canInProgress && $can('scheduling.finalize')): ?>
                                        <form method="post" action="/schedule/status">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                            <input type="hidden" name="status" value="in_progress" />
                                            <input type="hidden" name="view" value="day" />
                                            <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="professional_id" value="<?= (int)$professionalId ?>" />
                                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit"><?= $status === 'completed' ? 'Reabrir' : 'Atender' ?></button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($canComplete && $can('scheduling.finalize')): ?>
                                        <form method="post" action="/schedule/status">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                            <input type="hidden" name="status" value="completed" />
                                            <input type="hidden" name="view" value="day" />
                                            <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="professional_id" value="<?= (int)$professionalId ?>" />
                                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Concluir</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($canNoShow && $can('scheduling.finalize')): ?>
                                        <form method="post" action="/schedule/status">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                            <input type="hidden" name="status" value="no_show" />
                                            <input type="hidden" name="view" value="day" />
                                            <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="professional_id" value="<?= (int)$professionalId ?>" />
                                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" title="Marque como Faltou quando o paciente não compareceu.">Faltou</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($can('scheduling.cancel')): ?>
                                        <form method="post" action="/schedule/cancel">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Cancelar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </details>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    <?php endif; ?>

                <div class="lc-pager">
                    <div></div>
                    <div class="lc-pager__actions">
                        <?php if ($page > 1): ?>
                            <a class="lc-btn lc-btn--secondary" href="/schedule?view=day&date=<?= urlencode((string)$date) ?><?= $professionalId>0 ? ('&professional_id=' . (int)$professionalId) : '' ?>&per_page=<?= (int)$perPage ?>&page=<?= (int)($page - 1) ?>">Anterior</a>
                        <?php endif; ?>
                        <?php if ($hasNext): ?>
                            <a class="lc-btn lc-btn--secondary" href="/schedule?view=day&date=<?= urlencode((string)$date) ?><?= $professionalId>0 ? ('&professional_id=' . (int)$professionalId) : '' ?>&per_page=<?= (int)$perPage ?>&page=<?= (int)($page + 1) ?>">Próxima</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (is_array($blocks) && $blocks !== []): ?>
        <div class="lc-card" style="margin-bottom:14px;">
            <div class="lc-card__title">Bloqueios do dia</div>
            <div class="lc-card__body">
                <?php foreach ($blocks as $b): ?>
                    <?php
                        $bst = (string)($b['start_at'] ?? '');
                        $ben = (string)($b['end_at'] ?? '');
                        $reason = trim((string)($b['reason'] ?? ''));
                        $type = trim((string)($b['type'] ?? ''));
                    ?>
                    <div class="lc-muted" style="margin-bottom:6px;">
                        <strong><?= htmlspecialchars($bst !== '' ? substr($bst, 11, 5) : '', ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($ben !== '' ? substr($ben, 11, 5) : '', ENT_QUOTES, 'UTF-8') ?></strong>
                        <?= $reason !== '' ? (' • ' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8')) : '' ?>
                        <?= $type !== '' ? (' (' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . ')') : '' ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="lc-card lc-card--soft">
        <?php
            $canRenderGrid = is_array($slotMinutes) && $slotMinutes !== [];
            $isClosed = array_key_exists((string)$date, $closedMap);

            $toMinutes = static function (string $hhmm): int {
                $t = trim($hhmm);
                if (preg_match('/^(\d{2}):(\d{2})/', $t, $m) !== 1) {
                    return 0;
                }
                return ((int)$m[1]) * 60 + ((int)$m[2]);
            };
            $fromMinutes = static function (int $mins): string {
                $h = (int)floor($mins / 60);
                $m = $mins % 60;
                return str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
            };
            $dtToMinutes = static function (string $dt) use ($toMinutes): int {
                $t = substr($dt, 11, 5);
                return $toMinutes($t);
            };

            $minuteIndex = [];
            foreach ($slotMinutes as $idx => $m) {
                $minuteIndex[(int)$m] = (int)$idx;
            }
            $rowHeight = 42;
        ?>

        <?php if ($canRenderGrid): ?>
            <div class="lc-card" style="margin-bottom:14px;">
                <div class="lc-card__title">Horários do dia</div>
                <div class="lc-card__body" style="padding:0; overflow:auto; max-height: calc(100vh - 360px);">
                    <div style="min-width: 560px;">
                        <div class="lc-grid" style="grid-template-columns: 90px minmax(0, 1fr); gap:10px; padding:12px;">
                            <div></div>
                            <div class="lc-card" style="margin:0; border-left: 3px solid #2563eb; background: <?= $isClosed ? 'rgba(239,68,68,0.08)' : 'var(--lc-surface)' ?>;">
                                <div class="lc-card__body" style="padding:10px;">
                                    <div style="font-weight:700;"><?= htmlspecialchars((string)$date, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if ($isClosed): ?>
                                        <div class="lc-muted" style="font-size:12px; margin-top:6px;">Feriado/Recesso<?= trim((string)$closedMap[(string)$date]) !== '' ? (': ' . htmlspecialchars((string)$closedMap[(string)$date], ENT_QUOTES, 'UTF-8')) : '' ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div style="position: sticky; left: 0; background: var(--lc-surface);">
                                <?php foreach ($slotMinutes as $mins): ?>
                                    <div class="lc-muted" style="font-size:12px; height: <?= (int)$rowHeight ?>px; display:flex; align-items:center; margin-bottom:10px; padding:6px;">
                                        <?= htmlspecialchars($fromMinutes((int)$mins), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div style="position:relative; background: <?= $isClosed ? 'rgba(239,68,68,0.05)' : 'transparent' ?>; border-radius: 10px;">
                                <?php foreach ($slotMinutes as $mins): ?>
                                    <?php $slotTime = $fromMinutes((int)$mins); ?>
                                    <div style="height: <?= (int)$rowHeight ?>px; border:1px solid rgba(17,24,39,0.06); border-radius:10px; margin-bottom:10px; padding:6px; <?= (!$isProfessional && !$isClosed && $can('scheduling.create')) ? 'cursor:pointer;' : '' ?>"
                                        <?= (!$isProfessional && !$isClosed && $can('scheduling.create'))
                                            ? ('data-open-create="1" data-create-date="' . htmlspecialchars((string)$date, ENT_QUOTES, 'UTF-8') . '" data-create-time="' . htmlspecialchars($slotTime, ENT_QUOTES, 'UTF-8') . '" data-create-professional-id="' . (int)$professionalId . '"')
                                            : '' ?>
                                    ></div>
                                <?php endforeach; ?>

                                <?php if (is_array($blocks) && $blocks !== []): ?>
                                    <?php foreach ($blocks as $bl): ?>
                                        <?php
                                            $bst = (string)($bl['start_at'] ?? '');
                                            $ben = (string)($bl['end_at'] ?? '');
                                            if ($bst === '' || $ben === '') {
                                                continue;
                                            }
                                            $bStart = $dtToMinutes($bst);
                                            $bEnd = $dtToMinutes($ben);
                                            if ($bEnd <= $bStart) {
                                                continue;
                                            }
                                            $startIdx = null;
                                            for ($mm = $bStart; $mm >= 0; $mm -= 15) {
                                                if (isset($minuteIndex[$mm])) {
                                                    $startIdx = (int)$minuteIndex[$mm];
                                                    break;
                                                }
                                            }
                                            if ($startIdx === null) {
                                                continue;
                                            }
                                            $span = (int)ceil(($bEnd - $bStart) / 15);
                                            $top = $startIdx * ($rowHeight + 10);
                                            $height = max(28, ($span * $rowHeight) + (($span - 1) * 10));
                                            $reason = trim((string)($bl['reason'] ?? ''));
                                        ?>
                                        <div
                                            style="position:absolute; left:6px; right:6px; top: <?= (int)$top ?>px; height: <?= (int)$height ?>px; padding:8px 10px; margin:0; width:auto; text-align:left; overflow:hidden; border:1px dashed rgba(17,24,39,0.25); border-radius: 10px; background: rgba(17,24,39,0.06); z-index:4;"
                                            title="<?= htmlspecialchars($reason !== '' ? $reason : 'Bloqueado', ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            <div style="font-weight:700; font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Bloqueado<?= $reason !== '' ? (': ' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8')) : '' ?></div>
                                            <div class="lc-muted" style="font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                <?= htmlspecialchars(substr($bst, 11, 5), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars(substr($ben, 11, 5), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <?php if (is_array($items) && $items !== []): ?>
                                    <?php foreach ($items as $it): ?>
                                        <?php
                                            $st = (string)($it['start_at'] ?? '');
                                            $en = (string)($it['end_at'] ?? '');
                                            if ($st === '' || $en === '') {
                                                continue;
                                            }
                                            $apptStart = $dtToMinutes($st);
                                            $apptEnd = $dtToMinutes($en);
                                            if ($apptEnd <= $apptStart) {
                                                continue;
                                            }
                                            if (!isset($minuteIndex[$apptStart])) {
                                                continue;
                                            }
                                            $startIdx = (int)$minuteIndex[$apptStart];
                                            $span = (int)ceil(($apptEnd - $apptStart) / 15);
                                            $top = $startIdx * ($rowHeight + 10);
                                            $height = max(36, ($span * $rowHeight) + (($span - 1) * 10));

                                            $sid = (int)($it['service_id'] ?? 0);
                                            $serviceName = isset($svcMap[$sid]) ? (string)($svcMap[$sid]['name'] ?? '') : '';
                                            $pid = (int)($it['professional_id'] ?? 0);
                                            $professionalName = isset($profMap[$pid]) ? (string)($profMap[$pid]['name'] ?? '') : '';
                                            $status = (string)($it['status'] ?? '');
                                            $statusClass = isset($statusClassMap[$status]) ? (string)$statusClassMap[$status] : 'scheduled';
                                        ?>
                                        <button type="button"
                                             class="lc-statusbar lc-statusbar--<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"
                                             data-appointment-id="<?= (int)($it['id'] ?? 0) ?>"
                                             style="position:absolute; left:6px; right:6px; top: <?= (int)$top ?>px; height: <?= (int)$height ?>px; padding:8px 10px; margin:0; width:auto; text-align:left; overflow:hidden; border:0; z-index:5; cursor:pointer;">
                                            <div style="font-weight:700; font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Agendamento #<?= (int)($it['id'] ?? 0) ?></div>
                                            <div class="lc-muted" style="font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                <?= htmlspecialchars(substr($st, 11, 5), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars(substr($en, 11, 5), ENT_QUOTES, 'UTF-8') ?>
                                                <?= $professionalName !== '' ? (' • ' . htmlspecialchars($professionalName, ENT_QUOTES, 'UTF-8')) : '' ?>
                                                <?= $serviceName !== '' ? (' • ' . htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8')) : '' ?>
                                            </div>
                                        </button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$isProfessional && $can('scheduling.create')): ?>
        <div class="lc-modal" id="createAppointmentModal" aria-hidden="true">
            <div class="lc-modal__backdrop" data-close-modal></div>
            <div class="lc-modal__panel" role="dialog" aria-modal="true" aria-label="Novo agendamento">
                <div class="lc-modal__header">
                    <div>
                        <div class="lc-modal__title">Novo agendamento</div>
                        <div class="lc-modal__subtitle">Preencha os dados para agendar.</div>
                    </div>
                    <button class="lc-btn lc-btn--secondary lc-btn--sm" type="button" data-close-modal>Fechar</button>
                </div>

                <form method="post" action="/schedule/create" class="lc-modal__body">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="return_date" value="<?= htmlspecialchars((string)$date, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="return_view" value="<?= htmlspecialchars((string)$view, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="return_professional_id" value="<?= (int)$professionalId ?>" />

                    <div class="lc-field" style="grid-column: 1 / -1; position:relative;">
                        <label class="lc-label">Paciente</label>
                        <input class="lc-input" type="text" id="patientSearch" placeholder="Buscar por nome, e-mail ou telefone" autocomplete="off" required />
                        <input type="hidden" name="patient_id" id="patient_id" value="" />
                        <div class="lc-autocomplete" id="patientResults" style="display:none;"></div>
                        <div class="lc-muted" id="patientHint" style="margin-top:6px; display:none;">Selecione um paciente da lista.</div>
                    </div>

                    <div class="lc-field">
                        <label class="lc-label">Serviço</label>
                        <select class="lc-select" name="service_id" id="modal_service_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($services as $s): ?>
                                <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars((string)$s['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="lc-field">
                        <label class="lc-label">Profissional</label>
                        <select class="lc-select" name="professional_id" id="modal_professional_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($professionals as $p): ?>
                                <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="lc-field">
                        <label class="lc-label">Data</label>
                        <input class="lc-input" type="date" name="_modal_date" id="modal_date" value="<?= htmlspecialchars((string)$date, ENT_QUOTES, 'UTF-8') ?>" />
                    </div>

                    <div class="lc-field">
                        <label class="lc-label">Horário</label>
                        <select class="lc-select" name="start_at" id="modal_start_at" required>
                            <option value="">Selecione um serviço + profissional + data</option>
                        </select>
                    </div>

                    <div class="lc-field">
                        <label class="lc-label">Observações (opcional)</label>
                        <input class="lc-input" type="text" name="notes" placeholder="" />
                    </div>

                    <div class="lc-field">
                        <label class="lc-label">Etapa do funil (opcional)</label>
                        <select class="lc-select" name="funnel_stage_id">
                            <option value="">(opcional)</option>
                            <?php foreach (($funnelStages ?? []) as $st): ?>
                                <option value="<?= (int)($st['id'] ?? 0) ?>"><?= htmlspecialchars((string)($st['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="lc-modal__footer">
                        <button class="lc-btn lc-btn--primary" type="submit">Agendar</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="lc-modal" id="appointmentDetailsModal" aria-hidden="true">
        <div class="lc-modal__backdrop" data-close-modal></div>
        <div class="lc-modal__panel" role="dialog" aria-modal="true" aria-label="Detalhes do agendamento">
            <div class="lc-modal__header">
                <div>
                    <div class="lc-modal__title">Detalhes do agendamento</div>
                    <div class="lc-modal__subtitle" id="appointmentDetailsSubtitle">&nbsp;</div>
                </div>
                <button class="lc-btn lc-btn--secondary lc-btn--sm" type="button" data-close-modal>Fechar</button>
            </div>
            <div class="lc-modal__body" id="appointmentDetailsBody">
                <div class="lc-muted">Carregando...</div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
  const dateFilterEl = document.getElementById('filter_date');
  const openBtn = document.getElementById('openCreateAppointment');
  const modal = document.getElementById('createAppointmentModal');
  const patientSearchEl = document.getElementById('patientSearch');
  const patientIdEl = document.getElementById('patient_id');
  const patientResultsEl = document.getElementById('patientResults');
  const patientHintEl = document.getElementById('patientHint');
  const patientPackageEl = document.getElementById('patient_package_id');
  const patientPackageHintEl = document.getElementById('patientPackageHint');
  const modalServiceEl = document.getElementById('modal_service_id');
  const modalProfEl = document.getElementById('modal_professional_id');
  const modalDateEl = document.getElementById('modal_date');
  const modalStartEl = document.getElementById('modal_start_at');

  const detailsModal = document.getElementById('appointmentDetailsModal');
  const detailsBody = document.getElementById('appointmentDetailsBody');
  const detailsSubtitle = document.getElementById('appointmentDetailsSubtitle');

  let desiredSlotTime = '';
  let desiredDate = '';

  function openModal() {
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('lc-modal--open');

    if (modalDateEl && dateFilterEl && dateFilterEl.value) {
      modalDateEl.value = dateFilterEl.value;
    }

    if (modalDateEl && desiredDate) {
      modalDateEl.value = desiredDate;
    }
  }

  function closeModal() {
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'true');
    modal.classList.remove('lc-modal--open');
  }

  function openDetailsModal() {
    if (!detailsModal) return;
    detailsModal.setAttribute('aria-hidden', 'false');
    detailsModal.classList.add('lc-modal--open');
  }

  function closeDetailsModal() {
    if (!detailsModal) return;
    detailsModal.setAttribute('aria-hidden', 'true');
    detailsModal.classList.remove('lc-modal--open');
  }

  if (openBtn && modal) {
    openBtn.addEventListener('click', openModal);
    modal.addEventListener('click', function(e) {
      const t = e.target;
      if (!(t instanceof HTMLElement)) return;
      if (t.hasAttribute('data-close-modal')) {
        closeModal();
      }
    });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeModal();
        closeDetailsModal();
      }
    });
  }

  if (detailsModal) {
    detailsModal.addEventListener('click', function(e) {
      const t = e.target;
      if (!(t instanceof HTMLElement)) return;
      if (t.hasAttribute('data-close-modal')) {
        closeDetailsModal();
      }
    });
  }

  function esc(s) {
    return (s || '').toString()
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function fmtStatus(st) {
    const map = {
      scheduled: 'Agendado',
      confirmed: 'Confirmado',
      in_progress: 'Em atendimento',
      completed: 'Concluído',
      no_show: 'Faltou',
      cancelled: 'Cancelado'
    };
    const key = (st || '').toString();
    return map[key] || key;
  }

  async function loadAppointmentDetails(id) {
    if (!detailsBody) return;
    detailsBody.innerHTML = '<div class="lc-muted">Carregando...</div>';
    if (detailsSubtitle) detailsSubtitle.innerHTML = '&nbsp;';

    const url = '/schedule/details?id=' + encodeURIComponent(String(id || ''));
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    const data = await res.json().catch(function() { return {}; });
    if (!res.ok || !data || data.error) {
      const msg = (data && data.error) ? data.error : 'Erro ao carregar detalhes.';
      detailsBody.innerHTML = '<div class="lc-alert lc-alert--danger">' + esc(msg) + '</div>';
      return;
    }

    const it = data.item || {};
    const title = 'Agendamento #' + String(it.id || '');
    if (detailsSubtitle) {
      const st = (it.start_at || '').toString();
      const en = (it.end_at || '').toString();
      const subtitle = (st ? (st.substring(11, 16)) : '') + (en ? (' - ' + en.substring(11, 16)) : '');
      detailsSubtitle.textContent = subtitle || ' '; 
    }

    detailsBody.innerHTML =
      '<div style="grid-column: 1 / -1; display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:12px; align-items:start;">' +
        '<div class="lc-card" style="margin:0;">' +
          '<div class="lc-card__body">' +
            '<div style="font-weight:850; margin-bottom:10px;">' + esc(title) + '</div>' +
            '<div style="display:grid; grid-template-columns: 1fr; gap:8px;">' +
              '<div><span class="lc-muted">Paciente</span><div style="font-weight:700;">' + esc(it.patient_name || '') + '</div></div>' +
              '<div><span class="lc-muted">Serviço</span><div style="font-weight:700;">' + esc(it.service_name || '') + '</div></div>' +
              '<div><span class="lc-muted">Profissional</span><div style="font-weight:700;">' + esc(it.professional_name || '') + '</div></div>' +
            '</div>' +
          '</div>' +
        '</div>' +

        '<div class="lc-card" style="margin:0;">' +
          '<div class="lc-card__body">' +
            '<div style="display:grid; grid-template-columns: 1fr; gap:8px;">' +
              '<div><span class="lc-muted">Início</span><div style="font-weight:700;">' + esc((it.start_at || '').toString().replace('T', ' ')) + '</div></div>' +
              '<div><span class="lc-muted">Fim</span><div style="font-weight:700;">' + esc((it.end_at || '').toString().replace('T', ' ')) + '</div></div>' +
              '<div><span class="lc-muted">Status</span><div style="font-weight:700;">' + esc(fmtStatus(it.status || '')) + '</div></div>' +
            '</div>' +
          '</div>' +
        '</div>' +

        '<div class="lc-card" style="margin:0; grid-column: 1 / -1;">' +
          '<div class="lc-card__body">' +
            '<div><span class="lc-muted">Observações</span><div style="font-weight:700;">' + esc(it.notes || '') + '</div></div>' +
          '</div>' +
        '</div>' +
      '</div>';
  }

  document.addEventListener('click', function(e) {
    const t = e.target;
    if (!(t instanceof HTMLElement)) return;
    const btn = t.closest('[data-appointment-id]');
    if (!btn) return;
    const id = btn.getAttribute('data-appointment-id');
    if (!id) return;
    openDetailsModal();
    loadAppointmentDetails(id).catch(function() {
      if (detailsBody) detailsBody.innerHTML = '<div class="lc-alert lc-alert--danger">Erro ao carregar detalhes.</div>';
    });
  });

  document.addEventListener('click', function(e) {
    const t = e.target;
    if (!(t instanceof HTMLElement)) return;
    const btn = t.closest('[data-open-modal]');
    if (!btn) return;
    const id = btn.getAttribute('data-open-modal');
    if (id !== 'createAppointmentModal') return;
    openModal();
  });

  if (!modalServiceEl || !modalProfEl || !modalStartEl || !modalDateEl) {
    return;
  }

  function clearPatientSelection() {
    if (patientIdEl) patientIdEl.value = '';
  }

  function hidePatientResults() {
    if (!patientResultsEl) return;
    patientResultsEl.style.display = 'none';
    patientResultsEl.innerHTML = '';
  }

  function setPackagesLoading() {
    if (!patientPackageEl) return;
    patientPackageEl.innerHTML = '<option value="">Carregando...</option>';
  }

  function setPackagesEmpty() {
    if (!patientPackageEl) return;
    patientPackageEl.innerHTML = '<option value="">(sem pacote)</option>';
  }

  function showPackageHint(show) {
    if (!patientPackageHintEl) return;
    patientPackageHintEl.style.display = show ? 'block' : 'none';
  }

  async function loadPatientPackages(patientId) {
    if (!patientPackageEl) return;
    if (!patientId) {
      setPackagesEmpty();
      showPackageHint(true);
      return;
    }

    setPackagesLoading();
    showPackageHint(false);

    const url = `/patients/packages-json?patient_id=${encodeURIComponent(patientId)}&limit=50`;
    let data = null;
    try {
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) {
        setPackagesEmpty();
        return;
      }
      data = await res.json();
    } catch (e) {
      setPackagesEmpty();
      return;
    }

    const items = (data && Array.isArray(data.items)) ? data.items : [];
    if (items.length === 0) {
      setPackagesEmpty();
      return;
    }

    patientPackageEl.innerHTML = '<option value="">(sem pacote)</option>';
    for (const it of items) {
      const opt = document.createElement('option');
      const name = (it.package_name || '').toString();
      const remaining = (it.remaining_sessions !== undefined && it.remaining_sessions !== null) ? String(it.remaining_sessions) : '';
      opt.value = String(it.id || '');
      opt.textContent = remaining !== '' ? `${name} (${remaining} restantes)` : name;
      patientPackageEl.appendChild(opt);
    }
  }

  function showPatientHint(show) {
    if (!patientHintEl) return;
    patientHintEl.style.display = show ? 'block' : 'none';
  }

  async function searchPatients(q) {
    const url = `/patients/search-json?q=${encodeURIComponent(q)}&limit=10`;
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) return [];
    const data = await res.json();
    return (data && data.items) ? data.items : [];
  }

  let patientTimer = null;
  if (patientSearchEl && patientIdEl && patientResultsEl) {
    patientSearchEl.addEventListener('input', function() {
      clearPatientSelection();
      if (patientPackageEl) {
        setPackagesEmpty();
        showPackageHint(true);
      }
      const q = (patientSearchEl.value || '').trim();
      hidePatientResults();
      showPatientHint(q.length > 0);

      if (patientTimer) window.clearTimeout(patientTimer);
      if (q.length < 2) {
        return;
      }
      patientTimer = window.setTimeout(async function() {
        let items = [];
        try {
          items = await searchPatients(q);
        } catch (e) {
          items = [];
        }

        if (!Array.isArray(items) || items.length === 0) {
          hidePatientResults();
          return;
        }

        patientResultsEl.innerHTML = '';
        for (const it of items) {
          const row = document.createElement('button');
          row.type = 'button';
          row.className = 'lc-autocomplete__item';
          const name = (it.name || '').toString();
          const meta = [it.phone, it.email].filter(Boolean).join(' · ');
          row.innerHTML = `<div class="lc-autocomplete__name"></div><div class="lc-autocomplete__meta"></div>`;
          const nameEl = row.querySelector('.lc-autocomplete__name');
          const metaEl = row.querySelector('.lc-autocomplete__meta');
          if (nameEl) nameEl.textContent = name;
          if (metaEl) metaEl.textContent = meta;
          row.addEventListener('click', function() {
            patientIdEl.value = String(it.id || '');
            patientSearchEl.value = name;
            hidePatientResults();
            showPatientHint(false);
            loadPatientPackages(patientIdEl.value);
          });
          patientResultsEl.appendChild(row);
        }
        patientResultsEl.style.display = 'block';
      }, 250);
    });

    patientSearchEl.addEventListener('blur', function() {
      window.setTimeout(function() {
        if (!patientIdEl.value) {
          showPatientHint((patientSearchEl.value || '').trim() !== '');
        }
        hidePatientResults();
      }, 150);
    });

    patientSearchEl.addEventListener('focus', function() {
      if (!patientIdEl.value && (patientSearchEl.value || '').trim() !== '') {
        showPatientHint(true);
      }
    });
  }

  if (modal) {
    const form = modal.querySelector('form');
    if (form) {
      form.addEventListener('submit', function(e) {
        if (patientIdEl && String(patientIdEl.value || '').trim() === '') {
          e.preventDefault();
          showPatientHint(true);
          if (patientSearchEl) patientSearchEl.focus();
        }
      });
    }
  }

  async function loadSlots() {
    const serviceId = modalServiceEl.value;
    const profId = modalProfEl.value;
    const date = modalDateEl ? modalDateEl.value : '';
    modalStartEl.innerHTML = '<option value="">Carregando...</option>';

    if (!serviceId || !profId || !date) {
      modalStartEl.innerHTML = '<option value="">Selecione um serviço + profissional + data</option>';
      return;
    }

    const url = `/schedule/available?service_id=${encodeURIComponent(serviceId)}&professional_id=${encodeURIComponent(profId)}&date=${encodeURIComponent(date)}`;

    let data = null;
    try {
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) {
        modalStartEl.innerHTML = '<option value="">Erro ao carregar horários</option>';
        return;
      }
      data = await res.json();
    } catch (e) {
      modalStartEl.innerHTML = '<option value="">Erro ao carregar horários</option>';
      return;
    }

    const slots = data.slots || [];
    if (slots.length === 0) {
      modalStartEl.innerHTML = '<option value="">Sem horários disponíveis</option>';
      return;
    }

    modalStartEl.innerHTML = '<option value="">Selecione</option>';
    let foundDesired = false;
    for (const s of slots) {
      const t = (s.start_at || '').slice(11, 16);
      const opt = document.createElement('option');
      opt.value = s.start_at;
      opt.textContent = t;
      if (desiredSlotTime && t === desiredSlotTime) {
        opt.selected = true;
        foundDesired = true;
      }
      modalStartEl.appendChild(opt);
    }
    if (foundDesired) {
      desiredSlotTime = '';
      desiredDate = '';
    }
  }

  modalServiceEl.addEventListener('change', loadSlots);
  modalProfEl.addEventListener('change', loadSlots);

  modalDateEl.addEventListener('change', loadSlots);

  document.addEventListener('click', function(e) {
    const t = e.target;
    if (!(t instanceof HTMLElement)) return;
    const createCell = t.closest('[data-open-create]');
    if (!createCell || !modal) return;
    const d = createCell.getAttribute('data-create-date') || '';
    const tm = createCell.getAttribute('data-create-time') || '';
    const pid = parseInt(createCell.getAttribute('data-create-professional-id') || '0', 10);

    desiredDate = d;
    desiredSlotTime = tm;

    if (modalProfEl && pid > 0 && String(modalProfEl.value || '') !== String(pid)) {
      modalProfEl.value = String(pid);
    }

    openModal();
    loadSlots();
  });
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
