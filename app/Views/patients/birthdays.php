<?php
$title = 'Aniversariantes e Follow-up';
$patients    = $patients ?? [];
$followUp    = $follow_up ?? [];
$month       = isset($month) ? (int)$month : (int)date('n');
$days        = isset($days) ? (int)$days : 180;
$waTemplates = $wa_templates ?? [];
$csrf        = $_SESSION['_csrf'] ?? '';
$tab         = isset($tab) ? (string)$tab : 'birthdays';

$monthNames = [
    1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
    7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro',
];

$activeTemplates = array_values(array_filter($waTemplates, fn($t) => (string)($t['status'] ?? 'active') === 'active'));

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div style="font-weight:800; font-size:18px;">Pacientes</div>
    <a class="lc-btn lc-btn--secondary" href="/patients">Lista de pacientes</a>
</div>

<!-- Abas -->
<div class="lc-flex lc-gap-sm" style="margin-bottom:14px;">
    <a class="lc-btn <?= $tab === 'birthdays' ? 'lc-btn--primary' : 'lc-btn--secondary' ?>" href="/patients/birthdays?tab=birthdays&month=<?= $month ?>">
        🎂 Aniversariantes
        <?php if (!empty($patients)): ?><span class="lc-badge lc-badge--secondary" style="margin-left:4px;"><?= count($patients) ?></span><?php endif; ?>
    </a>
    <a class="lc-btn <?= $tab === 'followup' ? 'lc-btn--primary' : 'lc-btn--secondary' ?>" href="/patients/birthdays?tab=followup&days=<?= $days ?>">
        📞 Follow-up
        <?php if (!empty($followUp)): ?><span class="lc-badge lc-badge--secondary" style="margin-left:4px;"><?= count($followUp) ?></span><?php endif; ?>
    </a>
</div>

<?php if ($tab === 'birthdays'): ?>
<!-- ═══ ABA ANIVERSARIANTES ═══ -->
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <form method="get" action="/patients/birthdays" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
            <input type="hidden" name="tab" value="birthdays" />
            <div class="lc-field">
                <label class="lc-label">Mês</label>
                <select class="lc-select" name="month">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button class="lc-btn lc-btn--primary" type="submit">Filtrar</button>
        </form>
    </div>
</div>

<?php if (!empty($activeTemplates)): ?>
<div class="lc-card" style="margin-bottom:14px; border-left:4px solid #eeb810;">
    <div class="lc-card__body lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center;">
        <span style="font-weight:600; font-size:13px;">Envio em lote:</span>
        <select class="lc-select" id="batchTemplate" style="max-width:220px;">
            <?php foreach ($activeTemplates as $t): ?>
                <option value="<?= htmlspecialchars((string)$t['code'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$t['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" id="btnSendAll">Enviar para todos com WhatsApp</button>
        <span id="batchResult" class="lc-muted" style="font-size:12px;"></span>
    </div>
</div>
<?php endif; ?>

<?php if (empty($patients)): ?>
    <div class="lc-card"><div class="lc-card__body lc-muted" style="text-align:center; padding:30px;">Nenhum aniversariante em <?= $monthNames[$month] ?>.</div></div>
