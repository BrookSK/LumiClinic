<?php
$title = 'Documento LGPD';
$csrf = $_SESSION['_csrf'] ?? '';
$doc = $doc ?? null;
$scope = $scope ?? 'patient_portal';
$roles = $roles ?? [];
$error = $error ?? '';
$success = $success ?? '';

$id = (int)($doc['id'] ?? 0);
$isNew = $id <= 0;
$scopeLabels = ['patient_portal'=>'Portal do Paciente','system_user'=>'Equipe Interna'];

ob_start();
?>

<a href="/settings/lgpd" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para documentos
</a>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:6px;"><?= $isNew ? 'Novo documento' : 'Editar documento' ?></div>
<div style="font-size:13px;color:rgba(31,41,55,.50);margin-bottom:18px;max-width:600px;line-height:1.5;">
    <?php if ($isNew): ?>
        Crie um termo ou política que será apresentado para aceite. Escolha se é para pacientes (portal) ou para a equipe interna (login do sistema).
    <?php else: ?>
        Edite o conteúdo do documento. Se ele já foi assinado por alguém, uma nova versão será criada automaticamente.
    <?php endif; ?>
</div>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);max-width:700px;">
    <form method="post" action="/settings/lgpd/save" class="lc-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="id" value="<?= $id ?>" />

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="lc-field">
                <label class="lc-label">Quem precisa aceitar?</label>
                <select class="lc-select" name="scope" id="lgpdScope">
                    <?php foreach ($scopeLabels as $k => $lbl): ?>
                        <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $scope === $k ? 'selected' : '' ?>><?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Portal = pacientes. Equipe = funcionários da clínica.</div>
            </div>
            <div class="lc-field">
                <label class="lc-label">Status</label>
                <select class="lc-select" name="status">
                    <option value="active" <?= (string)($doc['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Ativo</option>
                    <option value="disabled" <?= (string)($doc['status'] ?? '') === 'disabled' ? 'selected' : '' ?>>Inativo</option>
                </select>
            </div>
        </div>

        <div class="lc-field">
            <label class="lc-label">Título do documento</label>
            <input class="lc-input" type="text" name="title" value="<?= htmlspecialchars((string)($doc['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required placeholder="Ex: Política de Privacidade, Termo de Consentimento..." />
        </div>

        <div class="lc-field">
            <label class="lc-label">Conteúdo</label>
            <textarea class="lc-input" name="body" rows="10" required placeholder="Escreva o texto completo do documento aqui..."><?= htmlspecialchars((string)($doc['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:4px;">
            <div class="lc-field">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 12px;border-radius:10px;border:1px solid rgba(17,24,39,.08);background:rgba(0,0,0,.01);">
                    <input type="checkbox" name="is_required" value="1" <?= (int)($doc['is_required'] ?? 0) === 1 ? 'checked' : '' ?> style="width:18px;height:18px;" />
                    <div>
                        <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);">Aceite obrigatório</div>
                        <div style="font-size:11px;color:rgba(31,41,55,.45);">Bloqueia o acesso até aceitar.</div>
                    </div>
                </label>
            </div>
            <div class="lc-field" id="lgpdRoleField" style="display:<?= $scope === 'system_user' ? 'block' : 'none' ?>;">
                <label class="lc-label">Papel alvo (equipe)</label>
                <select class="lc-select" name="target_role_code">
                    <option value="">Todos os papéis</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= htmlspecialchars((string)($r['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= (string)($doc['target_role_code'] ?? '') === (string)($r['code'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Deixe "Todos" para exigir de toda a equipe.</div>
            </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:16px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/settings/lgpd">Cancelar</a>
        </div>
    </form>
</div>

<script>
(function(){
    var s=document.getElementById('lgpdScope'),f=document.getElementById('lgpdRoleField');
    if(!s||!f)return;
    s.addEventListener('change',function(){f.style.display=s.value==='system_user'?'block':'none';});
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
