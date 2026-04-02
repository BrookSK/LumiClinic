<?php
$title = 'Papéis e Permissões';
$csrf = $_SESSION['_csrf'] ?? '';
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

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Papéis e Permissões</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Cada papel define o que um grupo de usuários pode fazer no sistema.</div>
    </div>
    <?php if ($can('rbac.manage')): ?>
        <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" onclick="var f=document.getElementById('newRoleForm');f.style.display=f.style.display==='none'?'block':'none';">+ Novo papel</button>
    <?php endif; ?>
</div>

<!-- Criar novo papel -->
<?php if ($can('rbac.manage')): ?>
<div id="newRoleForm" style="display:none;margin-bottom:16px;">
    <div style="padding:16px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.08);">
        <form method="post" action="/rbac/create" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <div class="lc-field" style="min-width:250px;flex:1;">
                <label class="lc-label">Nome do novo papel</label>
                <input class="lc-input" type="text" name="name" required placeholder="Ex: Enfermeiro, Estagiário..." />
            </div>
            <div style="padding-bottom:1px;display:flex;gap:8px;">
                <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Criar</button>
                <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="document.getElementById('newRoleForm').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Lista -->
<div style="display:flex;flex-direction:column;gap:8px;">
    <?php foreach ($roles as $r): ?>
        <?php
        $rid = (int)$r['id'];
        $isSystem = (int)($r['is_system'] ?? 0) === 1;
        $isEditable = (int)($r['is_editable'] ?? 0) === 1;
        $rName = (string)$r['name'];
        $rCode = (string)$r['code'];
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 2px 8px rgba(17,24,39,.04);flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                <span style="font-weight:750;font-size:14px;color:rgba(31,41,55,.96);"><?= htmlspecialchars($rName, ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($isSystem): ?>
                    <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:rgba(107,114,128,.10);color:#6b7280;border:1px solid rgba(107,114,128,.16);">Sistema</span>
                <?php else: ?>
                    <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:rgba(238,184,16,.12);color:rgba(129,89,1,1);border:1px solid rgba(238,184,16,.22);">Personalizado</span>
                <?php endif; ?>
                <?php if (!$isEditable): ?>
                    <span style="font-size:11px;color:rgba(31,41,55,.35);">Somente leitura</span>
                <?php endif; ?>
            </div>
            <?php if ($can('rbac.manage')): ?>
            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/rbac/edit?id=<?= $rid ?>">Editar permissões</a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
