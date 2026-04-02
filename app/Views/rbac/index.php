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
</div>

<!-- Criar novo papel -->
<?php if ($can('rbac.manage')): ?>
<div id="newRoleForm" style="display:none;margin-bottom:16px;">
    <div style="padding:18px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.08);">
        <div style="font-weight:750;font-size:14px;margin-bottom:10px;">Novo papel</div>
        <form method="post" action="/rbac/create" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <div class="lc-field" style="min-width:250px;flex:1;">
                <label class="lc-label">Nome do papel</label>
                <input class="lc-input" type="text" name="name" required placeholder="Ex: Recepcionista, Enfermeiro..." />
            </div>
            <div style="padding-bottom:1px;">
                <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Criar papel</button>
            </div>
        </form>
        <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:6px;">Após criar, você poderá configurar as permissões do papel.</div>
    </div>
</div>

<div style="margin-bottom:16px;">
    <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" onclick="var f=document.getElementById('newRoleForm');f.style.display=f.style.display==='none'?'block':'none';">+ Novo papel</button>
</div>
<?php endif; ?>

<!-- Lista de papéis -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px;">
    <?php foreach ($roles as $r): ?>
        <?php
        $rid = (int)$r['id'];
        $isSystem = (int)($r['is_system'] ?? 0) === 1;
        $isEditable = (int)($r['is_editable'] ?? 0) === 1;
        ?>
        <div style="padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);display:flex;flex-direction:column;gap:8px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                <span style="font-weight:750;font-size:14px;color:rgba(31,41,55,.96);"><?= htmlspecialchars((string)$r['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <div style="display:flex;gap:4px;">
                    <?php if ($isSystem): ?>
                        <span style="display:inline-flex;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700;background:rgba(107,114,128,.10);color:#6b7280;border:1px solid rgba(107,114,128,.18);">Sistema</span>
                    <?php else: ?>
                        <span style="display:inline-flex;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700;background:rgba(238,184,16,.12);color:rgba(129,89,1,1);border:1px solid rgba(238,184,16,.22);">Personalizado</span>
                    <?php endif; ?>
                </div>
            </div>
            <div style="font-size:12px;color:rgba(31,41,55,.45);">
                Código: <code><?= htmlspecialchars((string)$r['code'], ENT_QUOTES, 'UTF-8') ?></code>
                · <?= $isEditable ? 'Editável' : 'Somente leitura' ?>
            </div>
            <?php if ($can('rbac.manage')): ?>
            <div style="display:flex;gap:8px;margin-top:4px;">
                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/rbac/edit?id=<?= $rid ?>">Editar permissões</a>
                <form method="post" action="/rbac/clone" style="display:flex;gap:6px;align-items:center;margin:0;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="from_role_id" value="<?= $rid ?>" />
                    <input class="lc-input" style="width:140px;padding:7px 10px;font-size:12px;" type="text" name="name" placeholder="Clonar como..." />
                    <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Clonar</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
