<?php
$csrf = $_SESSION['_csrf'] ?? '';
$isProfessional = isset($is_professional) ? (bool)$is_professional : false;
$professionalId = isset($professional_id) ? (int)$professional_id : 0;
$title = 'Agenda (Mês)';
$statusClassMap = isset($status_class_map) && is_array($status_class_map) ? $status_class_map : [];

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

$funnelStages = $funnel_stages ?? [];

$dateDisplay = (string)($date ?? '');
$dateDt = \DateTimeImmutable::createFromFormat('Y-m-d', $dateDisplay);
if ($dateDt !== false) {
    $dateDisplay = $dateDt->format('d/m/Y');
}

$svcMap = [];
foreach (($services ?? []) as $s) {
    $svcMap[(int)$s['id']] = $s;
}

$profMap = [];
foreach (($professionals ?? []) as $p) {
    $profMap[(int)$p['id']] = $p;
}

$byDay = isset($by_day) && is_array($by_day) ? $by_day : [];
$blocksByDay = isset($blocks_by_day) && is_array($blocks_by_day) ? $blocks_by_day : [];

$monthStart = \DateTimeImmutable::createFromFormat('Y-m-d', (string)($month_start ?? ($date ?? date('Y-m-d'))));
if ($monthStart !== false) {
    $monthStart = $monthStart->modify('first day of this month');
}

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-card lc-statusbar lc-statusbar--no_show" style="margin-bottom: 16px;">
        <div class="lc-card__body"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if (isset($created) && $created !== ''): ?>
    <div class="lc-card lc-statusbar lc-statusbar--completed" style="margin-bottom: 16px;">
        <div class="lc-card__body">Atualizado. ID: <?= htmlspecialchars((string)$created, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="lc-schedule">
    <div class="lc-pagehead">
        <div>
            <div class="lc-pagehead__title">Agenda</div>
            <div class="lc-pagehead__meta">
                <span class="lc-badge lc-badge--primary"><?= htmlspecialchars((string)$dateDisplay, ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!$isProfessional && (int)$professionalId > 0 && isset($profMap[(int)$professionalId])): ?>
                    <span class="lc-badge"><?= htmlspecialchars((string)$profMap[(int)$professionalId]['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php elseif (!$isProfessional && (int)$professionalId === 0): ?>
                    <span class="lc-badge">Todos os profissionais</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="lc-pagehead__actions">
            <div class="lc-segmented" role="tablist" aria-label="Visão da agenda">
                <a class="lc-segmented__item" role="tab" aria-selected="false" href="/schedule?view=day&date=<?= urlencode((string)$date) ?><?= $professionalId > 0 ? ('&professional_id=' . (int)$professionalId) : '' ?>">Dia</a>
                <a class="lc-segmented__item" role="tab" aria-selected="false" href="/schedule?view=week&date=<?= urlencode((string)$date) ?><?= $professionalId > 0 ? ('&professional_id=' . (int)$professionalId) : '' ?>">Semana</a>
                <a class="lc-segmented__item lc-segmented__item--active" role="tab" aria-selected="true" href="/schedule?view=month&date=<?= urlencode((string)$date) ?><?= $professionalId > 0 ? ('&professional_id=' . (int)$professionalId) : '' ?>">Mês</a>
            </div>

            <?php if ($can('scheduling.ops')): ?>
                <a class="lc-btn lc-btn--secondary" href="/schedule/ops?date=<?= urlencode((string)$date) ?>">Operação</a>
            <?php endif; ?>
        </div>
    </div>

<div class="lc-card" style="margin-bottom:16px;">
    <div class="lc-card__body">
        <form method="get" action="/schedule" class="lc-form lc-flex lc-gap-md lc-flex--wrap" style="align-items:end;">
            <input type="hidden" name="view" value="month" />

            <div class="lc-field">
                <label class="lc-label">Mês de</label>
                <input class="lc-input" id="filter_date" type="date" name="date" value="<?= htmlspecialchars((string)$date, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <?php if (!$isProfessional): ?>
                <div class="lc-field" style="min-width: 280px;">
                    <label class="lc-label">Profissional</label>
                    <select class="lc-select" name="professional_id">
                        <option value="0">Todos</option>
                        <?php foreach (($professionals ?? []) as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$professionalId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <input type="hidden" name="professional_id" value="<?= (int)$professionalId ?>" />
            <?php endif; ?>

            <div>
                <button class="lc-btn" type="submit">Aplicar</button>
            </div>
        </form>
    </div>
</div>

<?php if ($monthStart !== false): ?>
    <?php
        $gridStart = $monthStart->modify('-' . (int)$monthStart->format('w') . ' days');
        $nextMonth = $monthStart->modify('first day of next month');
        $prevMonth = $monthStart->modify('first day of previous month');
        $today = date('Y-m-d');
    ?>

    <div class="lc-card" style="margin-bottom:16px;">
        <div class="lc-card__header lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md">
            <div>
                <?= htmlspecialchars($monthStart->format('m/Y'), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="lc-flex lc-gap-sm">
                <a class="lc-btn lc-btn--secondary" href="/schedule?view=month&date=<?= urlencode($prevMonth->format('Y-m-d')) ?><?= $professionalId>0 ? ('&professional_id=' . (int)$professionalId) : '' ?>">Anterior</a>
                <a class="lc-btn lc-btn--secondary" href="/schedule?view=month&date=<?= urlencode($nextMonth->format('Y-m-d')) ?><?= $professionalId>0 ? ('&professional_id=' . (int)$professionalId) : '' ?>">Próximo</a>
            </div>
        </div>
        <div class="lc-card__body">
            <div class="lc-grid" style="grid-template-columns: repeat(7, minmax(0, 1fr)); gap:10px;">
                <?php for ($i=0; $i<42; $i++): ?>
                    <?php
                        $d = $gridStart->modify('+' . $i . ' days');
                        $ymd = $d->format('Y-m-d');
                        $inMonth = $d->format('m') === $monthStart->format('m');
                        $dayItems = $byDay[$ymd] ?? [];
                        $dayBlocks = $blocksByDay[$ymd] ?? [];
                        $count = count($dayItems);
                        $border = $ymd === $today ? '4px solid #2563eb' : '1px solid rgba(17,24,39,0.08)';
                        $opacity = $inMonth ? '1' : '0.45';
                    ?>
                    <div style="min-width:0;">
                    <button type="button"
                        style="display:block; width:100%; padding:0; border:0; background:transparent; text-align:left; cursor: <?= (!$isProfessional ? 'pointer' : 'default') ?>; min-width:0;"
                        <?= (!$isProfessional && $can('scheduling.create')) ? ('data-open-create="1" data-create-date="' . htmlspecialchars($ymd, ENT_QUOTES, 'UTF-8') . '"') : '' ?>
                    >
                        <div class="lc-card" style="margin:0; border-left: <?= htmlspecialchars($border, ENT_QUOTES, 'UTF-8') ?>; opacity: <?= htmlspecialchars($opacity, ENT_QUOTES, 'UTF-8') ?>;">
                            <div class="lc-card__body lc-flex" style="padding:12px; height:120px; overflow:hidden; flex-direction:column; gap:8px;">
                                <div class="lc-flex lc-flex--between" style="align-items:baseline;">
                                    <div style="font-weight: 700;">
                                        <?= htmlspecialchars($d->format('d'), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="lc-muted" style="font-size:12px;">
                                        <?= $count > 0 ? ((int)$count . ' ag.') : '' ?>
                                    </div>
                                </div>

                                <?php if (is_array($dayBlocks) && $dayBlocks !== []): ?>
                                    <div style="font-size:12px; font-weight:650; color: rgba(31,41,55,0.70);">Bloqueado</div>
                                <?php endif; ?>

                                <?php if ($dayItems === []): ?>
                                    <div class="lc-muted" style="font-size:12px;">&nbsp;</div>
                                <?php else: ?>
                                    <?php
                                        usort($dayItems, fn ($a, $b) => strcmp((string)$a['start_at'], (string)$b['start_at']));
                                        $maxShow = 3;
                                        $shown = array_slice($dayItems, 0, $maxShow);
                                    ?>
                                    <div class="lc-flex" style="flex-direction:column; gap:6px;">
                                        <?php foreach ($shown as $it): ?>
                                            <?php
                                                $pid = (int)$it['professional_id'];
                                                $sid = (int)$it['service_id'];
                                                $pname = isset($profMap[$pid]) ? (string)$profMap[$pid]['name'] : ('#' . $pid);
                                                $sname = isset($svcMap[$sid]) ? (string)$svcMap[$sid]['name'] : ('#' . $sid);
                                                $status = (string)$it['status'];
                                                $statusClass = isset($statusClassMap[$status]) ? (string)$statusClassMap[$status] : 'scheduled';
                                                $patientNameMonth = trim((string)($it['patient_name'] ?? ''));
                                            ?>
                                            <button
                                                type="button"
                                                class="lc-flex lc-gap-sm"
                                                style="align-items:center; background:none; border:none; padding:0; cursor:pointer; text-align:left; width:100%;"
                                                onclick="event.stopPropagation(); openApptDetail(<?= (int)$it['id'] ?>);"
                                                title="<?= htmlspecialchars(($patientNameMonth !== '' ? $patientNameMonth . ' — ' : '') . $sname . ' (' . substr((string)$it['start_at'], 11, 5) . ')', ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:currentColor; flex-shrink:0;" class="lc-dot lc-dot--<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"></span>
                                                <div style="font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                    <span style="font-weight:600;"><?= htmlspecialchars(substr((string)$it['start_at'], 11, 5), ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php if ($patientNameMonth !== ''): ?>
                                                        <span class="lc-muted">• <?= htmlspecialchars($patientNameMonth, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php else: ?>
                                                        <span class="lc-muted">• <?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </button>
                                        <?php endforeach; ?>
                                        <?php if ($count > $maxShow): ?>
                                            <a
                                                href="/schedule?view=day&date=<?= urlencode($ymd) ?><?= $professionalId > 0 ? ('&professional_id=' . (int)$professionalId) : '' ?>"
                                                class="lc-muted"
                                                style="font-size:12px; text-decoration:none;"
                                                onclick="event.stopPropagation();"
                                            >+<?= (int)($count - $maxShow) ?> mais...</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </button>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

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
                        <?php foreach (($services ?? []) as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars((string)$s['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Profissional</label>
                    <select class="lc-select" name="professional_id" id="modal_professional_id" required>
                        <option value="">Selecione</option>
                        <?php foreach (($professionals ?? []) as $p): ?>
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

    <script>
    (function() {
      const dateFilterEl = document.getElementById('filter_date');
      const createModal = document.getElementById('createAppointmentModal');
      const patientSearchEl = document.getElementById('patientSearch');
      const patientIdEl = document.getElementById('patient_id');
      const patientResultsEl = document.getElementById('patientResults');
      const patientHintEl = document.getElementById('patientHint');
      const modalServiceEl = document.getElementById('modal_service_id');
      const modalProfEl = document.getElementById('modal_professional_id');
      const modalStartEl = document.getElementById('modal_start_at');

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
      document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        closeModal(createModal);
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
              const name = (it.name || '').toString();
              const meta = [it.phone, it.email].filter(Boolean).join(' · ');
              btn.innerHTML = `<div class="lc-autocomplete__name"></div><div class="lc-autocomplete__meta"></div>`;
              const nameEl = btn.querySelector('.lc-autocomplete__name');
              const metaEl = btn.querySelector('.lc-autocomplete__meta');
              if (nameEl) nameEl.textContent = name;
              if (metaEl) metaEl.textContent = meta;
              btn.addEventListener('click', function() {
                patientSearchEl.value = name;
                patientIdEl.value = String(it.id || '');
                hidePatientResults();
                showPatientHint(false);
              });
              patientResultsEl.appendChild(btn);
            }
            patientResultsEl.style.display = 'block';
          }, 250);
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
        for (const s of slots) {
          const t = (s.start_at || '').slice(11, 16);
          const opt = document.createElement('option');
          opt.value = s.start_at;
          opt.textContent = t;
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
        if (!createCell || !createModal) return;
        const d = createCell.getAttribute('data-create-date') || '';
        createDate = d;
        if (dateFilterEl && d) {
          dateFilterEl.value = d;
        }
        openModal(createModal);
        loadSlots();
      });
    })();
    </script>
<?php endif; ?>

</div>

<!-- Modal de detalhes do agendamento -->
<div id="apptDetailModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9999; align-items:center; justify-content:center; padding:18px;">
    <div style="width:100%; max-width:480px; background:#fff; border-radius:14px; box-shadow:0 16px 50px rgba(0,0,0,.3); overflow:hidden;">
        <div style="padding:14px 16px; border-bottom:1px solid rgba(0,0,0,.08); display:flex; justify-content:space-between; align-items:center;">
            <div style="font-weight:800; font-size:16px;">Detalhes do agendamento</div>
            <button type="button" onclick="closeApptDetail();" style="background:none; border:none; cursor:pointer; font-size:20px; line-height:1; color:#6b7280;">×</button>
        </div>
        <div id="apptDetailBody" style="padding:16px;">
            <div class="lc-muted">Carregando...</div>
        </div>
        <div id="apptDetailFooter" style="padding:12px 16px; border-top:1px solid rgba(0,0,0,.08); display:flex; gap:10px; flex-wrap:wrap;"></div>
    </div>
</div>

<script>
(function(){
    var modal = document.getElementById('apptDetailModal');
    var body = document.getElementById('apptDetailBody');
    var footer = document.getElementById('apptDetailFooter');

    window.openApptDetail = function(id) {
        if (!modal || !body || !footer) return;
        body.innerHTML = '<div class="lc-muted">Carregando...</div>';
        footer.innerHTML = '';
        modal.style.display = 'flex';

        fetch('/schedule/details?id=' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' } })
            .then(function(r){ return r.json(); })
            .then(function(data){
                var it = data.item;
                if (!it) { body.innerHTML = '<div class="lc-muted">Não encontrado.</div>'; return; }

                var statusLabels = {
                    'scheduled': 'Agendado', 'confirmed': 'Confirmado',
                    'in_progress': 'Em atendimento', 'completed': 'Concluído',
                    'no_show': 'Faltou', 'cancelled': 'Cancelado'
                };
                var statusLabel = statusLabels[it.status] || it.status;

                var startDate = it.start_at ? it.start_at.slice(0,10).split('-').reverse().join('/') : '';
                var startTime = it.start_at ? it.start_at.slice(11,16) : '';
                var endTime = it.end_at ? it.end_at.slice(11,16) : '';

                body.innerHTML = [
                    row('Paciente', it.patient_name || '—'),
                    row('Serviço', it.service_name || '—'),
                    row('Profissional', it.professional_name || '—'),
                    row('Data', startDate),
                    row('Horário', startTime + (endTime ? ' – ' + endTime : '')),
                    row('Status', statusLabel),
                    it.notes ? row('Observações', it.notes) : '',
                ].join('');

                footer.innerHTML = '';

                var btnDay = document.createElement('a');
                btnDay.href = '/schedule?view=day&date=' + (it.start_at ? it.start_at.slice(0,10) : '');
                btnDay.className = 'lc-btn lc-btn--secondary';
                btnDay.textContent = 'Ver na agenda do dia';
                footer.appendChild(btnDay);

                var btnClose = document.createElement('button');
                btnClose.type = 'button';
                btnClose.className = 'lc-btn lc-btn--secondary';
                btnClose.textContent = 'Fechar';
                btnClose.onclick = closeApptDetail;
                footer.appendChild(btnClose);
            })
            .catch(function(){
                body.innerHTML = '<div class="lc-muted">Erro ao carregar detalhes.</div>';
            });
    };

    window.closeApptDetail = function() {
        if (modal) modal.style.display = 'none';
    };

    function row(label, value) {
        return '<div style="margin-bottom:10px;"><div style="font-size:12px; color:#6b7280; margin-bottom:2px;">' +
            escHtml(label) + '</div><div style="font-weight:600;">' + escHtml(String(value || '')) + '</div></div>';
    }

    function escHtml(s) {
        return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    if (modal) {
        modal.addEventListener('click', function(e){ if (e.target === modal) closeApptDetail(); });
    }
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeApptDetail(); });
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
