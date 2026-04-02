<?php
$title = 'Novo Usuário';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
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

ob_start();
?>

<a href="/users" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para usuários
</a>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:18px;">Novo usuário</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($can('users.create')): ?>
<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);max-width:600px;">
    <form method="post" class="lc-form" action="/users/create">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <div class="lc-field">
            <label class="lc-label">Nome completo</label>
            <input class="lc-input" type="text" name="name" required placeholder="Ex: Dr. João Silva" />
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:4px;">
            <div class="lc-field">
                <label class="lc-label">E-mail de login</label>
                <input class="lc-input" type="email" name="email" required />
            </div>
            <div class="lc-field">
                <label class="lc-label">Senha</label>
                <input class="lc-input" type="password" name="password" required />
            </div>
        </div>

        <div class="lc-field">
            <label class="lc-label">Papel (define as permissões)</label>
            <select class="lc-select" name="role_id" required>
                <option value="">Selecione o papel...</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars((string)$r['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">O papel define o que o usuário pode fazer no sistema. Gerencie os papéis em <a href="/rbac" style="color:rgba(129,89,1,1);font-weight:600;">Papéis e Permissões</a>.</div>
        </div>

        <div style="display:flex;gap:10px;margin-top:16px;">
            <button class="lc-btn lc-btn--primary" type="submit">Criar usuário</button>
            <a class="lc-btn lc-btn--secondary" href="/users">Cancelar</a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
