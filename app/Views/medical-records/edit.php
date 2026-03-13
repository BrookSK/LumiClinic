<?php
$title = 'Editar registro';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$patient = $patient ?? null;
$record = $record ?? null;
$professionals = $professionals ?? [];
$alerts = $alerts ?? [];
$allergies = $allergies ?? [];
$conditions = $conditions ?? [];
$images = $images ?? [];
$imagePairs = $image_pairs ?? [];
ob_start();
?>
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Resumo do paciente</div>
    <div class="lc-card__body">
        <div class="lc-grid lc-grid--3 lc-gap-grid">
            <div class="lc-card" style="margin:0;"><div class="lc-card__body" style="padding:10px;"><div class="lc-muted" style="font-size:12px;">Alertas</div><div style="font-weight:800; font-size:18px; line-height:1;"><?= (int)count($alerts) ?></div></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body" style="padding:10px;"><div class="lc-muted" style="font-size:12px;">Alergias/Contraindicações</div><div style="font-weight:800; font-size:18px; line-height:1;"><?= (int)count($allergies) ?></div></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body" style="padding:10px;"><div class="lc-muted" style="font-size:12px;">Condições</div><div style="font-weight:800; font-size:18px; line-height:1;"><?= (int)count($conditions) ?></div></div></div>
        </div>
        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:10px;">
            <a class="lc-btn lc-btn--secondary" href="/patients/clinical-sheet?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Ficha clínica</a>
            <a class="lc-btn lc-btn--secondary" href="/medical-images?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Imagens clínicas (<?= (int)count($images) ?>)</a>
            <?php if ($imagePairs !== []): ?>
                <a class="lc-btn lc-btn--secondary" href="/medical-images?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Comparações (<?= (int)count($imagePairs) ?>)</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Editar registro - <?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/medical-records/edit">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />
        <input type="hidden" name="id" value="<?= (int)($record['id'] ?? 0) ?>" />

        <label class="lc-label">Atendido em</label>
        <?php
        $att = (string)($record['attended_at'] ?? '');
        $attValue = $att !== '' ? str_replace(' ', 'T', substr($att, 0, 16)) : '';
        ?>
        <input class="lc-input" type="datetime-local" name="attended_at" value="<?= htmlspecialchars($attValue, ENT_QUOTES, 'UTF-8') ?>" required />

        <label class="lc-label">Profissional</label>
        <?php $currentProf = (int)($record['professional_id'] ?? 0); ?>
        <select class="lc-select" name="professional_id">
            <option value="" <?= $currentProf === 0 ? 'selected' : '' ?>>(opcional)</option>
            <?php foreach ($professionals as $pr): ?>
                <option value="<?= (int)$pr['id'] ?>" <?= (int)$pr['id'] === $currentProf ? 'selected' : '' ?>><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <label class="lc-label">Procedimento</label>
        <input class="lc-input" type="text" name="procedure_type" value="<?= htmlspecialchars((string)($record['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

        <div class="lc-flex lc-flex--between" style="gap:10px; align-items:center;">
            <label class="lc-label" style="margin:0;">Descrição clínica</label>
            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="button" data-lc-mic="clinical_description" aria-label="Gravar descrição clínica" title="Gravar" style="width:36px; height:36px; padding:0; display:inline-flex; align-items:center; justify-content:center; line-height:1;">🎤</button>
        </div>
        <div class="lc-muted" id="lcMicStatus_clinical_description" style="margin-top:6px; display:none; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></div>
        <textarea class="lc-input" name="clinical_description" rows="5" id="lcField_clinical_description"><?= htmlspecialchars((string)($record['clinical_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

        <div class="lc-flex lc-flex--between" style="gap:10px; margin-top:10px; align-items:center;">
            <label class="lc-label" style="margin:0;">Evolução</label>
            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="button" data-lc-mic="clinical_evolution" aria-label="Gravar evolução" title="Gravar" style="width:36px; height:36px; padding:0; display:inline-flex; align-items:center; justify-content:center; line-height:1;">🎤</button>
        </div>
        <div class="lc-muted" id="lcMicStatus_clinical_evolution" style="margin-top:6px; display:none; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></div>
        <textarea class="lc-input" name="clinical_evolution" rows="5" id="lcField_clinical_evolution"><?= htmlspecialchars((string)($record['clinical_evolution'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

        <div class="lc-flex lc-flex--between" style="gap:10px; margin-top:10px; align-items:center;">
            <label class="lc-label" style="margin:0;">Notas</label>
            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="button" data-lc-mic="notes" aria-label="Gravar notas" title="Gravar" style="width:36px; height:36px; padding:0; display:inline-flex; align-items:center; justify-content:center; line-height:1;">🎤</button>
        </div>
        <div class="lc-muted" id="lcMicStatus_notes" style="margin-top:6px; display:none; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></div>
        <textarea class="lc-input" name="notes" rows="4" id="lcField_notes"><?= htmlspecialchars((string)($record['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/medical-records?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Voltar</a>
        </div>
    </form>
</div>

<script>
(function(){
  try {
    var patientId = <?= (int)($patient['id'] ?? 0) ?>;
    var medicalRecordId = <?= (int)($record['id'] ?? 0) ?>;
    var appointmentId = <?= (int)($record['appointment_id'] ?? 0) ?>;
    var professionalId = <?= (int)($record['professional_id'] ?? 0) ?>;
    var csrf = <?= json_encode($csrf, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function byId(id){ return document.getElementById(id); }

    function setStatus(fieldKey, msg){
      var el = byId('lcMicStatus_' + fieldKey);
      if (!el) return;
      el.textContent = msg || '';
      el.style.display = msg ? 'block' : 'none';
    }

    function appendText(fieldKey, text){
      var ta = byId('lcField_' + fieldKey);
      if (!ta) return;
      var cur = String(ta.value || '').trim();
      var add = String(text || '').trim();
      if (!add) return;
      ta.value = (cur ? (cur + "\n\n") : "") + add;
    }

    var active = null;

    async function startRecording(fieldKey, btn){
      if (active) {
        setStatus(active.fieldKey, '');
        try { active.stop(); } catch (e) {}
      }

      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || typeof MediaRecorder === 'undefined') {
        setStatus(fieldKey, 'Sem suporte a microfone.');
        return;
      }

      var stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      var chunks = [];
      var rec = new MediaRecorder(stream);
      rec.ondataavailable = function(e){ if (e.data && e.data.size) chunks.push(e.data); };

      function stopTracks(){
        try { stream.getTracks().forEach(function(t){ t.stop(); }); } catch (e) {}
      }

      async function doStop(){
        try { rec.stop(); } catch (e) {}
        stopTracks();
      }

      function setBtnRecording(isRecording){
        if (!btn) return;
        btn.textContent = isRecording ? '■' : '🎤';
        btn.title = isRecording ? 'Parar' : 'Gravar';
      }

      rec.onstop = async function(){
        try {
          if (!chunks.length) {
            setStatus(fieldKey, '');
            setBtnRecording(false);
            active = null;
            return;
          }

          var blob = new Blob(chunks, { type: chunks[0] ? chunks[0].type : 'audio/webm' });
          var file = new File([blob], 'recording.webm', { type: blob.type });

          setStatus(fieldKey, 'Transcrevendo...');
          setBtnRecording(false);

          var fd = new FormData();
          fd.append('_csrf', csrf);
          fd.append('patient_id', String(patientId || 0));
          fd.append('medical_record_id', String(medicalRecordId || 0));
          fd.append('appointment_id', String(appointmentId || 0));
          fd.append('professional_id', String(professionalId || 0));
          fd.append('audio', file);

          var resp = await fetch('/medical-records/audio/transcribe-json', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
          });

          var json = null;
          try { json = await resp.json(); } catch (e) {}
          if (!resp.ok || !json || json.ok !== true) {
            setStatus(fieldKey, (json && json.error) ? String(json.error) : 'Falha ao transcrever.');
            active = null;
            return;
          }

          var transcript = String(json.transcript || '').trim();
          if (transcript) {
            appendText(fieldKey, transcript);
            setStatus(fieldKey, '');
          } else {
            setStatus(fieldKey, 'Transcrição vazia.');
          }
        } catch (e) {
          setStatus(fieldKey, 'Falha ao transcrever.');
        } finally {
          active = null;
        }
      };

      rec.start();
      setBtnRecording(true);
      setStatus(fieldKey, 'Gravando...');
      active = {
        fieldKey: fieldKey,
        stop: doStop,
        setBtnRecording: setBtnRecording,
      };
    }

    function stopActive(){
      if (!active) return;
      var a = active;
      active = null;
      try { a.stop(); } catch (e) {}
      if (a.setBtnRecording) a.setBtnRecording(false);
    }

    document.querySelectorAll('[data-lc-mic]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var fieldKey = String(btn.getAttribute('data-lc-mic') || '').trim();
        if (!fieldKey) return;
        if (active && active.fieldKey === fieldKey) {
          stopActive();
          return;
        }
        startRecording(fieldKey, btn).catch(function(){ setStatus(fieldKey, 'Erro ao iniciar microfone.'); });
      });
    });
  } catch (e) {}
})();
</script>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
