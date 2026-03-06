<?php
$title = 'Novo registro';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$patient = $patient ?? null;
$professionals = $professionals ?? [];
$templates = $templates ?? [];
$template = $template ?? null;
$fields = $fields ?? [];
$records = $records ?? [];
$prefill = $prefill ?? [];
$transcript = isset($_GET['transcript']) ? trim((string)$_GET['transcript']) : '';
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Novo registro - <?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/medical-records/create">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />

        <?php
            $prefAtt = trim((string)($prefill['attended_at'] ?? ''));
            if ($prefAtt !== '') {
                $prefAtt = str_replace(' ', 'T', $prefAtt);
                if (strlen($prefAtt) === 19) {
                    $prefAtt = substr($prefAtt, 0, 16);
                }
            }
        ?>
        <label class="lc-label">Atendido em</label>
        <input class="lc-input" type="datetime-local" name="attended_at" value="<?= htmlspecialchars($prefAtt, ENT_QUOTES, 'UTF-8') ?>" required />

        <label class="lc-label">Profissional</label>
        <select class="lc-select" name="professional_id">
            <option value="">(opcional)</option>
            <?php $prefProf = (int)($prefill['professional_id'] ?? 0); ?>
            <?php foreach ($professionals as $pr): ?>
                <option value="<?= (int)$pr['id'] ?>" <?= (int)$pr['id'] === $prefProf ? 'selected' : '' ?>><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <label class="lc-label">Procedimento</label>
        <input class="lc-input" type="text" name="procedure_type" value="<?= htmlspecialchars((string)($prefill['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Template (opcional)</label>
        <select class="lc-select" name="template_id" onchange="if(this.value){ window.location.href = '/medical-records/create?patient_id=<?= (int)($patient['id'] ?? 0) ?>&template_id=' + encodeURIComponent(this.value);} else { window.location.href = '/medical-records/create?patient_id=<?= (int)($patient['id'] ?? 0) ?>'; }">
            <option value="">(sem template)</option>
            <?php $curTplId = (int)($template['id'] ?? 0); ?>
            <?php foreach ($templates as $t): ?>
                <option value="<?= (int)$t['id'] ?>" <?= (int)$t['id'] === $curTplId ? 'selected' : '' ?>><?= htmlspecialchars((string)$t['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <?php if (is_array($fields) && $fields !== []): ?>
            <div class="lc-card" style="margin-top:12px;">
                <div class="lc-card__title">Campos do template</div>
                <div class="lc-card__body">
                    <?php foreach ($fields as $f): ?>
                        <?php
                        $key = (string)($f['field_key'] ?? '');
                        $label = (string)($f['label'] ?? $key);
                        $type = (string)($f['field_type'] ?? 'text');
                        $req = (int)($f['required'] ?? 0) === 1;
                        $opts = [];
                        if (isset($f['options_json']) && $f['options_json']) {
                            $decoded = json_decode((string)$f['options_json'], true);
                            if (is_array($decoded)) {
                                $opts = $decoded;
                            }
                        }
                        $name = 'f_' . $key;
                        ?>
                        <label class="lc-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?><?= $req ? ' *' : '' ?></label>
                        <?php if ($type === 'textarea'): ?>
                            <textarea class="lc-input" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" rows="4" <?= $req ? 'required' : '' ?>></textarea>
                        <?php elseif ($type === 'checkbox'): ?>
                            <select class="lc-select" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
                                <option value="0">Não</option>
                                <option value="1">Sim</option>
                            </select>
                        <?php elseif ($type === 'select'): ?>
                            <select class="lc-select" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" <?= $req ? 'required' : '' ?>>
                                <option value="">Selecione</option>
                                <?php foreach ($opts as $o): ?>
                                    <option value="<?= htmlspecialchars((string)$o, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$o, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($type === 'number'): ?>
                            <input class="lc-input" type="number" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" <?= $req ? 'required' : '' ?> />
                        <?php elseif ($type === 'date'): ?>
                            <input class="lc-input" type="date" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" <?= $req ? 'required' : '' ?> />
                        <?php else: ?>
                            <input class="lc-input" type="text" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" <?= $req ? 'required' : '' ?> />
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <label class="lc-label">Descrição clínica</label>
        <textarea class="lc-input" name="clinical_description" rows="5"></textarea>

        <label class="lc-label">Evolução</label>
        <textarea class="lc-input" name="clinical_evolution" rows="5" id="lcClinicalEvolution"></textarea>

        <label class="lc-label">Notas</label>
        <textarea class="lc-input" name="notes" rows="4"></textarea>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/medical-records?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Voltar</a>
        </div>
    </form>
</div>

<div class="lc-card" style="margin-top:16px;">
    <div class="lc-card__header">Áudio (transcrição IA)</div>
    <div class="lc-card__body">
        <form method="post" action="/medical-records/audio/transcribe" enctype="multipart/form-data" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />
            <input type="hidden" name="appointment_id" value="<?= (int)($_GET['appointment_id'] ?? 0) ?>" />
            <input type="hidden" name="professional_id" value="<?= (int)($prefill['professional_id'] ?? 0) ?>" />

            <div class="lc-flex lc-flex--wrap lc-gap-sm" style="align-items:center;">
                <button class="lc-btn lc-btn--secondary" type="button" id="lcRecStart">Gravar</button>
                <button class="lc-btn lc-btn--secondary" type="button" id="lcRecStop" disabled>Parar</button>
                <div class="lc-muted" id="lcRecStatus">Pronto</div>
            </div>

            <div style="margin-top:10px;">
                <audio id="lcRecPreview" controls style="width:100%; display:none;"></audio>
            </div>

            <div class="lc-field" style="margin-top:10px;">
                <label class="lc-label">Arquivo de áudio</label>
                <input class="lc-input" type="file" name="audio" id="lcAudioFile" accept="audio/*" />
                <div class="lc-muted" style="margin-top:6px;">Você pode gravar acima ou enviar um arquivo.</div>
            </div>

            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:10px;">
                <button class="lc-btn lc-btn--primary" type="submit">Transcrever e adicionar na evolução</button>
            </div>
        </form>

        <script>
        (function(){
          try {
            var transcript = <?= json_encode($transcript, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            if (transcript) {
              var evo = document.getElementById('lcClinicalEvolution');
              if (evo) {
                var cur = String(evo.value || '').trim();
                var add = String(transcript || '').trim();
                if (add) {
                  evo.value = (cur ? (cur + "\n\n") : "") + add;
                }
              }
            }

            var startBtn = document.getElementById('lcRecStart');
            var stopBtn = document.getElementById('lcRecStop');
            var statusEl = document.getElementById('lcRecStatus');
            var audioEl = document.getElementById('lcRecPreview');
            var fileInput = document.getElementById('lcAudioFile');

            function setStatus(t){ if (statusEl) statusEl.textContent = t; }

            var mediaRecorder = null;
            var chunks = [];

            async function start(){
              if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                setStatus('Sem suporte a microfone.');
                return;
              }
              var stream = await navigator.mediaDevices.getUserMedia({ audio: true });
              chunks = [];
              mediaRecorder = new MediaRecorder(stream);
              mediaRecorder.ondataavailable = function(e){ if (e.data && e.data.size) chunks.push(e.data); };
              mediaRecorder.onstop = function(){
                try {
                  var blob = new Blob(chunks, { type: chunks[0] ? chunks[0].type : 'audio/webm' });
                  var url = URL.createObjectURL(blob);
                  if (audioEl) { audioEl.src = url; audioEl.style.display = 'block'; }
                  var f = new File([blob], 'recording.webm', { type: blob.type });
                  if (fileInput) {
                    var dt = new DataTransfer();
                    dt.items.add(f);
                    fileInput.files = dt.files;
                  }
                  setStatus('Gravação pronta.');
                } catch (e) {
                  setStatus('Falha ao preparar áudio.');
                }
              };

              mediaRecorder.start();
              if (startBtn) startBtn.disabled = true;
              if (stopBtn) stopBtn.disabled = false;
              setStatus('Gravando...');
            }

            function stop(){
              if (!mediaRecorder) return;
              try {
                mediaRecorder.stop();
                if (mediaRecorder.stream) {
                  mediaRecorder.stream.getTracks().forEach(function(t){ t.stop(); });
                }
              } catch (e) {}
              if (startBtn) startBtn.disabled = false;
              if (stopBtn) stopBtn.disabled = true;
            }

            if (startBtn) startBtn.addEventListener('click', function(){ start().catch(function(){ setStatus('Erro ao iniciar microfone.'); }); });
            if (stopBtn) stopBtn.addEventListener('click', function(){ stop(); });
          } catch (e) {}
        })();
        </script>
    </div>
</div>

<div class="lc-card" style="margin-top:16px;">
    <div class="lc-card__header">Histórico</div>
    <div class="lc-card__body">
        <?php if (!is_array($records) || $records === []): ?>
            <div class="lc-muted">Sem registros anteriores.</div>
        <?php else: ?>
            <?php foreach ($records as $r): ?>
                <div class="lc-card" style="padding:12px; margin-bottom:10px;">
                    <div><strong><?= htmlspecialchars((string)($r['attended_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                    <?php if (((string)($r['procedure_type'] ?? '')) !== ''): ?>
                        <div class="lc-muted"><?= htmlspecialchars((string)$r['procedure_type'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <?php if (((string)($r['clinical_description'] ?? '')) !== ''): ?>
                        <div style="margin-top:8px;"><?= nl2br(htmlspecialchars((string)$r['clinical_description'], ENT_QUOTES, 'UTF-8')) ?></div>
                    <?php endif; ?>
                    <?php if (((string)($r['clinical_evolution'] ?? '')) !== ''): ?>
                        <div style="margin-top:8px;"><div class="lc-label">Evolução</div><?= nl2br(htmlspecialchars((string)$r['clinical_evolution'], ENT_QUOTES, 'UTF-8')) ?></div>
                    <?php endif; ?>
                    <div style="margin-top:10px;">
                        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/medical-records/edit?patient_id=<?= (int)($patient['id'] ?? 0) ?>&id=<?= (int)($r['id'] ?? 0) ?>">Abrir</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
