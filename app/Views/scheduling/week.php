<?php
/** @var string $date */
/** @var string $view */
/** @var int $professional_id */
/** @var string $created */
/** @var string $error */
/** @var string $week_start */
/** @var int $week_start_weekday */
/** @var list<array<string,mixed>> $working_hours */
/** @var list<array<string,mixed>> $closed_days */
/** @var list<array<string,mixed>> $items */
/** @var list<array<string,mixed>> $professionals */
/** @var list<array<string,mixed>> $services */
$isProfessional = isset($is_professional) ? (bool)$is_professional : false;
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Agenda (Semana)';

$weekdayNames = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

$svcMap = [];
foreach ($services as $s) {
    $svcMap[(int)$s['id']] = $s;
}

$profMap = [];
foreach ($professionals as $p) {
    $profMap[(int)$p['id']] = $p;
}

$byDay = [];
foreach ($items as $it) {
    $d = substr((string)$it['start_at'], 0, 10);
    if (!isset($byDay[$d])) {
        $byDay[$d] = [];
    }
    $byDay[$d][] = $it;
}

$whByWeekday = [];
if (isset($working_hours) && is_array($working_hours)) {
    foreach ($working_hours as $wh) {
        $wd = (int)($wh['weekday'] ?? -1);
        if ($wd < 0 || $wd > 6) {
            continue;
        }
        if (!isset($whByWeekday[$wd])) {
            $whByWeekday[$wd] = [];
        }
        $whByWeekday[$wd][] = $wh;
    }
}

$closedMap = [];
if (isset($closed_days) && is_array($closed_days)) {
    foreach ($closed_days as $cd) {
        $ymd = (string)($cd['closed_date'] ?? '');
        if ($ymd === '') {
            continue;
        }
        $closedMap[$ymd] = (string)($cd['reason'] ?? '');
    }
}

/** @return int */
$toMinutes = static function (string $hhmm): int {
    $t = trim($hhmm);
    if (preg_match('/^(\d{2}):(\d{2})/', $t, $m) !== 1) {
        return 0;
    }
    return ((int)$m[1]) * 60 + ((int)$m[2]);
};

/** @return string */
$fromMinutes = static function (int $mins): string {
    $h = (int)floor($mins / 60);
    $m = $mins % 60;
    return str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
};

// Determine week window
$weekStart = \DateTimeImmutable::createFromFormat('Y-m-d', $week_start);

