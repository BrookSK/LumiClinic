<?php
$title = 'Anamnese';
$csrf  = $_SESSION['_csrf'] ?? '';
$patient   = $patient ?? null;
$templates = $templates ?? [];
$responses = $responses ?? [];
$waTemplates = $wa_templates ?? [];

$patientId = (int)($patient['id'] ?? 0);

$templateMap = [];
foreach ($templates as $t) {
    $tid = (int)($t['id'] ?? 0);
    if ($tid > 0) $templateMap[$tid] = (string)($t['name'] ?? '');
}

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

ob_start();
?>

<!-- Cabeçalho -->
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px;">Anamnese · <?= count($responses) ?> resposta<?= count($responses) !== 1 ? 's' : '' ?></div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= $patientId ?>">Paciente</a>
    </div>
</div>

<!-- Ações: preencher aqui ou enviar -->
<?php if (!empty($templates) && $can('anamnesis.fill')): ?>
<div class="lc-card" style="margin-bottom:16px;">
    <div class="lc-card__header" style="font-weight:700;">Nova anamnese</div>
    <div class="lc-card__body">
        <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr; align-items:end;">
            <div class="lc-field">
                <label class="lc-label">Template</label>
                <select class="lc-select" id="template_select">
                    <option value="">Selecione</option>
                    <?php foreach ($templates as $t): ?>
                        <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars((string)$t['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
                <a id="btn-fill-here" class="lc-btn lc-btn--primary" href="#" onclick="return goFill()">Preencher aqui</a>
                <button type="button" class="lc-btn lc-btn--secondary" onclick="toggleSendPanel()">Enviar ao paciente ▾</button>
            </div>
        </div>

        <!-- Painel de envio (oculto) -->
        <div id="send-panel" style="display:none; margin-top:14px; padding-top:14px; border-top:1px solid rgba(0,0,0,.08);">
            <div class="lc-muted" style="font-size:13px; margin-bottom:12px;">
                O paciente receberá um link para preencher a anamnese. Ao final, ele assina digitalmente.
            </div>
            <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr 1fr;">
                <!-- Via WhatsApp -->
                <div class="lc-card lc-card--soft" style="margin:0; padding:14px;">
                    <div style="font-weight:700; margin-bottom:8px;">📱 WhatsApp</div>
                    <?php
                    $phone = trim((string)($patient['phone'] ?? ''));
                    $waOptIn = (int)($patient['whatsapp_opt_in'] ?? 0);
                    ?>
                    <?php if ($phone !== '' && $waOptIn): ?>
                        <div class="lc-muted" style="font-size:12px; margin-bottom:8px;">
                            Envia para: <strong><?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" style="width:100%;" onclick="sendAnamnesis('whatsapp')">Enviar via WhatsApp</button>
                    <?php elseif ($phone === ''): ?>
                        <div class="lc-muted" style="font-size:12px;">Paciente sem telefone cadastrado.</div>
                    <?php else: ?>
                        <div class="lc-muted" style="font-size:12px;">Paciente sem opt-in de WhatsApp.</div>
                    <?php endif; ?>
                </div>

                <!-- Via E-mail -->
                <div class="lc-card lc-card--soft" style="margin:0; padding:14px;">
                    <div style="font-weight:700; margin-bottom:8px;">✉️ E-mail</div>
                    <div class="lc-muted" style="font-size:12px; margin-bottom:8px;">
                        Envia para: <strong><?= htmlspecialchars((string)($patient['email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <?php if (($patient['email'] ?? '') !== ''): ?>
                        <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" style="width:100%;" onclick="sendAnamnesis('email')">Enviar por e-mail</button>
                    <?php else: ?>
                        <div class="lc-muted" style="font-size:12px;">Paciente sem e-mail cadastrado.</div>
                    <?php endif; ?>
                </div>

                <!-- Via Portal -->
                <div class="lc-card lc-card--soft" style="margin:0; padding:14px;">
                    <div style="font-weight:700; margin-bottom:8px;">🌐 Portal</div>
                    <div class="lc-muted" style="font-size:12px; margin-bottom:8px;">Disponibiliza no portal do paciente.</div>
                    <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" style="width:100%;" onclick="sendAnamnesis('portal')">Disponibilizar no portal</button>
                </div>
            </div>
            <div id="send-result" style="margin-top:10px;"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Respostas anteriores -->
<div class="lc-card">
    <div class="lc-card__header" style="font-weight:700;">Respostas</div>
    <div class="lc-card__body" style="padding:0;">
        <?php if (empty($responses)): ?>
            <div class="lc-muted" style="padding:20px; text-align:center;">Nenhuma anamnese preenchida ainda.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Template</th>
                    <th>Assinatura</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($responses as $r): ?>
                    <?php
                    $tid = (int)($r['template_id'] ?? 0);
                    $snapName = trim((string)($r['template_name_snapshot'] ?? ''));
                    $tName = $snapName !== '' ? $snapName : ($templateMap[$tid] ?? ('Template #' . $tid));
                    $createdAt = (string)($r['created_at'] ?? '');
                    $dateFmt = '';
                    try { $dateFmt = (new \DateTimeImmutable($createdAt))->format('d/m/Y H:i'); } catch (\Throwable $e) { $dateFmt = $createdAt; }
                    $hasSig = !empty($r['signature_data_url']);
                    ?>
                    <tr>
                        <td style="white-space:nowrap; font-size:13px;"><?= htmlspecialchars($dateFmt, ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($tName, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($hasSig): ?>
                                <span class="lc-badge lc-badge--success" style="font-size:11px;">✓ Assinado</span>
                            <?php else: ?>
                                <span class="lc-badge lc-badge--secondary" style="font-size:11px;">Sem assinatura</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="lc-flex lc-gap-sm">
                                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/anamnesis/response?id=<?= (int)$r['id'] ?>">Ver</a>
                                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/anamnesis/export-pdf?id=<?= (int)$r['id'] ?>" target="_blank">PDF</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
(function(){
    var csrf = <?= json_encode($csrf, JSON_UNESCAPED_UNICODE) ?>;
    var patientId = <?= $patientId ?>;

    window.goFill = function() {
        var tid = document.getElementById('template_select').value;
        if (!tid) { alert('Selecione um template.'); return false; }
        window.location.href = '/anamnesis/fill?patient_id=' + patientId + '&template_id=' + encodeURIComponent(tid);
        return false;
    };

    window.toggleSendPanel = function() {
        var p = document.getElementById('send-panel');
        if (!p) return;
        p.style.display = p.style.display === 'none' ? 'block' : 'none';
    };

    window.sendAnamnesis = function(channel) {
        var tid = document.getElementById('template_select').value;
        if (!tid) { alert('Selecione um template primeiro.'); return; }

        var resultEl = document.getElementById('send-result');
        if (resultEl) resultEl.textContent = 'Enviando...';

        fetch('/anamnesis/send-link', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                _csrf: csrf,
                patient_id: patientId,
                template_id: parseInt(tid, 10),
                channel: channel,
                wa_template_code: 'anamnesis_request',
            }),
            credentials: 'same-origin',
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (resultEl) {
                if (data.ok) {
                    resultEl.innerHTML = '<div class="lc-alert lc-alert--success" style="margin:0;">' + (data.message || 'Enviado com sucesso.') + '</div>';
                } else {
                    resultEl.innerHTML = '<div class="lc-alert lc-alert--danger" style="margin:0;">' + (data.error || 'Erro ao enviar.') + '</div>';
                }
            }
        })
        .catch(function(){
            if (resultEl) resultEl.innerHTML = '<div class="lc-alert lc-alert--danger" style="margin:0;">Falha na requisição.</div>';
        });
    };
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
