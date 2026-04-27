<?php
$title = 'Templates WhatsApp';
$templates = $templates ?? [];

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) return false;
    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) return false;
        return in_array($permissionCode, $permissions['allow'], true);
    }
    return in_array($permissionCode, $permissions, true);
};

ob_start();
?>

<a href="/settings" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para configurações
</a>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Templates de WhatsApp</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Modelos de mensagem usados para lembretes, confirmações e campanhas.</div>
    </div>
    <?php if ($can('settings.update')): ?>
        <a class="lc-btn lc-btn--primary lc-btn--sm" href="/whatsapp-templates/create">+ Novo template</a>
    <?php endif; ?>
</div>

<div style="padding:14px 16px;border-radius:12px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.12);font-size:13px;color:rgba(31,41,55,.70);line-height:1.5;margin-bottom:16px;">
    Templates definem o texto das mensagens automáticas. Use variáveis como <code>{nome_paciente}</code>, <code>{data}</code>, <code>{horario}</code> e <code>{nome_clinica}</code> para personalizar.
</div>

<?php if ($templates === []): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
        <div style="font-size:32px;margin-bottom:8px;">📝</div>
        <div style="font-size:14px;">Nenhum template criado ainda.</div>
    </div>
<?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px;">
        <?php foreach ($templates as $t): ?>
            <?php
            $tid = (int)$t['id'];
            $st = (string)($t['status'] ?? 'active');
            $stOk = $st === 'active';
            ?>
            <a href="/whatsapp-templates/edit?id=<?= $tid ?>" style="padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:8px;transition:all 160ms ease;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <span style="font-weight:750;font-size:14px;color:rgba(31,41,55,.96);"><?= htmlspecialchars((string)$t['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span style="display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.3px;text-transform:uppercase;background:<?= $stOk ? 'rgba(22,163,74,.12)' : 'rgba(107,114,128,.10)' ?>;color:<?= $stOk ? '#16a34a' : '#6b7280' ?>;border:1px solid <?= $stOk ? 'rgba(22,163,74,.22)' : 'rgba(107,114,128,.18)' ?>;"><?= $stOk ? 'Ativo' : 'Desativado' ?></span>
                </div>
                <div style="font-size:12px;color:rgba(31,41,55,.45);">Código: <code><?= htmlspecialchars((string)$t['code'], ENT_QUOTES, 'UTF-8') ?></code></div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
