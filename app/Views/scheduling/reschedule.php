<?php
/** @var array<string,mixed> $appointment */
/** @var list<array<string,mixed>> $professionals */
/** @var list<array<string,mixed>> $services */
/** @var string $error */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Reagendar';

$apptId = (int)($appointment['id'] ?? 0);
$serviceId = (int)($appointment['service_id'] ?? 0);
$professionalId = (int)($appointment['professional_id'] ?? 0);
$startAt = (string)($appointment['start_at'] ?? '');
$date = substr($startAt, 0, 10);

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-card" style="margin-bottom: 16px; border-left: 4px solid #b91c1c;">
        <div class="lc-card__body"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Agendamento #<?= (int)$apptId ?></div>
    <div class="lc-card__body" class="lc-muted">
        Atual: <?= htmlspecialchars($startAt, ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Novo horário</div>
    <div class="lc-card__body">
        <form method="post" action="/schedule/reschedule" class="lc-form" style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= (int)$apptId ?>" />

            <div class="lc-field">
                <label class="lc-label">Serviço</label>
                <select class="lc-select" name="service_id" id="service_id" required>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= ((int)$s['id'] === $serviceId) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$s['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Profissional</label>
                <select class="lc-select" name="professional_id" id="professional_id" required>
                    <?php foreach ($professionals as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === $professionalId) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Data</label>
                <input class="lc-input" type="date" id="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Horário</label>
                <select class="lc-select" name="start_at" id="start_at" required>
                    <option value="">Selecione um serviço + profissional + data</option>
                </select>
            </div>

            <div style="grid-column: 1 / -1;">
                <button class="lc-btn" type="submit">Salvar reagendamento</button>
                <a class="lc-btn lc-btn--secondary" href="/schedule?date=<?= urlencode($date) ?>">Voltar</a>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
  const serviceEl = document.getElementById('service_id');
  const profEl = document.getElementById('professional_id');
  const startEl = document.getElementById('start_at');
  const dateEl = document.getElementById('date');
  const excludeId = <?= json_encode($apptId) ?>;

  async function loadSlots() {
    const serviceId = serviceEl.value;
    const profId = profEl.value;
    const date = dateEl.value;
    startEl.innerHTML = '<option value="">Carregando...</option>';

    if (!serviceId || !profId || !date) {
      startEl.innerHTML = '<option value="">Selecione um serviço + profissional + data</option>';
      return;
    }

    const url = `/schedule/available?service_id=${encodeURIComponent(serviceId)}&professional_id=${encodeURIComponent(profId)}&date=${encodeURIComponent(date)}&exclude_appointment_id=${encodeURIComponent(excludeId)}`;
    const res = await fetch(url);
    const data = await res.json();

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
  dateEl.addEventListener('change', loadSlots);

  loadSlots();
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
