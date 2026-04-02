<?php
$title = 'Novo registro';
$csrf  = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$patient      = $patient ?? null;
$professionals = $professionals ?? [];
$records      = $records ?? [];
$prefill      = $prefill ?? [];
$alerts       = $alerts ?? [];
$allergies    = $allergies ?? [];
$conditions   = $conditions ?? [];
$images       = $images ?? [];
$imagePairs   = $image_pairs ?? [];

$patientId = (int)($patient['id'] ?? 0);
$activeAlerts = array_filter($alerts, fn($a) => (int)($a['active'] ?? 1) === 1);

ob_start();
?>

<!-- Alertas clínicos — só aparece se existir algo -->
<?php if (!empty($activeAlerts) || !empty($allergies)): ?>
<div style="background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:12px 16px; margin-bottom:14px; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
    <span style="font-weight:700; color:#b91c1c; font-size:13px; margin-right:4px;">⚠ Atenção:</span>
    <?php foreach ($activeAlerts as $al): ?>
        <span style="background:#fee2e2; color:#b91c1c; border-radius:6px; padding:3px 10px; font-size:12px; font-weight:600;">
            <?= htmlspecialchars((string)($al['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </span>
    <?php endforeach; ?>
    <?php foreach ($allergies as $al): ?>
        <span style="background:#fee2e2; color:#b91c1c; border-radius:6px; padding:3px 10px; font-size:12px; font-weight:600;">
            🚫 <?= htmlspecialchars((string)($al['trigger_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </span>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Formulário -->
<div class="lc-card">
    <div class="lc-flex lc-flex--between lc-flex--center" style="padding:14px 16px; border-bottom:1px solid rgba(0,0,0,.06);">
        <div style="font-weight:700; font-size:16px;">
            Novo registro — <?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="lc-flex lc-gap-sm">
            <?php
            $appointmentIdForFinalize = (int)($_GET['appointment_id'] ?? 0);
            if ($appointmentIdForFinalize > 0):
            ?>
                <a class="lc-btn lc-btn--primary lc-btn--sm" href="/schedule/complete-materials?id=<?= $appointmentIdForFinalize ?>&date=<?= urlencode(date('Y-m-d')) ?>">
                    ✓ Finalizar atendimento
                </a>
            <?php endif; ?>
            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/medical-records?patient_id=<?= $patientId ?>">Voltar</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger" style="margin:12px 16px 0;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/medical-records/create" style="padding:16px;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="patient_id" value="<?= $patientId ?>" />

        <?php
        $prefAtt = trim((string)($prefill['attended_at'] ?? ''));
        if ($prefAtt !== '') {
            $prefAtt = str_replace(' ', 'T', $prefAtt);
            if (strlen($prefAtt) === 19) $prefAtt = substr($prefAtt, 0, 16);
        }
        if ($prefAtt === '') $prefAtt = date('Y-m-d\TH:i');
        $prefProf = (int)($prefill['professional_id'] ?? 0);
        $prefProfName = '';
        foreach ($professionals as $pr) {
            if ((int)$pr['id'] === $prefProf) {
                $prefProfName = (string)$pr['name'];
                break;
            }
        }
        ?>

        <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr <?= $prefProf > 0 ? '' : '1fr ' ?>1fr; align-items:end;">
            <div class="lc-field">
                <label class="lc-label">Data/hora</label>
                <input class="lc-input" type="datetime-local" name="attended_at" value="<?= htmlspecialchars($prefAtt, ENT_QUOTES, 'UTF-8') ?>" required />
            </div>

            <?php if ($prefProf > 0): ?>
                <!-- Profissional pré-definido pelo agendamento — não editável -->
                <input type="hidden" name="professional_id" value="<?= $prefProf ?>" />
            <?php else: ?>
                <div class="lc-field">
                    <label class="lc-label">Profissional</label>
                    <select class="lc-select" name="professional_id">
                        <option value="">(opcional)</option>
                        <?php foreach ($professionals as $pr): ?>
                            <option value="<?= (int)$pr['id'] ?>"><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="lc-field">
                <label class="lc-label">Procedimento</label>
                <input class="lc-input" type="text" name="procedure_type"
                    value="<?= htmlspecialchars((string)($prefill['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Ex: Botox, Limpeza..." />
            </div>
        </div>

        <?php if ($prefProf > 0 && $prefProfName !== ''): ?>
            <div class="lc-muted" style="font-size:12px; margin-top:4px;">
                Profissional: <strong><?= htmlspecialchars($prefProfName, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        <?php endif; ?>

        <?php
        $fields = [
            'clinical_description' => 'Descrição clínica',
            'clinical_evolution'   => 'Evolução',
            'notes'                => 'Notas',
        ];
        foreach ($fields as $key => $label):
        ?>
        <div class="lc-field" style="margin-top:14px;">
            <div class="lc-flex lc-flex--between" style="align-items:center; margin-bottom:4px;">
                <label class="lc-label" style="margin:0;"><?= $label ?></label>
                <button class="lc-btn lc-btn--secondary lc-btn--sm" type="button"
                    data-lc-mic="<?= $key ?>"
                    style="width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center;"
                    title="Gravar">🎤</button>
            </div>
            <div class="lc-muted" id="lcMicStatus_<?= $key ?>" style="display:none; font-size:12px; margin-bottom:4px;"></div>
            <textarea class="lc-input" name="<?= $key ?>" rows="<?= $key === 'notes' ? 3 : 5 ?>" id="lcField_<?= $key ?>"></textarea>
        </div>
        <?php endforeach; ?>

        <div class="lc-flex lc-gap-sm" style="margin-top:16px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar registro</button>
            <a class="lc-btn lc-btn--secondary" href="/medical-records?patient_id=<?= $patientId ?>">Cancelar</a>
        </div>
    </form>
</div>

<!-- Histórico com "mostrar mais" via JS -->
<?php if (!empty($records)): ?>
<?php
$historyData = [];
foreach ($records as $r) {
    $attendedAt = (string)($r['attended_at'] ?? '');
    $dateDisplay = '';
    try { $dateDisplay = (new \DateTimeImmutable($attendedAt))->format('d/m/Y'); } catch (\Throwable $e) { $dateDisplay = $attendedAt; }
    $desc = trim((string)($r['clinical_description'] ?? ''));
    $evol = trim((string)($r['clinical_evolution'] ?? ''));
    $preview = $desc !== '' ? $desc : $evol;
    $preview = mb_strlen($preview, 'UTF-8') > 80 ? mb_substr($preview, 0, 80, 'UTF-8') . '…' : $preview;
    $historyData[] = [
        'date'      => $dateDisplay,
        'procedure' => trim((string)($r['procedure_type'] ?? '')),
        'preview'   => $preview,
        'url'       => '/medical-records/edit?patient_id=' . $patientId . '&id=' . (int)$r['id'],
    ];
}
?>
<div style="margin-top:16px;">
    <div class="lc-flex lc-flex--between lc-flex--center" style="margin-bottom:8px; padding:0 2px;">
        <span class="lc-muted" style="font-size:12px;">Histórico (<?= count($records) ?> registros)</span>
        <a class="lc-muted" style="font-size:12px;" href="/medical-records?patient_id=<?= $patientId ?>">Ver tudo →</a>
    </div>
    <div id="historyList" style="display:flex; flex-direction:column; gap:6px;"></div>
    <div id="historyMore" style="display:none; text-align:center; margin-top:8px;">
        <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="showMoreHistory()">Mostrar mais</button>
    </div>
</div>
<script>
(function(){
    var data = <?= json_encode($historyData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var shown = 0, step = 5;
    var list = document.getElementById('historyList');
    var moreBtn = document.getElementById('historyMore');
    function esc(s){ return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function renderItem(it) {
        var el = document.createElement('div');
        el.style.cssText = 'background:var(--lc-surface,#fffdf8);border:1px solid rgba(0,0,0,.08);border-radius:8px;padding:8px 12px;display:flex;justify-content:space-between;align-items:center;gap:10px;';
        el.innerHTML = '<div style="min-width:0;flex:1;">'
            + '<span style="font-weight:600;font-size:13px;">' + esc(it.date) + '</span>'
            + (it.procedure ? '<span style="color:var(--lc-muted,#6b7280);font-size:12px;margin-left:6px;">· ' + esc(it.procedure) + '</span>' : '')
            + (it.preview ? '<div style="color:var(--lc-muted,#6b7280);font-size:12px;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + esc(it.preview) + '</div>' : '')
            + '</div>'
            + '<a href="' + esc(it.url) + '" style="flex-shrink:0;white-space:nowrap;" class="lc-btn lc-btn--secondary lc-btn--sm">Abrir</a>';
        return el;
    }
    window.showMoreHistory = function() {
        var end = Math.min(shown + step, data.length);
        for (var i = shown; i < end; i++) list.appendChild(renderItem(data[i]));
        shown = end;
        moreBtn.style.display = shown < data.length ? 'block' : 'none';
    };
    showMoreHistory();
})();
</script>
<?php endif; ?>

<script>
(function(){
  try {
    var patientId = <?= $patientId ?>;
    var appointmentId = <?= (int)($_GET['appointment_id'] ?? 0) ?>;
    var professionalId = <?= (int)($prefill['professional_id'] ?? 0) ?>;
    var csrf = <?= json_encode($csrf, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function byId(id){ return document.getElementById(id); }
    function setStatus(k, msg){ var el = byId('lcMicStatus_'+k); if(!el) return; el.textContent=msg||''; el.style.display=msg?'block':'none'; }
    function appendText(k, text){ var ta = byId('lcField_'+k); if(!ta) return; var cur=(ta.value||'').trim(); var add=(text||'').trim(); if(!add) return; ta.value=(cur?(cur+"\n\n"):"")+add; }

    var active = null;

    async function startRecording(fieldKey, btn){
      if (active) { setStatus(active.fieldKey,''); try{active.stop();}catch(e){} }
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { setStatus(fieldKey,'Sem suporte a microfone.'); return; }
      if (typeof MediaRecorder === 'undefined') { setStatus(fieldKey,'Não suportado neste navegador.'); return; }

      var stream = await navigator.mediaDevices.getUserMedia({audio:true});
      var chunks = [], rec = new MediaRecorder(stream);
      rec.ondataavailable = function(e){ if(e.data&&e.data.size) chunks.push(e.data); };

      function stopTracks(){ try{stream.getTracks().forEach(function(t){t.stop()});}catch(e){} }
      async function doStop(){ try{rec.stop();}catch(e){} stopTracks(); }
      function setBtnRec(r){ if(!btn) return; btn.textContent=r?'■':'🎤'; btn.title=r?'Parar':'Gravar'; }

      rec.onstop = async function(){
        try {
          if (!chunks.length) { setStatus(fieldKey,''); setBtnRec(false); active=null; return; }
          var blob = new Blob(chunks,{type:chunks[0]?chunks[0].type:'audio/webm'});
          var file = new File([blob],'recording.webm',{type:blob.type});
          setStatus(fieldKey,'Transcrevendo...'); setBtnRec(false);
          var fd = new FormData();
          fd.append('_csrf',csrf); fd.append('patient_id',String(patientId));
          fd.append('appointment_id',String(appointmentId)); fd.append('professional_id',String(professionalId));
          fd.append('audio',file);
          var resp = await fetch('/medical-records/audio/transcribe-json',{method:'POST',body:fd,credentials:'same-origin'});
          var json = null; try{json=await resp.json();}catch(e){}
          if (!resp.ok||!json||json.ok!==true) { setStatus(fieldKey,(json&&json.error)?String(json.error):'Falha ao transcrever.'); active=null; return; }
          var t = String(json.transcript||'').trim();
          if (t) { appendText(fieldKey,t); setStatus(fieldKey,''); } else { setStatus(fieldKey,'Transcrição vazia.'); }
        } catch(e) { setStatus(fieldKey,'Falha ao transcrever.'); } finally { active=null; }
      };

      rec.start(); setBtnRec(true); setStatus(fieldKey,'Gravando...');
      active = {fieldKey:fieldKey, stop:doStop, setBtnRecording:setBtnRec};
    }

    document.querySelectorAll('[data-lc-mic]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var k = String(btn.getAttribute('data-lc-mic')||'').trim();
        if (!k) return;
        if (active&&active.fieldKey===k) { var a=active; active=null; try{a.stop();}catch(e){} if(a.setBtnRecording) a.setBtnRecording(false); return; }
        startRecording(k,btn).catch(function(){ setStatus(k,'Erro ao iniciar microfone.'); });
      });
    });
  } catch(e) {}
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
