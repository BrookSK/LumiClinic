<?php
$title = 'Editar registro';
$csrf  = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$patient      = $patient ?? null;
$record       = $record ?? null;
$professionals = $professionals ?? [];
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

<!-- Formulário de edição -->
<div class="lc-card">
    <div class="lc-flex lc-flex--between lc-flex--center" style="padding:14px 16px; border-bottom:1px solid rgba(0,0,0,.06);">
        <div style="font-weight:700; font-size:16px;">
            <?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="lc-flex lc-gap-sm">
            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/patients/clinical-sheet?patient_id=<?= $patientId ?>">Ficha clínica</a>
            <?php if (!empty($images) || !empty($imagePairs)): ?>
                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/medical-images?patient_id=<?= $patientId ?>">Imagens</a>
            <?php endif; ?>
            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/medical-records?patient_id=<?= $patientId ?>">Voltar</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger" style="margin:12px 16px 0;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/medical-records/edit" style="padding:16px;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="patient_id" value="<?= $patientId ?>" />
        <input type="hidden" name="id" value="<?= (int)($record['id'] ?? 0) ?>" />

        <?php
        $att = (string)($record['attended_at'] ?? '');
        $attValue = $att !== '' ? str_replace(' ', 'T', substr($att, 0, 16)) : '';
        $currentProf = (int)($record['professional_id'] ?? 0);
        $currentProfName = '';
        foreach ($professionals as $pr) {
            if ((int)$pr['id'] === $currentProf) { $currentProfName = (string)$pr['name']; break; }
        }
        ?>

        <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr; align-items:end; margin-bottom:14px;">
            <div class="lc-field">
                <label class="lc-label">Data/hora</label>
                <input class="lc-input" type="datetime-local" name="attended_at" value="<?= htmlspecialchars($attValue, ENT_QUOTES, 'UTF-8') ?>" required />
            </div>
            <div class="lc-field">
                <label class="lc-label">Procedimento</label>
                <input class="lc-input" type="text" name="procedure_type" value="<?= htmlspecialchars((string)($record['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: Botox, Limpeza..." />
            </div>
        </div>

        <?php if ($currentProf > 0): ?>
            <input type="hidden" name="professional_id" value="<?= $currentProf ?>" />
            <div class="lc-muted" style="font-size:12px; margin-bottom:14px;">
                Profissional: <strong><?= htmlspecialchars($currentProfName, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        <?php else: ?>
            <div class="lc-field" style="margin-bottom:14px;">
                <label class="lc-label">Profissional</label>
                <select class="lc-select" name="professional_id">
                    <option value="">(opcional)</option>
                    <?php foreach ($professionals as $pr): ?>
                        <option value="<?= (int)$pr['id'] ?>"><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php
        $editFields = [
            'clinical_description' => ['label' => 'Descrição clínica', 'rows' => 5, 'value' => (string)($record['clinical_description'] ?? '')],
            'clinical_evolution'   => ['label' => 'Evolução',          'rows' => 5, 'value' => (string)($record['clinical_evolution'] ?? '')],
            'notes'                => ['label' => 'Notas',             'rows' => 3, 'value' => (string)($record['notes'] ?? '')],
        ];
        foreach ($editFields as $key => $cfg):
        ?>
        <div class="lc-field" style="margin-bottom:14px;">
            <div class="lc-flex lc-flex--between" style="align-items:center; margin-bottom:4px;">
                <label class="lc-label" style="margin:0;"><?= $cfg['label'] ?></label>
                <button class="lc-btn lc-btn--secondary lc-btn--sm" type="button"
                    data-lc-mic="<?= $key ?>"
                    style="width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center;"
                    title="Gravar">🎤</button>
            </div>
            <div class="lc-muted" id="lcMicStatus_<?= $key ?>" style="display:none; font-size:12px; margin-bottom:4px;"></div>
            <textarea class="lc-input" name="<?= $key ?>" rows="<?= $cfg['rows'] ?>" id="lcField_<?= $key ?>"><?= htmlspecialchars($cfg['value'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <?php endforeach; ?>

        <div class="lc-flex lc-gap-sm">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/medical-records?patient_id=<?= $patientId ?>">Cancelar</a>
        </div>
    </form>
</div>

<script>
(function(){
  try {
    var patientId = <?= $patientId ?>;
    var medicalRecordId = <?= (int)($record['id'] ?? 0) ?>;
    var appointmentId = <?= (int)($record['appointment_id'] ?? 0) ?>;
    var professionalId = <?= (int)($record['professional_id'] ?? 0) ?>;
    var csrf = <?= json_encode($csrf, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function byId(id){ return document.getElementById(id); }
    function setStatus(k, msg){ var el = byId('lcMicStatus_'+k); if(!el) return; el.textContent=msg||''; el.style.display=msg?'block':'none'; }
    function appendText(k, text){ var ta = byId('lcField_'+k); if(!ta) return; var cur=(ta.value||'').trim(); var add=(text||'').trim(); if(!add) return; ta.value=(cur?(cur+"\n\n"):"")+add; }

    var active = null;

    async function startRecording(fieldKey, btn){
      if (active) { setStatus(active.fieldKey,''); if(active.timer)clearInterval(active.timer); try{active.stop();}catch(e){} }
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || typeof MediaRecorder === 'undefined') { setStatus(fieldKey,'Sem suporte a microfone.'); return; }

      // Verificar limite de transcrição
      try {
        var statusResp = await fetch('/medical-records/audio/transcription-status', {credentials:'same-origin'});
        var statusJson = await statusResp.json();
        if (statusJson && statusJson.blocked) {
          setStatus(fieldKey, 'Limite de transcrição atingido. Faça upgrade do plano.');
          return;
        }
      } catch(e) {}

      var stream = await navigator.mediaDevices.getUserMedia({audio:true});
      var chunks = [], rec = new MediaRecorder(stream);
      rec.ondataavailable = function(e){ if(e.data&&e.data.size) chunks.push(e.data); };

      function stopTracks(){ try{stream.getTracks().forEach(function(t){t.stop()});}catch(e){} }
      async function doStop(){ try{rec.stop();}catch(e){} stopTracks(); }
      function setBtnRec(r){ if(!btn) return; btn.textContent=r?'■':'🎤'; btn.title=r?'Parar':'Gravar'; }

      var startTime = Date.now();

      rec.onstop = async function(){
        try {
          if (!chunks.length) { setStatus(fieldKey,''); setBtnRec(false); active=null; return; }
          var blob = new Blob(chunks,{type:chunks[0]?chunks[0].type:'audio/webm'});
          var file = new File([blob],'recording.webm',{type:blob.type});
          setStatus(fieldKey,'Transcrevendo...'); setBtnRec(false);
          var fd = new FormData();
          fd.append('_csrf',csrf); fd.append('patient_id',String(patientId));
          fd.append('medical_record_id',String(medicalRecordId));
          fd.append('appointment_id',String(appointmentId));
          fd.append('professional_id',String(professionalId));
          fd.append('audio',file);
          fd.append('duration_seconds', String(Math.round((Date.now() - startTime) / 1000)));
          var resp = await fetch('/medical-records/audio/transcribe-json',{method:'POST',body:fd,credentials:'same-origin'});
          var json = null; try{json=await resp.json();}catch(e){}
          if (!resp.ok||!json||json.ok!==true) { setStatus(fieldKey,(json&&json.error)?String(json.error):'Falha ao transcrever.'); active=null; return; }
          var t = String(json.transcript||'').trim();
          if (t) { appendText(fieldKey,t); setStatus(fieldKey,''); } else { setStatus(fieldKey,'Transcrição vazia.'); }
        } catch(e) { setStatus(fieldKey,'Falha ao transcrever.'); } finally { active=null; }
      };

      rec.start(60000); setBtnRec(true); setStatus(fieldKey,'Gravando...');
      var timerInterval = setInterval(function(){
        if (!active || active.fieldKey !== fieldKey) { clearInterval(timerInterval); return; }
        var elapsed = Math.floor((Date.now() - startTime) / 1000);
        var min = Math.floor(elapsed / 60);
        var sec = elapsed % 60;
        setStatus(fieldKey, 'Gravando... ' + min + ':' + (sec < 10 ? '0' : '') + sec);
      }, 1000);
      active = {fieldKey:fieldKey, stop:doStop, setBtnRecording:setBtnRec, timer:timerInterval};
    }

    document.querySelectorAll('[data-lc-mic]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var k = String(btn.getAttribute('data-lc-mic')||'').trim();
        if (!k) return;
        if (active&&active.fieldKey===k) { var a=active; active=null; if(a.timer)clearInterval(a.timer); try{a.stop();}catch(e){} if(a.setBtnRecording) a.setBtnRecording(false); return; }
        startRecording(k,btn).catch(function(){ setStatus(k,'Erro ao iniciar microfone.'); });
      });
    });
  } catch(e) {}
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
