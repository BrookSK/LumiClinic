<?php
$title = 'Templates de Anamnese';
$templates = $templates ?? [];

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

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div style="font-weight:800; font-size:18px;">Templates de Anamnese</div>
    <?php if ($can('anamnesis.manage')): ?>
        <a class="lc-btn lc-btn--primary" href="/anamnesis/templates/create">+ Novo template</a>
    <?php endif; ?>
</div>

<?php if (empty($templates)): ?>
    <div class="lc-card">
        <div class="lc-card__body" style="text-align:center; padding:40px 20px;">
            <div style="font-size:32px; margin-bottom:10px;">📋</div>
            <div class="lc-muted" style="margin-bottom:12px;">Nenhum template cadastrado.</div>
            <?php if ($can('anamnesis.manage')): ?>
                <a class="lc-btn lc-btn--primary" href="/anamnesis/templates/create">Criar primeiro template</a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
        <?php foreach ($templates as $t): ?>
            <?php $active = (string)($t['status'] ?? 'active') === 'active'; ?>
            <div class="lc-card" style="margin:0; opacity:<?= $active ? '1' : '.6' ?>;">
                <div style="padding:16px;">
                    <div class="lc-flex lc-flex--between lc-flex--center" style="margin-bottom:8px;">
                        <div style="font-weight:700; font-size:15px;"><?= htmlspecialchars((string)($t['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <span class="lc-badge <?= $active ? 'lc-badge--success' : 'lc-badge--secondary' ?>" style="font-size:11px;">
                            <?= $active ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </div>
                    <div class="lc-muted" style="font-size:12px; margin-bottom:12px;">
                        <?= (int)($t['field_count'] ?? 0) ?> campo<?= (int)($t['field_count'] ?? 0) !== 1 ? 's' : '' ?>
                    </div>
                    <?php if ($can('anamnesis.manage')): ?>
                        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/anamnesis/templates/edit?id=<?= (int)$t['id'] ?>">Editar</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
