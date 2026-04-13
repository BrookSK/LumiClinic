<?php
$title = 'Prontuário';
$patient    = $patient ?? null;
$records    = $records ?? [];
$alerts     = $alerts ?? [];
$allergies  = $allergies ?? [];
$conditions = $conditions ?? [];
$images     = $images ?? [];
$imagePairs = $image_pairs ?? [];
$professionals = $professionals ?? [];

$patientId = (int)($patient['id'] ?? 0);

// Alertas ativos
$activeAlerts = array_filter($alerts, fn($a) => (int)($a['active'] ?? 1) === 1);

ob_start();
?>

<!-- Cabeçalho compacto -->
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:4px;">
            <?php if (!empty($activeAlerts)): ?>
                <?php foreach ($activeAlerts as $al): ?>
                    <span class="lc-badge lc-badge--danger" title="<?= htmlspecialchars((string)($al['details'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        ⚠ <?= htmlspecialchars((string)($al['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php foreach ($allergies as $al): ?>
                <span class="lc-badge lc-badge--danger" style="font-size:11px;">
                    🚫 <?= htmlspecialchars((string)($al['trigger_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php endforeach; ?>
            <?php foreach ($conditions as $c): ?>
                <span class="lc-badge lc-badge--secondary" style="font-size:11px;">
                    <?= htmlspecialchars((string)($c['condition_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php endforeach; ?>
            <?php if (empty($activeAlerts) && empty($allergies) && empty($conditions)): ?>
                <span class="lc-muted" style="font-size:12px;">Sem alertas clínicos</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= $patientId ?>">Voltar ao paciente</a>
        <a class="lc-btn lc-btn--primary" href="/medical-records/create?patient_id=<?= $patientId ?>">+ Novo registro</a>
    </div>
</div>

<!-- Histórico de registros -->
<?php if (empty($records)): ?>
    <div class="lc-card">
        <div class="lc-card__body" style="text-align:center; padding:40px 20px;">
            <div style="font-size:32px; margin-bottom:10px;">📋</div>
            <div style="font-weight:700; margin-bottom:6px;">Nenhum registro ainda</div>
            <div class="lc-muted" style="margin-bottom:16px;">Este é o primeiro atendimento deste paciente.</div>
            <a class="lc-btn lc-btn--primary" href="/medical-records/create?patient_id=<?= $patientId ?>">Criar primeiro registro</a>
        </div>
    </div>
<?php else: ?>
    <div style="display:flex; flex-direction:column; gap:10px;">
        <?php foreach ($records as $r): ?>
            <?php
            $attendedAt = (string)($r['attended_at'] ?? '');
            $dateDisplay = '';
            if ($attendedAt !== '') {
                try {
                    $dt = new \DateTimeImmutable($attendedAt);
                    $dateDisplay = $dt->format('d/m/Y H:i');
                } catch (\Throwable $e) {
                    $dateDisplay = $attendedAt;
                }
            }
            $procedure = trim((string)($r['procedure_type'] ?? ''));
            $desc = trim((string)($r['clinical_description'] ?? ''));
            $evol = trim((string)($r['clinical_evolution'] ?? ''));
            $notes = trim((string)($r['notes'] ?? ''));
            $profName = trim((string)($r['professional_name'] ?? ''));
            // Preview: primeiras 120 chars da descrição ou evolução
            $preview = $desc !== '' ? $desc : $evol;
            $preview = mb_strlen($preview, 'UTF-8') > 120
                ? mb_substr($preview, 0, 120, 'UTF-8') . '…'
                : $preview;
            ?>
            <div class="lc-card" style="margin:0;">
                <div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="padding:14px 16px; gap:10px; cursor:pointer;" onclick="toggleRecord(<?= (int)$r['id'] ?>)">
                    <div>
                        <div style="font-weight:700;"><?= htmlspecialchars($dateDisplay, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="lc-muted" style="font-size:13px; margin-top:2px;">
                            <?php if ($procedure !== ''): ?>
                                <span><?= htmlspecialchars($procedure, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if ($profName !== ''): ?>
                                <span style="margin:0 4px;">·</span>
                                <span><?= htmlspecialchars($profName, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($preview !== ''): ?>
                            <div class="lc-muted" style="font-size:12px; margin-top:4px;" id="preview-<?= (int)$r['id'] ?>">
                                <?= htmlspecialchars($preview, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="lc-flex lc-gap-sm" style="align-items:center; flex-shrink:0;">
                        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/medical-records/edit?patient_id=<?= $patientId ?>&id=<?= (int)$r['id'] ?>" onclick="event.stopPropagation()">Editar</a>
                        <span id="chevron-<?= (int)$r['id'] ?>" style="color:var(--lc-muted); font-size:18px; line-height:1; transition:transform .2s;">›</span>
                    </div>
                </div>

                <div id="record-<?= (int)$r['id'] ?>" style="display:none; padding:0 16px 16px; border-top:1px solid rgba(0,0,0,.06);">
                    <?php if ($desc !== ''): ?>
                        <div style="margin-top:12px;">
                            <div class="lc-label">Descrição clínica</div>
                            <div style="margin-top:4px; line-height:1.7;"><?= nl2br(htmlspecialchars($desc, ENT_QUOTES, 'UTF-8')) ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($evol !== ''): ?>
                        <div style="margin-top:12px;">
                            <div class="lc-label">Evolução</div>
                            <div style="margin-top:4px; line-height:1.7;"><?= nl2br(htmlspecialchars($evol, ENT_QUOTES, 'UTF-8')) ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($notes !== ''): ?>
                        <div style="margin-top:12px;">
                            <div class="lc-label">Notas</div>
                            <div style="margin-top:4px; line-height:1.7; color:var(--lc-muted);"><?= nl2br(htmlspecialchars($notes, ENT_QUOTES, 'UTF-8')) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function toggleRecord(id) {
    var body = document.getElementById('record-' + id);
    var chev = document.getElementById('chevron-' + id);
    var prev = document.getElementById('preview-' + id);
    if (!body) return;
    var open = body.style.display !== 'none';
    body.style.display = open ? 'none' : 'block';
    if (chev) chev.style.transform = open ? '' : 'rotate(90deg)';
    if (prev) prev.style.display = open ? '' : 'none';
}
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
