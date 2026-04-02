<?php
$title = 'Usuários';
$users = $users ?? [];
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 50;
$hasNext = isset($has_next) ? (bool)$has_next : false;

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
$statusColor = ['active'=>'#16a34a','disabled'=>'#6b7280','inactive'=>'#b91c1c'];

ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Usuários</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Gerencie os usuários que têm acesso ao sistema da clínica.</div>
    </div>
    <?php if ($can('users.create')): ?>
        <a class="lc-btn lc-btn--primary lc-btn--sm" href="/users/create">+ Novo usuário</a>
    <?php endif; ?>
</div>

<?php if ($users === []): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
        <div style="font-size:32px;margin-bottom:8px;">👤</div>
        <div style="font-size:14px;">Nenhum usuário cadastrado.</div>
    </div>
<?php else: ?>
<div style="border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);overflow:hidden;">
    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead><tr><th>Nome</th><th>E-mail</th><th>Papel</th><th>Status</th><th>Criado em</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <?php
                $st = (string)($u['status'] ?? 'active');
                $stLbl = $statusLabel[$st] ?? $st;
                $stClr = $statusColor[$st] ?? '#6b7280';
                $roleName = trim((string)($u['role_name'] ?? ''));
                $created = (string)($u['created_at'] ?? '');
                $createdFmt = $created !== '' ? date('d/m/Y', strtotime($created)) : '—';
                ?>
                <tr>
                    <td style="font-weight:700;"><?= htmlspecialchars((string)$u['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size:13px;"><?= htmlspecialchars((string)$u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($roleName !== ''): ?>
                            <span style="display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;background:rgba(238,184,16,.12);color:rgba(129,89,1,1);border:1px solid rgba(238,184,16,.22);"><?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                            <span style="font-size:12px;color:rgba(31,41,55,.40);">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.3px;text-transform:uppercase;background:<?= $stClr ?>18;color:<?= $stClr ?>;border:1px solid <?= $stClr ?>30"><?= htmlspecialchars($stLbl, ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td style="font-size:12px;color:rgba(31,41,55,.50);"><?= htmlspecialchars($createdFmt, ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align:right;">
                        <?php if ($can('users.update')): ?>
                            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/users/edit?id=<?= (int)$u['id'] ?>">Editar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;flex-wrap:wrap;gap:10px;">
    <span style="font-size:12px;color:rgba(31,41,55,.45);">Página <?= (int)$page ?></span>
    <div style="display:flex;gap:8px;">
        <?php if ($page > 1): ?><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/users?per_page=<?= (int)$perPage ?>&page=<?= (int)($page - 1) ?>">← Anterior</a><?php endif; ?>
        <?php if ($hasNext): ?><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/users?per_page=<?= (int)$perPage ?>&page=<?= (int)($page + 1) ?>">Próxima →</a><?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