<?php else: ?>
    <div style="display:flex; flex-direction:column; gap:8px;">
        <?php foreach ($patients as $p): ?>
            <?php
            $birthDate = (string)($p['birth_date'] ?? '');
            $day = $birthDate !== '' && strlen($birthDate) >= 10 ? substr($birthDate, 8, 2) . '/' . substr($birthDate, 5, 2) : '';
            $phone = trim((string)($p['phone'] ?? ''));
            $waOptIn = (int)($p['whatsapp_opt_in'] ?? 0);
            $waLink = $phone !== '' ? ('https://wa.me/' . preg_replace('/\D/', '', $phone)) : '';
            ?>
            <div class="lc-card" style="margin:0;">
                <div class="lc-flex lc-flex--between lc-flex--center" style="padding:12px 16px; gap:10px;">
                    <div class="lc-flex lc-gap-md" style="align-items:center; flex:1; min-width:0;">
                        <div style="width:40px; height:40px; border-radius:50%; background:#fef9c3; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">🎂</div>
                        <div style="min-width:0;">
                            <div style="font-weight:700; font-size:14px;"><?= htmlspecialchars((string)($p['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="lc-muted" style="font-size:12px;">
                                <?= htmlspecialchars($day, ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($phone !== ''): ?> · <?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="lc-flex lc-gap-sm" style="flex-shrink:0; align-items:center;">
                        <?php if (!empty($activeTemplates) && $waOptIn && $phone !== ''): ?>
                            <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="sendWa(<?= (int)$p['id'] ?>, this)">WhatsApp</button>
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <form method="get" action="/patients/birthdays" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
            <input type="hidden" name="tab" value="followup" />
            <div class="lc-field">
                <label class="lc-label">Sem consulta há mais de</label>
                <select class="lc-select" name="days">
                    <?php foreach ([90,120,180,270,365] as $d): ?>
                        <option value="<?= $d ?>" <?= $d === $days ? 'selected' : '' ?>><?= $d ?> dias</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="lc-btn lc-btn--primary" type="submit">Filtrar</button>
        </form>
    </div>
</div>

<?php if (!empty($activeTemplates)): ?>
<div class="lc-card" style="margin-bottom:14px; border-left:4px solid #eeb810;">
    <div class="lc-card__body lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center;">
        <span style="font-weight:600; font-size:13px;">Envio em lote:</span>
        <select class="lc-select" id="batchTemplateFu" style="max-width:220px;">
            <?php foreach ($activeTemplates as $t): ?>
                <option value="<?= htmlspecialchars((string)$t['code'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$t['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" id="btnSendAllFu">Enviar para todos com WhatsApp</button>
        <span id="batchResultFu" class="lc-muted" style="font-size:12px;"></span>
    </div>
</div>
<?php endif; ?>

<?php if (empty($followUp)): ?>
    <div class="lc-card"><div class="lc-card__body lc-muted" style="text-align:center; padding:30px;">Nenhum paciente sem consulta há mais de <?= $days ?> dias.</div></div>
<?php else: ?>
    <div style="display:flex; flex-direction:column; gap:8px;">
        <?php foreach ($followUp as $p): ?>
            <?php
            $lastAt = trim((string)($p['last_appointment_at'] ?? ''));
            $lastFmt = '';
            if ($lastAt !== '') { try { $lastFmt = (new \DateTimeImmutable($lastAt))->format('d/m/Y'); } catch (\Throwable $e) { $lastFmt = $lastAt; } }
            $phone = trim((string)($p['phone'] ?? ''));
            $waOptIn = (int)($p['whatsapp_opt_in'] ?? 0);
            ?>
            <div class="lc-card" style="margin:0;">
                <div class="lc-flex lc-flex--between lc-flex--center" style="padding:12px 16px; gap:10px;">
                    <div class="lc-flex lc-gap-md" style="align-items:center; flex:1; min-width:0;">
                        <div style="width:40px; height:40px; border-radius:50%; background:#fef2f2; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">📞</div>
                        <div style="min-width:0;">
                            <div style="font-weight:700; font-size:14px;"><?= htmlspecialchars((string)($p['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="lc-muted" style="font-size:12px;">
                                Última consulta: <?= $lastFmt !== '' ? htmlspecialchars($lastFmt, ENT_QUOTES, 'UTF-8') : '<span style="color:#b91c1c;">Nunca</span>' ?>
                                <?php if ($phone !== ''): ?> · <?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="lc-flex lc-gap-sm" style="flex-shrink:0; align-items:center;">
                        <?php if (!empty($activeTemplates) && $waOptIn && $phone !== ''): ?>
                            <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="sendWa(<?= (int)$p['id'] ?>, this)">WhatsApp</button>
                            <span class="lc-muted" id="wa-status-<?= (int)$p['id'] ?>" style="font-size:11px;"></span>
                        <?php endif; ?>
                        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/patients/view?id=<?= (int)$p['id'] ?>">Ver</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php endif; ?>

<?php if (!empty($activeTemplates)): ?>
<script>
(function(){
    var csrf = <?= json_encode($csrf, JSON_UNESCAPED_UNICODE) ?>;

    window.sendWa = function(pid, btn) {
        var tplEl = document.getElementById('batchTemplate') || document.getElementById('batchTemplateFu');
        var code = tplEl ? tplEl.value : '';
        if (!code) return;
        var s = document.getElementById('wa-status-' + pid);
        if (btn) btn.disabled = true;
        if (s) s.textContent = '...';
        fetch('/patients/whatsapp/send-json', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({_csrf:csrf, patient_id:pid, template_code:code}),
            credentials:'same-origin'
        }).then(function(r){return r.json()}).then(function(d){
            if(btn) btn.disabled=false;
            if(s) s.textContent = d.ok ? '✓' : '✗';
        }).catch(function(){ if(btn) btn.disabled=false; if(s) s.textContent='✗'; });
    };

    function batchSend(btnId, tplId, resultId) {
        var btn = document.getElementById(btnId);
        var tplEl = document.getElementById(tplId);
        var res = document.getElementById(resultId);
        if (!btn) return;
        btn.addEventListener('click', function(){
            var code = tplEl ? tplEl.value : '';
            if (!code) return;
            var waBtns = document.querySelectorAll('[onclick^="sendWa"]');
            if (!waBtns.length) { if(res) res.textContent='Nenhum paciente com WhatsApp.'; return; }
            if (!confirm('Enviar para ' + waBtns.length + ' paciente(s)?')) return;
            if(res) res.textContent='Enviando...';
            var sent=0, fail=0, total=waBtns.length, pending=total;
            waBtns.forEach(function(b){
                var m = b.getAttribute('onclick').match(/sendWa\((\d+)/);
                if(!m){pending--;if(!pending&&res) res.textContent=sent+' ok, '+fail+' falha';return;}
                var pid=parseInt(m[1],10);
                fetch('/patients/whatsapp/send-json',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({_csrf:csrf,patient_id:pid,template_code:code}),credentials:'same-origin'})
                .then(function(r){return r.json()}).then(function(d){if(d.ok)sent++;else fail++;})
                .catch(function(){fail++;})
                .finally(function(){pending--;if(!pending&&res) res.textContent=sent+' enviados, '+fail+' falhas';});
            });
        });
    }
    batchSend('btnSendAll','batchTemplate','batchResult');
    batchSend('btnSendAllFu','batchTemplateFu','batchResultFu');
})();
</script>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
