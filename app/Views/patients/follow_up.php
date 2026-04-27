<?php
$title = 'Follow-up de Pacientes';
$patients = $patients ?? [];
$days = isset($days) ? (int)$days : 180;
$waTemplates = $wa_templates ?? [];
$csrf = $_SESSION['_csrf'] ?? '';

$activeTemplates = array_filter($waTemplates, fn($t) => (string)($t['status'] ?? 'active') === 'active');

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Follow-up</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients">Pacientes</a>
        <a class="lc-btn lc-btn--secondary" href="/patients/birthdays">Aniversariantes</a>
        <a class="lc-btn lc-btn--secondary" href="/patients/follow-up/export.pdf?days=<?= (int)$days ?>" target="_blank">📄 Exportar PDF</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <form method="get" action="/patients/follow-up" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
            <div class="lc-field">
                <label class="lc-label">Sem consulta há mais de (dias)</label>
                <select class="lc-select" name="days">
                    <?php foreach ([90, 120, 180, 270, 365] as $d): ?>
                        <option value="<?= $d ?>" <?= $d === $days ? 'selected' : '' ?>><?= $d ?> dias</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="lc-btn lc-btn--primary" type="submit">Filtrar</button>
        </form>
    </div>
</div>

<?php if (!empty($activeTemplates)): ?>
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__header">Envio em lote via WhatsApp</div>
    <div class="lc-card__body">
        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
            <div class="lc-field">
                <label class="lc-label">Template</label>
                <select class="lc-select" id="batchTemplate" style="min-width:220px;">
                    <option value="">Selecione um template</option>
                    <?php foreach ($activeTemplates as $t): ?>
                        <option value="<?= htmlspecialchars((string)$t['code'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string)$t['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="button" class="lc-btn lc-btn--primary" id="btnSendAll">Enviar para todos com WhatsApp</button>
        </div>
        <div class="lc-muted" style="margin-top:6px; font-size:12px;">
            Variáveis disponíveis nos templates: <code>{patient_name}</code>, <code>{clinic_name}</code>
        </div>
        <div id="batchResult" style="margin-top:10px;"></div>
    </div>
</div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__header">
        Pacientes sem consulta há mais de <?= (int)$days ?> dias
        <span class="lc-badge lc-badge--primary" style="margin-left:8px;"><?= count($patients) ?></span>
    </div>
    <div class="lc-card__body">
        <?php if ($patients === []): ?>
            <div class="lc-muted">Nenhum paciente encontrado para este critério.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Última consulta</th>
                    <th>Telefone</th>
                    <th>WhatsApp</th>
                    <?php if (!empty($activeTemplates)): ?><th>Enviar</th><?php endif; ?>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($patients as $p): ?>
                    <?php
                        $lastAt = trim((string)($p['last_appointment_at'] ?? ''));
                        $phone = trim((string)($p['phone'] ?? ''));
                        $waOptIn = (int)($p['whatsapp_opt_in'] ?? 0);
                        $waLink = '';
                        if ($phone !== '') {
                            $waLink = 'https://wa.me/' . preg_replace('/\D/', '', $phone);
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($p['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $lastAt !== '' ? htmlspecialchars($lastAt, ENT_QUOTES, 'UTF-8') : '<span class="lc-muted">Nunca</span>' ?></td>
                        <td><?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $waOptIn ? '<span class="lc-badge lc-badge--success">Sim</span>' : '<span class="lc-badge lc-badge--secondary">Não</span>' ?></td>
                        <?php if (!empty($activeTemplates)): ?>
                        <td>
                            <?php if ($waOptIn && $phone !== ''): ?>
                                <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm"
                                    onclick="sendWa(<?= (int)$p['id'] ?>, this)">
                                    Enviar WA
                                </button>
                                <span class="lc-muted" id="wa-status-<?= (int)$p['id'] ?>" style="font-size:12px; margin-left:6px;"></span>
                            <?php else: ?>
                                <span class="lc-muted" style="font-size:12px;">Sem WA</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td>
                            <div class="lc-flex lc-gap-sm">
                                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/patients/view?id=<?= (int)$p['id'] ?>">Ver</a>
                                <?php if ($waLink !== '' && $waOptIn): ?>
                                    <a class="lc-btn lc-btn--secondary lc-btn--sm" href="<?= htmlspecialchars($waLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">WA</a>
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

<?php if (!empty($activeTemplates)): ?>
<script>
(function(){
    var csrf = <?= json_encode($csrf, JSON_UNESCAPED_UNICODE) ?>;

    window.sendWa = function(patientId, btn) {
        var tplEl = document.getElementById('batchTemplate');
        var code = tplEl ? tplEl.value : '';
        if (!code) { alert('Selecione um template primeiro.'); return; }

        var statusEl = document.getElementById('wa-status-' + patientId);
        if (btn) btn.disabled = true;
        if (statusEl) statusEl.textContent = 'Enviando...';

        var fd = new FormData();
        fd.append('_csrf', csrf);
        fd.append('patient_id', patientId);
        fd.append('template_code', code);

        fetch('/patients/whatsapp/send-json', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (btn) btn.disabled = false;
            if (data.ok) {
                if (statusEl) statusEl.textContent = '✓ Enviado';
            } else {
                if (statusEl) statusEl.textContent = '✗ ' + (data.error || 'Erro');
            }
        })
        .catch(function(){
            if (btn) btn.disabled = false;
            if (statusEl) statusEl.textContent = '✗ Falha';
        });
    };

    var btnAll = document.getElementById('btnSendAll');
    var batchResult = document.getElementById('batchResult');
    if (btnAll) {
        btnAll.addEventListener('click', function(){
            var tplEl = document.getElementById('batchTemplate');
            var code = tplEl ? tplEl.value : '';
            if (!code) { alert('Selecione um template primeiro.'); return; }

            var btns = document.querySelectorAll('[onclick^="sendWa"]');
            if (!btns.length) { if (batchResult) batchResult.textContent = 'Nenhum paciente com WhatsApp.'; return; }

            if (!confirm('Enviar mensagem para ' + btns.length + ' paciente(s) com WhatsApp?')) return;

            if (batchResult) batchResult.textContent = 'Enviando...';
            var sent = 0, failed = 0, total = btns.length, pending = total;

            function done() {
                if (batchResult) batchResult.textContent = 'Concluído: ' + sent + ' enviados, ' + failed + ' falhas de ' + total + '.';
            }

            btns.forEach(function(btn){
                var match = btn.getAttribute('onclick').match(/sendWa\((\d+)/);
                if (!match) { pending--; if (pending === 0) done(); return; }
                var pid = parseInt(match[1], 10);

                var fd2 = new FormData();
                fd2.append('_csrf', csrf);
                fd2.append('patient_id', pid);
                fd2.append('template_code', code);

                fetch('/patients/whatsapp/send-json', {
                    method: 'POST',
                    body: fd2,
                    credentials: 'same-origin'
                })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    var statusEl = document.getElementById('wa-status-' + pid);
                    if (data.ok) { sent++; if (statusEl) statusEl.textContent = '✓ Enviado'; }
                    else { failed++; if (statusEl) statusEl.textContent = '✗ ' + (data.error || 'Erro'); }
                })
                .catch(function(){
                    failed++;
                    var statusEl = document.getElementById('wa-status-' + pid);
                    if (statusEl) statusEl.textContent = '✗ Falha';
                })
                .finally(function(){
                    pending--;
                    if (pending === 0) done();
                });
            });
        });
    }
})();
</script>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
