<?php
$title = 'Documento LGPD';
$csrf = $_SESSION['_csrf'] ?? '';
$doc = $doc ?? null;
$scope = $scope ?? 'patient_portal';
$roles = $roles ?? [];
$error = $error ?? '';
$success = $success ?? '';

$id = (int)($doc['id'] ?? 0);
$scopeLabels = [
    'patient_portal' => 'Portal do Paciente',
    'system_user'    => 'Equipe Interna',
];

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary"><?= $id > 0 ? 'Editar documento' : 'Novo documento' ?></div>
    <a class="lc-btn lc-btn--secondary" href="/settings/lgpd">Voltar</a>
</div>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__body">
        <form method="post" action="/settings/lgpd/save" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= $id ?>" />

            <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr; align-items:end;">
                <div class="lc-field">
                    <label class="lc-label">Tipo de documento</label>
                    <select class="lc-select" name="scope">
                        <?php foreach ($scopeLabels as $k => $lbl): ?>
                            <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $scope === $k ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Status</label>
                    <select class="lc-select" name="status">
                        <option value="active" <?= (string)($doc['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="disabled" <?= (string)($doc['status'] ?? '') === 'disabled' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
            </div>

            <div class="lc-field" style="margin-top:10px;">
                <label class="lc-label">Título</label>
                <input class="lc-input" type="text" name="title" value="<?= htmlspecialchars((string)($doc['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required placeholder="Ex: Política de Privacidade" />
            </div>

            <div class="lc-field" style="margin-top:10px;">
                <label class="lc-label">Conteúdo</label>
                <textarea class="lc-input" name="body" rows="12" required><?= htmlspecialchars((string)($doc['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr; margin-top:10px; align-items:end;">
                <div class="lc-field">
                    <label class="lc-checkbox" style="display:flex; gap:8px; align-items:center;">
                        <input type="checkbox" name="is_required" value="1" <?= (int)($doc['is_required'] ?? 0) === 1 ? 'checked' : '' ?> />
                        <span>Aceite obrigatório</span>
                    </label>
                    <div class="lc-muted" style="font-size:12px; margin-top:4px;">Se marcado, o usuário não consegue usar o sistema sem aceitar.</div>
                </div>

                <div class="lc-field" id="role_field">
                    <label class="lc-label">Papel alvo (equipe interna)</label>
                    <select class="lc-select" name="target_role_code">
                        <option value="">Todos os papéis</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= htmlspecialchars((string)($r['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                <?= (string)($doc['target_role_code'] ?? '') === (string)($r['code'] ?? '') ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="lc-flex lc-gap-sm" style="margin-top:16px;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <a class="lc-btn lc-btn--secondary" href="/settings/lgpd">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    var scopeEl = document.querySelector('select[name="scope"]');
    var roleField = document.getElementById('role_field');
    if (!scopeEl || !roleField) return;
    function toggle() {
        roleField.style.display = scopeEl.value === 'system_user' ? 'block' : 'none';
    }
    scopeEl.addEventListener('change', toggle);
    toggle();
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
