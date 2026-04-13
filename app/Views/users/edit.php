<?php
$title = 'Editar Usuário';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$user = $user ?? null;
$roles = $roles ?? [];

$can = function (string $pc): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $p = $_SESSION['permissions'] ?? [];
    if (!is_array($p)) return false;
    if (isset($p['allow'],$p['deny'])&&is_array($p['allow'])&&is_array($p['deny'])) {
        if (in_array($pc,$p['deny'],true)) return false;
        return in_array($pc,$p['allow'],true);
    }
    return in_array($pc,$p,true);
};

$statusLabel = ['active'=>'Ativo','disabled'=>'Desativado','inactive'=>'Inativo'];

ob_start();
?>

<a href="/users" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para usuários
</a>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:18px;">Editar usuário</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($can('users.update')): ?>
<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);max-width:600px;">
    <form method="post" class="lc-form" action="/users/edit">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="id" value="<?= (int)($user['id'] ?? 0) ?>" />

        <div class="lc-field">
            <label class="lc-label">Nome completo</label>
            <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />
        </div>

        <div class="lc-field">
            <label class="lc-label">E-mail</label>
            <input class="lc-input" type="email" name="email" value="<?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:4px;">
            <div class="lc-field">
                <label class="lc-label">Papel</label>
                <?php $currentRoleId = (int)($user['role_id'] ?? 0); ?>
                <select class="lc-select" name="role_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === $currentRoleId ? 'selected' : '' ?>><?= htmlspecialchars((string)$r['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lc-field">
                <label class="lc-label">Status</label>
                <?php $currentStatus = (string)($user['status'] ?? 'active'); ?>
                <select class="lc-select" name="status">
                    <?php foreach ($statusLabel as $k=>$v): ?>
                        <option value="<?= $k ?>" <?= $currentStatus === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="lc-field">
            <label class="lc-label">Nova senha (deixe vazio para manter a atual)</label>
            <input class="lc-input" type="password" name="new_password" placeholder="••••••••" />
        </div>

        <?php
        $prof = $professional ?? null;
        $isProfessional = is_array($prof);
        $roleCode = '';
        foreach ($roles as $r) {
            if ((int)$r['id'] === $currentRoleId) { $roleCode = (string)($r['code'] ?? ''); break; }
        }
        ?>
        <?php if ($isProfessional || $roleCode === 'professional'): ?>
        <div style="margin-top:12px;padding:14px;border-radius:10px;background:rgba(99,102,241,.03);border:1px solid rgba(99,102,241,.12);">
            <div style="font-weight:700;font-size:13px;color:rgba(99,102,241,.8);margin-bottom:10px;">👨‍⚕️ Dados profissionais</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="lc-field">
                    <label class="lc-label">Especialidade</label>
                    <input class="lc-input" type="text" name="specialty" value="<?= htmlspecialchars((string)($prof['specialty'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: Dermatologia, Estética" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Nº do conselho (CRM, CRO, etc.)</label>
                    <input class="lc-input" type="text" name="council_number" value="<?= htmlspecialchars((string)($prof['council_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: CRM 123456/SP" />
                    <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Aparece no receituário e na impressão de receitas.</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:10px;margin-top:16px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/users">Cancelar</a>
        </div>
    </form>

    <?php if ($can('users.delete')): ?>
    <details style="margin-top:16px;">
        <summary style="font-size:12px;color:rgba(185,28,28,.60);cursor:pointer;list-style:none;">Desativar usuário</summary>
        <div style="margin-top:8px;padding:12px;border-radius:12px;border:1px solid rgba(185,28,28,.18);background:rgba(185,28,28,.04);">
            <div style="font-size:12px;color:rgba(185,28,28,.70);margin-bottom:8px;">O usuário perderá acesso ao sistema.</div>
            <form method="post" action="/users/disable" style="margin:0;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= (int)($user['id'] ?? 0) ?>" />
                <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit" onclick="return confirm('Desativar este usuário?');">Confirmar desativação</button>
            </form>
        </div>
    </details>
    <?php endif; ?>
</div>

<?php else: ?>
<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);max-width:600px;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div><span style="font-size:12px;color:rgba(31,41,55,.45);">Nome</span><div style="font-weight:700;margin-top:2px;"><?= htmlspecialchars((string)($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div>
        <div><span style="font-size:12px;color:rgba(31,41,55,.45);">E-mail</span><div style="font-weight:700;margin-top:2px;"><?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div>
        <div><span style="font-size:12px;color:rgba(31,41,55,.45);">Status</span><div style="font-weight:700;margin-top:2px;"><?= htmlspecialchars($statusLabel[(string)($user['status'] ?? '')] ?? (string)($user['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div>
        <div><span style="font-size:12px;color:rgba(31,41,55,.45);">Papel</span><div style="font-weight:700;margin-top:2px;"><?= htmlspecialchars((string)($user['role_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div></div>
    </div>
</div>
<div style="margin-top:14px;"><a class="lc-btn lc-btn--secondary" href="/users">Voltar</a></div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
