<?php
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Reagendar';

$can = function (string $p): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $perms = $_SESSION['permissions'] ?? [];
    if (!is_array($perms)) return false;
    if (isset($perms['allow'], $perms['deny'])) {
        if (in_array($p, $perms['deny'], true)) return false;
        return in_array($p, $perms['allow'], true);
    }
    return in_array($p, $perms, true);
};

$apptId       = (int)($appointment['id'] ?? 0);
$serviceId    = (int)($appointment['service_id'] ?? 0);
$professionalId = (int)($appointment['professional_id'] ?? 0);
$startAt      = (string)($appointment['start_at'] ?? '');
$patientName  = trim((string)($appointment['patient_name'] ?? ''));
$serviceName  = trim((string)($appointment['service_name'] ?? ''));
$profName     = trim((string)($appointment['professional_name'] ?? ''));
$date         = substr($startAt, 0, 10);

// Formatar data/hora atual
$currentFmt = '';
try {
    $currentFmt = (new \DateTimeImmutable($startAt))->format('d/m/Y \à\s H:i');
} catch (\Throwable $e) {
    $currentFmt = $startAt;
}

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div style="font-weight:800; font-size:18px;">Reagendar</div>
    <a class="lc-btn lc-btn--secondary" href="/schedule?view=day&date=<?= urlencode($date) ?>">Voltar à agenda</a>
</div>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Resumo do agendamento atual -->
<div class="lc-card" style="margin-bottom:16px; border-left:4px solid #eeb810;">
    <div class="lc-card__body">
        <div class="lc-muted" style="font-size:12px; margin-bottom:6px;">Agendamento atual</div>
        <div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(3, minmax(0,1fr));">
            <div>
                <div class="lc-muted" style="font-size:12px;">Paciente</div>
                <div style="font-weight:700;"><?= htmlspecialchars($patientName !== '' ? $patientName : '—', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Serviço</div>
                <div style="font-weight:600;"><?= htmlspecialchars($serviceName !== '' ? $serviceName : '—', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Data/hora atual</div>
                <div style="font-weight:600; color:#b91c1c;"><?= htmlspecialchars($currentFmt, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Formulário de novo horário -->
<div class="lc-card">
    <div class="lc-card__header" style="font-weight:700;">Novo horário</div>
    <div class="lc-card__body">
        <?php if ($can('scheduling.update')): ?>
            <form method="post" action="/schedule/reschedule" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= $apptId ?>" />

                <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr; align-items:end;">
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
                        <label class="lc-label">Nova data</label>
                        <input class="lc-input" type="date" id="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" min="<?= date('Y-m-d') ?>" />
                    </div>

                    <div class="lc-field">
                        <label class="lc-label">Novo horário</label>
                        <select class="lc-select" name="start_at" id="start_at" required>
                            <option value="">Selecione a data primeiro</option>
                        </select>
                    </div>
                </div>

                <div class="lc-flex lc-gap-sm" style="margin-top:16px;">
                    <button class="lc-btn lc-btn--primary" type="submit">Confirmar reagendamento</button>
                    <a class="lc-btn lc-btn--secondary" href="/schedule?view=day&date=<?= urlencode($date) ?>">Cancelar</a>
                </div>
            </form>
        <?php else: ?>
            <div class="lc-muted">Sem permissão para reagendar.</div>
            <a class="lc-btn lc-btn--secondary" style="margin-top:10px;" href="/schedule?view=day&date=<?= urlencode($date) ?>">Voltar</a>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    var serviceEl    = document.getElementById('service_id');
    var profEl       = document.getElementById('professional_id');
    var startEl      = document.getElementById('start_at');
    var dateEl       = document.getElementById('date');
    var excludeId    = <?= json_encode($apptId) ?>;

    if (!serviceEl || !profEl || !startEl || !dateEl) return;

    async function loadSlots() {
        var serviceId = serviceEl.value;
        var profId    = profEl.value;
        var date      = dateEl.value;

        if (!serviceId || !profId || !date) {
            startEl.innerHTML = '<option value="">Selecione serviço + profissional + data</option>';
            return;
        }

        startEl.innerHTML = '<option value="">Carregando...</option>';

        try {
            var url = '/schedule/available?service_id=' + encodeURIComponent(serviceId)
                + '&professional_id=' + encodeURIComponent(profId)
                + '&date=' + encodeURIComponent(date)
                + '&exclude_appointment_id=' + encodeURIComponent(excludeId);

            var res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            var data = await res.json();
            var slots = data.slots || [];

            if (slots.length === 0) {
                startEl.innerHTML = '<option value="">Sem horários disponíveis nesta data</option>';
                return;
            }

            startEl.innerHTML = '<option value="">Selecione o horário</option>';
            slots.forEach(function(s) {
                var t = (s.start_at || '').slice(11, 16);
                var opt = document.createElement('option');
                opt.value = s.start_at;
                opt.textContent = t;
                startEl.appendChild(opt);
            });
        } catch (e) {
            startEl.innerHTML = '<option value="">Erro ao carregar horários</option>';
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