// Build a global slot list based on working hours for the 7 days
$slotMinutes = [];
if ($weekStart !== false) {
    for ($i = 0; $i < 7; $i++) {
        $d = $weekStart->modify('+' . $i . ' days');
        $wd = (int)$d->format('w');
        $windows = $whByWeekday[$wd] ?? [];
        foreach ($windows as $w) {
            $startM = $toMinutes((string)($w['start_time'] ?? ''));
            $endM = $toMinutes((string)($w['end_time'] ?? ''));
            if ($endM <= $startM) {
                continue;
            }
            for ($m = $startM; $m < $endM; $m += 30) {
                $slotMinutes[$m] = true;
            }
        }
    }
}
$slotMinutes = array_keys($slotMinutes);
sort($slotMinutes);

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-card lc-statusbar lc-statusbar--no_show" style="margin-bottom: 16px;">
        <div class="lc-card__body"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if (isset($created) && $created !== ''): ?>
    <div class="lc-card lc-statusbar lc-statusbar--completed" style="margin-bottom: 16px;">
        <div class="lc-card__body">Atualizado. ID: <?= htmlspecialchars($created, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="lc-schedule">
    <div class="lc-pagehead">
        <div>
            <div class="lc-pagehead__title">Agenda</div>
            <div class="lc-pagehead__meta">
                <span class="lc-badge lc-badge--primary"><?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!$isProfessional && (int)$professional_id > 0 && isset($profMap[(int)$professional_id])): ?>
                    <span class="lc-badge"><?= htmlspecialchars((string)$profMap[(int)$professional_id]['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php elseif (!$isProfessional && (int)$professional_id === 0): ?>
                    <span class="lc-badge">Todos os profissionais</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="lc-pagehead__actions">
            <div class="lc-segmented" role="tablist" aria-label="Visão da agenda">
                <a class="lc-segmented__item" role="tab" aria-selected="false" href="/schedule?view=day&date=<?= urlencode($date) ?><?= ((int)$professional_id > 0) ? ('&professional_id=' . (int)$professional_id) : '' ?>">Dia</a>
                <a class="lc-segmented__item lc-segmented__item--active" role="tab" aria-selected="true" href="/schedule?view=week&date=<?= urlencode($date) ?><?= ((int)$professional_id > 0) ? ('&professional_id=' . (int)$professional_id) : '' ?>">Semana</a>
                <a class="lc-segmented__item" role="tab" aria-selected="false" href="/schedule?view=month&date=<?= urlencode($date) ?><?= ((int)$professional_id > 0) ? ('&professional_id=' . (int)$professional_id) : '' ?>">Mês</a>
            </div>

            <a class="lc-btn lc-btn--secondary" href="/schedule/ops?date=<?= urlencode($date) ?>">Operação</a>
        </div>
    </div>

<div class="lc-card" style="margin-bottom:16px;">
    <div class="lc-card__body">
        <form method="get" action="/schedule" class="lc-form lc-flex lc-gap-md lc-flex--wrap" style="align-items:end;">
            <input type="hidden" name="view" value="week" />

            <div class="lc-field">
                <label class="lc-label">Semana de</label>
                <input class="lc-input" type="date" id="filter_date" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <?php if (!$isProfessional): ?>
                <div class="lc-field" style="min-width: 280px;">
                    <label class="lc-label">Profissional</label>
                    <select class="lc-select" name="professional_id">
                        <option value="0">Todos</option>
                        <?php foreach ($professionals as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$professional_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
            <?php endif; ?>

            <div>
                <button class="lc-btn" type="submit">Aplicar</button>
            </div>
        </form>
    </div>
</div>

<?php if ($weekStart !== false): ?>
    <?php
        $today = date('Y-m-d');
        $days = [];
        for ($i=0; $i<7; $i++) {
            $d = $weekStart->modify('+' . $i . ' days');
            $ymd = $d->format('Y-m-d');
            $wd = (int)$d->format('w');
            $isClosed = array_key_exists($ymd, $closedMap);
            $hasWorking = isset($whByWeekday[$wd]) && is_array($whByWeekday[$wd]) && $whByWeekday[$wd] !== [];
            $hasAppointments = isset($byDay[$ymd]) && is_array($byDay[$ymd]) && $byDay[$ymd] !== [];
            $days[] = [
                'd' => $d,
                'ymd' => $ymd,
                'wd' => $wd,
                'is_closed' => $isClosed,
                'closed_reason' => $isClosed ? (string)$closedMap[$ymd] : '',
                'has_working' => $hasWorking,
                'has_appointments' => $hasAppointments,
            ];
        }
    ?>

    <div class="lc-card">
        <div class="lc-card__header">Semana</div>
        <div class="lc-card__body" style="padding:0; overflow:auto;">
            <div style="min-width: 980px;">
                <div class="lc-grid" style="grid-template-columns: 90px repeat(7, minmax(0, 1fr)); gap:10px; padding:12px;">
                    <div></div>
                    <?php foreach ($days as $day): ?>
                        <?php
                            $ymd = (string)$day['ymd'];
                            $wd = (int)$day['wd'];
                            $border = $ymd === $today ? '3px solid #2563eb' : '1px solid rgba(17,24,39,0.10)';
                            $bg = 'var(--lc-surface)';
                            if ((bool)$day['is_closed']) {
                                $bg = 'rgba(239,68,68,0.08)';
                            } elseif (!(bool)$day['has_working']) {
                                $bg = 'rgba(17,24,39,0.04)';
                            } elseif (!(bool)$day['has_appointments']) {
                                $bg = 'rgba(37,99,235,0.06)';
                            }
                        ?>
                        <div class="lc-card" style="margin:0; border-left: <?= htmlspecialchars($border, ENT_QUOTES, 'UTF-8') ?>; background: <?= htmlspecialchars($bg, ENT_QUOTES, 'UTF-8') ?>;">
                            <div class="lc-card__body" style="padding:10px;">
                                <div style="font-weight:700;">
                                    <?= htmlspecialchars($weekdayNames[$wd], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="lc-muted" style="font-size:12px;">
                                    <?= htmlspecialchars($ymd, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <?php if ((bool)$day['is_closed']): ?>
                                    <div class="lc-muted" style="font-size:12px; margin-top:6px;">
                                        Feriado/Recesso<?= $day['closed_reason'] !== '' ? (': ' . htmlspecialchars($day['closed_reason'], ENT_QUOTES, 'UTF-8')) : '' ?>
                                    </div>
                                <?php elseif (!(bool)$day['has_working']): ?>
                                    <div class="lc-muted" style="font-size:12px; margin-top:6px;">Sem atendimento</div>
                                <?php elseif (!(bool)$day['has_appointments']): ?>
                                    <div class="lc-muted" style="font-size:12px; margin-top:6px;">Dia livre</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($slotMinutes as $mins): ?>
                        <div class="lc-muted" style="font-size:12px; padding:8px 0; position:sticky; left:0; background: var(--lc-surface);">
                            <?= htmlspecialchars($fromMinutes((int)$mins), ENT_QUOTES, 'UTF-8') ?>
                        </div>

                        <?php foreach ($days as $day): ?>
                            <?php
                                $ymd = (string)$day['ymd'];
                                $slotTime = $fromMinutes((int)$mins);
                                $slotKey = $ymd . ' ' . $slotTime;

                                $dayItems = $byDay[$ymd] ?? [];
                                $apptsAtSlot = [];
                                if (is_array($dayItems) && $dayItems !== []) {
                                    foreach ($dayItems as $it) {
                                        $st = (string)($it['start_at'] ?? '');
                                        if (substr($st, 0, 16) === $slotKey) {
                                            $apptsAtSlot[] = $it;
                                        }
                                    }
                                }

                                $cellBg = 'transparent';
                                if ((bool)$day['is_closed']) {
                                    $cellBg = 'rgba(239,68,68,0.05)';
                                } elseif (!(bool)$day['has_working']) {
                                    $cellBg = 'rgba(17,24,39,0.03)';
                                }
                            ?>
                            <div style="min-height:52px; border:1px solid rgba(17,24,39,0.08); border-radius:10px; padding:6px; background: <?= htmlspecialchars($cellBg, ENT_QUOTES, 'UTF-8') ?>; <?= (!$isProfessional && !(bool)$day['is_closed'] && (bool)$day['has_working'] && $apptsAtSlot === []) ? 'cursor:pointer;' : '' ?>" <?= (!$isProfessional && !(bool)$day['is_closed'] && (bool)$day['has_working'] && $apptsAtSlot === []) ? ('data-open-create="1" data-create-date="' . htmlspecialchars($ymd, ENT_QUOTES, 'UTF-8') . '" data-create-time="' . htmlspecialchars($slotTime, ENT_QUOTES, 'UTF-8') . '"') : '' ?>>
                                <?php if ($apptsAtSlot === []): ?>
                                    <div class="lc-muted" style="font-size:12px;">&nbsp;</div>
                                <?php else: ?>
                                    <?php foreach ($apptsAtSlot as $it): ?>
                                        <?php
                                            $status = (string)($it['status'] ?? '');
                                            $statusClass = 'scheduled';
                                            if ($status === 'cancelled') $statusClass = 'cancelled';
                                            if ($status === 'confirmed') $statusClass = 'confirmed';
                                            if ($status === 'in_progress') $statusClass = 'in_progress';
                                            if ($status === 'completed') $statusClass = 'completed';
                                            if ($status === 'no_show') $statusClass = 'no_show';
                                            $patientName = (string)($it['patient_name'] ?? '');
                                            $serviceName = (string)($it['service_name'] ?? '');
                                            $professionalName = (string)($it['professional_name'] ?? '');
                                        ?>
                                        <button type="button" class="lc-statusbar lc-statusbar--<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>" style="padding-left:8px; margin:0; width:100%; text-align:left; cursor:pointer;" data-open-appointment="1"
                                            data-appt-id="<?= (int)$it['id'] ?>"
                                            data-appt-patient="<?= htmlspecialchars($patientName !== '' ? $patientName : ('Paciente #' . (int)($it['patient_id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>"
                                            data-appt-service="<?= htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8') ?>"
                                            data-appt-professional="<?= htmlspecialchars($professionalName, ENT_QUOTES, 'UTF-8') ?>"
                                            data-appt-start="<?= htmlspecialchars((string)($it['start_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-appt-end="<?= htmlspecialchars((string)($it['end_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-appt-status="<?= htmlspecialchars((string)($it['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-appt-origin="<?= htmlspecialchars((string)($it['origin'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                            <div style="font-weight:700; font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                <?= htmlspecialchars($patientName !== '' ? $patientName : ('Paciente #' . (int)($it['patient_id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <div class="lc-muted" style="font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                <?= htmlspecialchars(substr((string)($it['start_at'] ?? ''), 11, 5), ENT_QUOTES, 'UTF-8') ?>
                                                -
                                                <?= htmlspecialchars(substr((string)($it['end_at'] ?? ''), 11, 5), ENT_QUOTES, 'UTF-8') ?>
                                                <?= $serviceName !== '' ? (' • ' . htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8')) : '' ?>
                                            </div>
                                        </button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!$isProfessional): ?>
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
                    <label class="lc-label">Horário</label>
                    <select class="lc-select" name="start_at" id="modal_start_at" required>
                        <option value="">Selecione um serviço + profissional + data</option>
                    </select>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Observações (opcional)</label>
                    <input class="lc-input" type="text" name="notes" placeholder="" />
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
    <div class="lc-modal__panel" role="dialog" aria-modal="true" aria-label="Agendamento">
        <div class="lc-modal__header">
            <div>
                <div class="lc-modal__title" id="appt_title">Agendamento</div>
                <div class="lc-modal__subtitle" id="appt_subtitle"></div>
            </div>
            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="button" data-close-modal>Fechar</button>
        </div>

        <div class="lc-modal__body">
            <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="lc-card" style="margin:0;">
                    <div class="lc-card__body">
                        <div class="lc-muted" style="font-size:12px;">Paciente</div>
                        <div style="font-weight:700;" id="appt_patient"></div>
                        <div class="lc-muted" style="font-size:12px; margin-top:10px;">Serviço</div>
                        <div id="appt_service"></div>
                        <div class="lc-muted" style="font-size:12px; margin-top:10px;">Profissional</div>
                        <div id="appt_professional"></div>
                    </div>
                </div>
                <div class="lc-card" style="margin:0;">
                    <div class="lc-card__body">
                        <div class="lc-muted" style="font-size:12px;">Início</div>
                        <div id="appt_start"></div>
                        <div class="lc-muted" style="font-size:12px; margin-top:10px;">Fim</div>
                        <div id="appt_end"></div>
                        <div class="lc-muted" style="font-size:12px; margin-top:10px;">Status</div>
                        <div id="appt_status"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lc-modal__footer" style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-start;">
            <a class="lc-btn lc-btn--secondary" id="appt_reschedule" href="#">Reagendar</a>
            <a class="lc-btn lc-btn--secondary" id="appt_logs" href="#">Logs</a>

            <form method="post" action="/schedule/status" id="appt_form_confirmed">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" id="appt_id_confirmed" value="" />
                <input type="hidden" name="status" value="confirmed" />
                <input type="hidden" name="view" value="week" />
                <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Confirmar</button>
            </form>

            <form method="post" action="/schedule/status" id="appt_form_in_progress">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" id="appt_id_in_progress" value="" />
                <input type="hidden" name="status" value="in_progress" />
                <input type="hidden" name="view" value="week" />
                <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Atender</button>
            </form>

            <form method="post" action="/schedule/status" id="appt_form_completed">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" id="appt_id_completed" value="" />
                <input type="hidden" name="status" value="completed" />
                <input type="hidden" name="view" value="week" />
                <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Concluir</button>
            </form>

            <form method="post" action="/schedule/status" id="appt_form_no_show">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" id="appt_id_no_show" value="" />
                <input type="hidden" name="status" value="no_show" />
                <input type="hidden" name="view" value="week" />
                <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">No-show</button>
            </form>

            <form method="post" action="/schedule/cancel" id="appt_form_cancel">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" id="appt_id_cancel" value="" />
                <button class="lc-btn lc-btn--secondary" type="submit">Cancelar</button>
            </form>
        </div>
    </div>
</div>
</div>

<script>
(function() {
  const dateFilterEl = document.getElementById('filter_date');
  const createModal = document.getElementById('createAppointmentModal');
  const detailsModal = document.getElementById('appointmentDetailsModal');
  const patientSearchEl = document.getElementById('patientSearch');
  const patientIdEl = document.getElementById('patient_id');
  const patientResultsEl = document.getElementById('patientResults');
  const patientHintEl = document.getElementById('patientHint');
  const modalServiceEl = document.getElementById('modal_service_id');
  const modalProfEl = document.getElementById('modal_professional_id');
  const modalStartEl = document.getElementById('modal_start_at');

  let desiredSlotTime = '';
  let createDate = '';

  function openModal(modal) {
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('lc-modal--open');
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'true');
    modal.classList.remove('lc-modal--open');
  }

  function wireClose(modal) {
    if (!modal) return;
    modal.addEventListener('click', function(e) {
      const t = e.target;
      if (!(t instanceof HTMLElement)) return;
      if (t.hasAttribute('data-close-modal')) {
        closeModal(modal);
      }
    });
  }

  wireClose(createModal);
  wireClose(detailsModal);
  document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    closeModal(createModal);
    closeModal(detailsModal);
  });

  function clearPatientSelection() {
    if (patientIdEl) patientIdEl.value = '';
  }

  function hidePatientResults() {
    if (!patientResultsEl) return;
    patientResultsEl.style.display = 'none';
    patientResultsEl.innerHTML = '';
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
      const q = (patientSearchEl.value || '').trim();
      hidePatientResults();
      showPatientHint(q.length > 0);
      if (patientTimer) window.clearTimeout(patientTimer);
      if (q.length < 2) return;
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
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'lc-autocomplete__item';
          btn.textContent = it.name;
          btn.addEventListener('click', function() {
            patientSearchEl.value = it.name;
            patientIdEl.value = String(it.id);
            hidePatientResults();
            showPatientHint(false);
          });
          patientResultsEl.appendChild(btn);
        }
        patientResultsEl.style.display = 'block';
      }, 220);
    });
  }

  if (createModal) {
    const form = createModal.querySelector('form');
    if (form) {
      form.addEventListener('submit', function(e) {
        if (patientIdEl && String(patientIdEl.value || '').trim() === '') {
          e.preventDefault();
          showPatientHint(true);
        }
      });
    }
  }

  async function loadSlots() {
    if (!modalServiceEl || !modalProfEl || !modalStartEl) return;
    const serviceId = modalServiceEl.value;
    const profId = modalProfEl.value;
    const date = createDate || (dateFilterEl ? dateFilterEl.value : '');
    modalStartEl.innerHTML = '<option value="">Carregando...</option>';

    if (!serviceId || !profId || !date) {
      modalStartEl.innerHTML = '<option value="">Selecione um serviço + profissional + data</option>';
      return;
    }

    const url = `/schedule/available-slots?service_id=${encodeURIComponent(serviceId)}&professional_id=${encodeURIComponent(profId)}&date=${encodeURIComponent(date)}`;
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
    for (const s of slots) {
      const t = (s.start_at || '').slice(11, 16);
      const opt = document.createElement('option');
      opt.value = s.start_at;
      opt.textContent = t;
      if (desiredSlotTime && t === desiredSlotTime) {
        opt.selected = true;
      }
      modalStartEl.appendChild(opt);
    }
  }

  if (modalServiceEl) modalServiceEl.addEventListener('change', loadSlots);
  if (modalProfEl) modalProfEl.addEventListener('change', loadSlots);
  if (dateFilterEl) dateFilterEl.addEventListener('change', loadSlots);

  document.addEventListener('click', function(e) {
    const t = e.target;
    if (!(t instanceof HTMLElement)) return;

    const createCell = t.closest('[data-open-create]');
    if (createCell && createModal) {
      const d = createCell.getAttribute('data-create-date') || '';
      const tm = createCell.getAttribute('data-create-time') || '';
      createDate = d;
      desiredSlotTime = tm;
      openModal(createModal);
      loadSlots();
      return;
    }

    const apptBtn = t.closest('[data-open-appointment]');
    if (apptBtn && detailsModal) {
      const id = apptBtn.getAttribute('data-appt-id') || '';
      const patient = apptBtn.getAttribute('data-appt-patient') || '';
      const service = apptBtn.getAttribute('data-appt-service') || '';
      const professional = apptBtn.getAttribute('data-appt-professional') || '';
      const start = apptBtn.getAttribute('data-appt-start') || '';
      const end = apptBtn.getAttribute('data-appt-end') || '';
      const status = apptBtn.getAttribute('data-appt-status') || '';

      const elPatient = document.getElementById('appt_patient');
      const elService = document.getElementById('appt_service');
      const elProf = document.getElementById('appt_professional');
      const elStart = document.getElementById('appt_start');
      const elEnd = document.getElementById('appt_end');
      const elStatus = document.getElementById('appt_status');
      const elSubtitle = document.getElementById('appt_subtitle');

      if (elPatient) elPatient.textContent = patient;
      if (elService) elService.textContent = service || '-';
      if (elProf) elProf.textContent = professional || '-';
      if (elStart) elStart.textContent = start;
      if (elEnd) elEnd.textContent = end;
      if (elStatus) elStatus.textContent = status;
      if (elSubtitle) elSubtitle.textContent = `${start.slice(0,10)} ${start.slice(11,16)} - ${end.slice(11,16)}`;

      const linkRes = document.getElementById('appt_reschedule');
      const linkLogs = document.getElementById('appt_logs');
      if (linkRes) linkRes.setAttribute('href', `/schedule/reschedule?id=${encodeURIComponent(id)}`);
      if (linkLogs) linkLogs.setAttribute('href', `/schedule/logs?appointment_id=${encodeURIComponent(id)}`);

      const ids = ['appt_id_confirmed','appt_id_in_progress','appt_id_completed','appt_id_no_show','appt_id_cancel'];
      for (const iid of ids) {
        const el = document.getElementById(iid);
        if (el) el.setAttribute('value', id);
      }

      openModal(detailsModal);
      return;
    }
  });
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
