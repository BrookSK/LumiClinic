<?php
/** @var string $date */
/** @var list<array<string,mixed>> $items */
/** @var list<array<string,mixed>> $professionals */
/** @var list<array<string,mixed>> $services */
/** @var string $created */
/** @var string $error */
$view = isset($view) ? (string)$view : 'day';
$professionalId = isset($professional_id) ? (int)$professional_id : 0;
$isProfessional = isset($is_professional) ? (bool)$is_professional : false;
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 100;
$hasNext = isset($has_next) ? (bool)$has_next : false;
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Agenda';

$svcMap = [];
foreach ($services as $s) {
    $svcMap[(int)$s['id']] = $s;
}

$profMap = [];
foreach ($professionals as $p) {
    $profMap[(int)$p['id']] = $p;
}

ob_start();
?>

<div class="lc-schedule">
    <div class="lc-pagehead">
        <div>
            <div class="lc-pagehead__title">Agenda</div>
            <div class="lc-pagehead__meta">
                <span class="lc-badge lc-badge--gold"><?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!$isProfessional && $professionalId > 0 && isset($profMap[$professionalId])): ?>
                    <span class="lc-badge"><?= htmlspecialchars((string)$profMap[$professionalId]['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php elseif (!$isProfessional && $professionalId === 0): ?>
                    <span class="lc-badge">Todos os profissionais</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="lc-pagehead__actions">
            <a class="lc-btn lc-btn--secondary" href="/schedule?view=week&date=<?= urlencode($date) ?><?= $professionalId > 0 ? ('&professional_id=' . (int)$professionalId) : '' ?>">Semana</a>
            <a class="lc-btn lc-btn--secondary" href="/schedule?view=month&date=<?= urlencode($date) ?><?= $professionalId > 0 ? ('&professional_id=' . (int)$professionalId) : '' ?>">Mês</a>
            <a class="lc-btn lc-btn--secondary" href="/schedule/ops?date=<?= urlencode($date) ?>">Operação</a>
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

    <div class="lc-schedule__grid">
        <div class="lc-stack">
            <div class="lc-card lc-card--soft">
                <div class="lc-card__header">
                    <div class="lc-card__title">Filtros</div>
                </div>
                <div class="lc-card__body">
                    <form method="get" action="/schedule" class="lc-form lc-form--grid" style="grid-template-columns:1fr;">
                        <input type="hidden" name="view" value="day" />
                        <input type="hidden" name="per_page" value="<?= (int)$perPage ?>" />
                        <input type="hidden" name="page" value="1" />

                        <div class="lc-field">
                            <label class="lc-label">Data</label>
                            <input class="lc-input" type="date" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                        </div>

                        <?php if (!$isProfessional): ?>
                            <div class="lc-field">
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

                        <div class="lc-form__actions">
                            <button class="lc-btn lc-btn--primary" type="submit">Aplicar filtros</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!$isProfessional): ?>
                <div class="lc-card lc-card--soft">
                    <div class="lc-card__header">
                        <div class="lc-card__title">Criar agendamento</div>
                    </div>
                    <div class="lc-card__body">
                        <form method="post" action="/schedule/create" class="lc-form lc-form--grid" style="grid-template-columns:1fr;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

                            <div class="lc-field">
                                <label class="lc-label">Serviço</label>
                                <select class="lc-select" name="service_id" id="service_id" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($services as $s): ?>
                                        <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars((string)$s['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="lc-field">
                                <label class="lc-label">Profissional</label>
                                <select class="lc-select" name="professional_id" id="professional_id" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($professionals as $p): ?>
                                        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="lc-field">
                                <label class="lc-label">Horário</label>
                                <select class="lc-select" name="start_at" id="start_at" required>
                                    <option value="">Selecione um serviço + profissional + data</option>
                                </select>
                            </div>

                            <div class="lc-field">
                                <label class="lc-label">Observações (opcional)</label>
                                <input class="lc-input" type="text" name="notes" placeholder="" />
                            </div>

                            <div class="lc-form__actions">
                                <button class="lc-btn lc-btn--primary" type="submit">Agendar</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <div class="lc-card lc-card--soft">
                <div class="lc-card__header">
                    <div class="lc-card__title">Agendamentos</div>
                    <div class="lc-card__actions">
                        <span class="lc-muted">Página <?= (int)$page ?></span>
                    </div>
                </div>
                <div class="lc-card__body">
        <?php if ($items === []): ?>
            <div class="lc-muted">Nenhum agendamento.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                <thead>
                <tr>
                    <th>Início</th>
                    <th>Fim</th>
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
                        $status = (string)$it['status'];
                        $statusClass = 'scheduled';
                        if ($status === 'cancelled') $statusClass = 'cancelled';
                        if ($status === 'confirmed') $statusClass = 'confirmed';
                        if ($status === 'in_progress') $statusClass = 'in_progress';
                        if ($status === 'completed') $statusClass = 'completed';
                        if ($status === 'no_show') $statusClass = 'no_show';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars(substr((string)$it['start_at'], 11, 5), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(substr((string)$it['end_at'], 11, 5), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($sname, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="lc-badge lc-badge--status lc-badge--status-<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="lc-td-actions">
                            <div class="lc-actions lc-actions--compact">
                                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/schedule/reschedule?id=<?= (int)$it['id'] ?>">Reagendar</a>
                                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/schedule/logs?appointment_id=<?= (int)$it['id'] ?>">Logs</a>

                                <details class="lc-actions__more">
                                    <summary class="lc-btn lc-btn--secondary lc-btn--sm">Ações</summary>
                                    <div class="lc-actions__menu">
                                        <form method="post" action="/schedule/status">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                            <input type="hidden" name="status" value="confirmed" />
                                            <input type="hidden" name="view" value="day" />
                                            <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="professional_id" value="<?= (int)$professionalId ?>" />
                                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Confirmar</button>
                                        </form>

                                        <form method="post" action="/schedule/status">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                            <input type="hidden" name="status" value="in_progress" />
                                            <input type="hidden" name="view" value="day" />
                                            <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="professional_id" value="<?= (int)$professionalId ?>" />
                                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Atender</button>
                                        </form>

                                        <form method="post" action="/schedule/status">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                            <input type="hidden" name="status" value="completed" />
                                            <input type="hidden" name="view" value="day" />
                                            <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="professional_id" value="<?= (int)$professionalId ?>" />
                                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Concluir</button>
                                        </form>

                                        <form method="post" action="/schedule/status">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                            <input type="hidden" name="status" value="no_show" />
                                            <input type="hidden" name="view" value="day" />
                                            <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="professional_id" value="<?= (int)$professionalId ?>" />
                                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">No-show</button>
                                        </form>

                                        <form method="post" action="/schedule/cancel">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Cancelar</button>
                                        </form>
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
    </div>
</div>
<script>
(function() {
  const serviceEl = document.getElementById('service_id');
  const profEl = document.getElementById('professional_id');
  const startEl = document.getElementById('start_at');
  const date = <?= json_encode($date) ?>;

  if (!serviceEl || !profEl || !startEl) {
    return;
  }

  async function loadSlots() {
    const serviceId = serviceEl.value;
    const profId = profEl.value;
    startEl.innerHTML = '<option value="">Carregando...</option>';

    if (!serviceId || !profId || !date) {
      startEl.innerHTML = '<option value="">Selecione um serviço + profissional + data</option>';
      return;
    }

    const url = `/schedule/available?service_id=${encodeURIComponent(serviceId)}&professional_id=${encodeURIComponent(profId)}&date=${encodeURIComponent(date)}`;

    let data = null;
    try {
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) {
        startEl.innerHTML = '<option value="">Erro ao carregar horários</option>';
        return;
      }
      data = await res.json();
    } catch (e) {
      startEl.innerHTML = '<option value="">Erro ao carregar horários</option>';
      return;
    }

    const slots = data.slots || [];
    if (slots.length === 0) {
      startEl.innerHTML = '<option value="">Sem horários disponíveis</option>';
      return;
    }

    startEl.innerHTML = '<option value="">Selecione</option>';
    for (const s of slots) {
      const t = (s.start_at || '').slice(11, 16);
      const opt = document.createElement('option');
      opt.value = s.start_at;
      opt.textContent = t;
      startEl.appendChild(opt);
    }
  }

  serviceEl.addEventListener('change', loadSlots);
  profEl.addEventListener('change', loadSlots);

  loadSlots();
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
