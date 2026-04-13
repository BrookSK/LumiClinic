<?php
$title = 'Ficha clínica';
$csrf = $_SESSION['_csrf'] ?? '';
$patientId = (int)($patient['id'] ?? 0);
$allergies  = isset($allergies) && is_array($allergies) ? $allergies : [];
$conditions = isset($conditions) && is_array($conditions) ? $conditions : [];
$alerts     = isset($alerts) && is_array($alerts) ? $alerts : [];
$error   = $error ?? '';
$success = $success ?? '';

$activeAlerts = array_filter($alerts, fn($a) => (int)($a['active'] ?? 1) === 1);

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

$sevColor = ['critical' => '#b91c1c', 'warning' => '#d97706', 'info' => '#2563eb'];
$sevLabel = ['critical' => 'Crítico', 'warning' => 'Atenção', 'info' => 'Info'];

ob_start();
?>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Cabeçalho -->
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px;">Alertas Clínicos</div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= $patientId ?>">Voltar ao paciente</a>
    </div>
</div>

<div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr 1fr;">

    <!-- ALERTAS CLÍNICOS -->
    <div class="lc-card" style="margin:0;">
        <div class="lc-flex lc-flex--between lc-flex--center" style="padding:12px 14px; border-bottom:1px solid rgba(0,0,0,.06);">
            <div style="font-weight:700; font-size:14px;">⚠ Alertas</div>
            <?php if ($can('patients.update')): ?>
                <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="toggleForm('form-alert')">+ Adicionar</button>
            <?php endif; ?>
        </div>

        <!-- Formulário oculto -->
        <?php if ($can('patients.update')): ?>
        <div id="form-alert" style="display:none; padding:12px 14px; border-bottom:1px solid rgba(0,0,0,.06); background:rgba(0,0,0,.02);">
            <form method="post" action="/patients/clinical-sheet/alerts/create" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="patient_id" value="<?= $patientId ?>" />
                <input class="lc-input" type="text" name="title" placeholder="Título do alerta" required style="margin-bottom:6px;" />
                <textarea class="lc-input" name="details" rows="2" placeholder="Detalhes (opcional)" style="margin-bottom:6px;"></textarea>
                <div class="lc-flex lc-gap-sm" style="align-items:center;">
                    <select class="lc-select" name="severity" style="flex:1;">
                        <option value="warning" selected>Atenção</option>
                        <option value="critical">Crítico</option>
                        <option value="info">Info</option>
                    </select>
                    <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Salvar</button>
                    <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="toggleForm('form-alert')">✕</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div style="padding:10px 14px;">
            <?php if (empty($alerts)): ?>
                <div class="lc-muted" style="font-size:13px;">Nenhum alerta.</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:8px;">
                <?php foreach ($alerts as $a): ?>
                    <?php
                    $aid = (int)($a['id'] ?? 0);
                    $sev = (string)($a['severity'] ?? 'warning');
                    $active = (int)($a['active'] ?? 0) === 1;
                    $color = $sevColor[$sev] ?? '#6b7280';
                    ?>
                    <div style="border-left:3px solid <?= $color ?>; padding:6px 10px; background:rgba(0,0,0,.02); border-radius:0 6px 6px 0; opacity:<?= $active ? '1' : '.5' ?>;">
                        <div style="font-weight:600; font-size:13px; color:<?= $color ?>;">
                            <?= htmlspecialchars((string)($a['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            <span style="font-weight:400; font-size:11px; margin-left:4px;"><?= $sevLabel[$sev] ?? $sev ?></span>
                        </div>
                        <?php if (($a['details'] ?? '') !== ''): ?>
                            <div class="lc-muted" style="font-size:12px; margin-top:2px;"><?= htmlspecialchars((string)$a['details'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <?php if ($can('patients.update')): ?>
                        <form method="post" action="/patients/clinical-sheet/alerts/delete" onsubmit="return confirm('Remover?');" style="margin-top:4px;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="patient_id" value="<?= $patientId ?>" />
                            <input type="hidden" name="id" value="<?= $aid ?>" />
                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="font-size:11px; padding:2px 8px;">✕</button>
                        </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ALERGIAS / CONTRAINDICAÇÕES -->
    <div class="lc-card" style="margin:0;">
        <div class="lc-flex lc-flex--between lc-flex--center" style="padding:12px 14px; border-bottom:1px solid rgba(0,0,0,.06);">
            <div style="font-weight:700; font-size:14px;">🚫 Alergias</div>
            <?php if ($can('patients.update')): ?>
                <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="toggleForm('form-allergy')">+ Adicionar</button>
            <?php endif; ?>
        </div>

        <?php if ($can('patients.update')): ?>
        <div id="form-allergy" style="display:none; padding:12px 14px; border-bottom:1px solid rgba(0,0,0,.06); background:rgba(0,0,0,.02);">
            <form method="post" action="/patients/clinical-sheet/allergies/create" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="patient_id" value="<?= $patientId ?>" />
                <div class="lc-flex lc-gap-sm" style="margin-bottom:6px;">
                    <select class="lc-select" name="type" style="flex:1;">
                        <option value="allergy">Alergia</option>
                        <option value="contraindication">Contraindicação</option>
                    </select>
                </div>
                <input class="lc-input" type="text" name="trigger_name" placeholder="Ex: Dipirona" required style="margin-bottom:6px;" />
                <input class="lc-input" type="text" name="reaction" placeholder="Reação (opcional)" style="margin-bottom:6px;" />
                <div class="lc-flex lc-gap-sm" style="align-items:center;">
                    <input class="lc-input" type="text" name="severity" placeholder="Severidade" style="flex:1;" />
                    <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Salvar</button>
                    <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="toggleForm('form-allergy')">✕</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div style="padding:10px 14px;">
            <?php if (empty($allergies)): ?>
                <div class="lc-muted" style="font-size:13px;">Nenhuma alergia.</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:8px;">
                <?php foreach ($allergies as $it): ?>
                    <?php
                    $aid = (int)($it['id'] ?? 0);
                    $t = (string)($it['type'] ?? '');
                    $isContra = $t === 'contraindication';
                    ?>
                    <div style="border-left:3px solid #b91c1c; padding:6px 10px; background:rgba(185,28,28,.04); border-radius:0 6px 6px 0;">
                        <div style="font-weight:600; font-size:13px;">
                            <?= htmlspecialchars((string)($it['trigger_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            <span style="font-weight:400; font-size:11px; color:#6b7280; margin-left:4px;"><?= $isContra ? 'Contraindicação' : 'Alergia' ?></span>
                        </div>
                        <?php if (($it['reaction'] ?? '') !== ''): ?>
                            <div class="lc-muted" style="font-size:12px; margin-top:2px;"><?= htmlspecialchars((string)$it['reaction'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <?php if ($can('patients.update')): ?>
                        <form method="post" action="/patients/clinical-sheet/allergies/delete" onsubmit="return confirm('Remover?');" style="margin-top:4px;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="patient_id" value="<?= $patientId ?>" />
                            <input type="hidden" name="id" value="<?= $aid ?>" />
                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="font-size:11px; padding:2px 8px;">✕</button>
                        </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- CONDIÇÕES -->
    <div class="lc-card" style="margin:0;">
        <div class="lc-flex lc-flex--between lc-flex--center" style="padding:12px 14px; border-bottom:1px solid rgba(0,0,0,.06);">
            <div style="font-weight:700; font-size:14px;">🩺 Condições</div>
            <?php if ($can('patients.update')): ?>
                <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="toggleForm('form-condition')">+ Adicionar</button>
            <?php endif; ?>
        </div>

        <?php if ($can('patients.update')): ?>
        <div id="form-condition" style="display:none; padding:12px 14px; border-bottom:1px solid rgba(0,0,0,.06); background:rgba(0,0,0,.02);">
            <form method="post" action="/patients/clinical-sheet/conditions/create" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="patient_id" value="<?= $patientId ?>" />
                <input class="lc-input" type="text" name="condition_name" placeholder="Ex: Hipertensão" required style="margin-bottom:6px;" />
                <div class="lc-flex lc-gap-sm" style="align-items:center; margin-bottom:6px;">
                    <select class="lc-select" name="status" style="flex:1;">
                        <option value="active" selected>Ativa</option>
                        <option value="inactive">Inativa</option>
                        <option value="resolved">Resolvida</option>
                    </select>
                    <input class="lc-input" type="date" name="onset_date" style="flex:1;" />
                </div>
                <div class="lc-flex lc-gap-sm" style="align-items:center;">
                    <textarea class="lc-input" name="notes" rows="1" placeholder="Notas (opcional)" style="flex:1;"></textarea>
                    <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Salvar</button>
                    <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="toggleForm('form-condition')">✕</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div style="padding:10px 14px;">
            <?php if (empty($conditions)): ?>
                <div class="lc-muted" style="font-size:13px;">Nenhuma condição.</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:8px;">
                <?php foreach ($conditions as $c): ?>
                    <?php
                    $cid = (int)($c['id'] ?? 0);
                    $st = (string)($c['status'] ?? 'active');
                    $stColors = ['active' => '#16a34a', 'inactive' => '#6b7280', 'resolved' => '#2563eb'];
                    $stLabels = ['active' => 'Ativa', 'inactive' => 'Inativa', 'resolved' => 'Resolvida'];
                    $stColor = $stColors[$st] ?? '#6b7280';
                    ?>
                    <div style="border-left:3px solid <?= $stColor ?>; padding:6px 10px; background:rgba(0,0,0,.02); border-radius:0 6px 6px 0;">
                        <div style="font-weight:600; font-size:13px;">
                            <?= htmlspecialchars((string)($c['condition_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            <span style="font-weight:400; font-size:11px; color:<?= $stColor ?>; margin-left:4px;"><?= $stLabels[$st] ?? $st ?></span>
                        </div>
                        <?php if (($c['onset_date'] ?? '') !== ''): ?>
                            <div class="lc-muted" style="font-size:12px; margin-top:2px;">Desde <?= htmlspecialchars((string)$c['onset_date'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <?php if ($can('patients.update')): ?>
                        <form method="post" action="/patients/clinical-sheet/conditions/delete" onsubmit="return confirm('Remover?');" style="margin-top:4px;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="patient_id" value="<?= $patientId ?>" />
                            <input type="hidden" name="id" value="<?= $cid ?>" />
                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="font-size:11px; padding:2px 8px;">✕</button>
                        </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /grid -->

<script>
function toggleForm(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
    if (el.style.display === 'block') {
        var first = el.querySelector('input[type="text"],textarea');
        if (first) first.focus();
    }
}
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
