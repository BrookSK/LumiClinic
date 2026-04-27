<?php
$title = 'Editar Template WhatsApp';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$template = $template ?? null;

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

$tid = (int)($template['id'] ?? 0);
$tName = (string)($template['name'] ?? '');
$tCode = (string)($template['code'] ?? '');
$tBody = (string)($template['body'] ?? '');
$tStatus = (string)($template['status'] ?? 'active');

ob_start();
?>

<a href="/whatsapp-templates" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para templates
</a>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
    <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);"><?= htmlspecialchars($tName !== '' ? $tName : 'Template', ENT_QUOTES, 'UTF-8') ?></div>
    <span style="display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.3px;text-transform:uppercase;background:<?= $tStatus === 'active' ? 'rgba(22,163,74,.12)' : 'rgba(107,114,128,.10)' ?>;color:<?= $tStatus === 'active' ? '#16a34a' : '#6b7280' ?>;border:1px solid <?= $tStatus === 'active' ? 'rgba(22,163,74,.22)' : 'rgba(107,114,128,.18)' ?>;"><?= $tStatus === 'active' ? 'Ativo' : 'Desativado' ?></span>
</div>

<?php if ($can('settings.update')): ?>
<div style="display:grid;grid-template-columns:1fr 280px;gap:18px;align-items:start;">
    <!-- Form -->
    <div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
        <form method="post" class="lc-form" action="/whatsapp-templates/edit">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= $tid ?>" />

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="lc-field">
                    <label class="lc-label">Código</label>
                    <input class="lc-input" type="text" name="code" value="<?= htmlspecialchars($tCode, ENT_QUOTES, 'UTF-8') ?>" required />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Nome</label>
                    <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars($tName, ENT_QUOTES, 'UTF-8') ?>" required />
                </div>
            </div>

            <div class="lc-field">
                <label class="lc-label">Status</label>
                <select class="lc-select" name="status" style="max-width:200px;">
                    <option value="active" <?= $tStatus === 'active' ? 'selected' : '' ?>>Ativo</option>
                    <option value="disabled" <?= $tStatus === 'disabled' ? 'selected' : '' ?>>Desativado</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Mensagem</label>
                <textarea class="lc-input" name="body" rows="6" required><?= htmlspecialchars($tBody, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div style="display:flex;gap:10px;margin-top:14px;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <a class="lc-btn lc-btn--secondary" href="/whatsapp-templates">Cancelar</a>
            </div>
        </form>
    </div>

    <!-- Sidebar -->
    <div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
        <div style="font-weight:750;font-size:13px;color:rgba(31,41,55,.55);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Variáveis disponíveis</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <div style="padding:8px 10px;border-radius:8px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);">
                <code style="font-weight:700;font-size:12px;">{nome_paciente}</code>
                <div style="font-size:11px;color:rgba(31,41,55,.45);margin-top:2px;">Nome do paciente</div>
            </div>
            <div style="padding:8px 10px;border-radius:8px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);">
                <code style="font-weight:700;font-size:12px;">{data}</code>
                <div style="font-size:11px;color:rgba(31,41,55,.45);margin-top:2px;">Data da consulta</div>
            </div>
            <div style="padding:8px 10px;border-radius:8px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);">
                <code style="font-weight:700;font-size:12px;">{horario}</code>
                <div style="font-size:11px;color:rgba(31,41,55,.45);margin-top:2px;">Horário da consulta</div>
            </div>
            <div style="padding:8px 10px;border-radius:8px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);">
                <code style="font-weight:700;font-size:12px;">{nome_clinica}</code>
                <div style="font-size:11px;color:rgba(31,41,55,.45);margin-top:2px;">Nome da clínica</div>
            </div>
            <div style="padding:8px 10px;border-radius:8px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);">
                <code style="font-weight:700;font-size:12px;">{link}</code>
                <div style="font-size:11px;color:rgba(31,41,55,.45);margin-top:2px;">Link rastreável (campanhas)</div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:14px;">
        <div><span style="font-size:12px;color:rgba(31,41,55,.45);">Código</span><div style="font-weight:700;"><?= htmlspecialchars($tCode, ENT_QUOTES, 'UTF-8') ?></div></div>
        <div><span style="font-size:12px;color:rgba(31,41,55,.45);">Nome</span><div style="font-weight:700;"><?= htmlspecialchars($tName, ENT_QUOTES, 'UTF-8') ?></div></div>
    </div>
    <div><span style="font-size:12px;color:rgba(31,41,55,.45);">Mensagem</span><div style="margin-top:4px;white-space:pre-wrap;font-size:13px;"><?= htmlspecialchars($tBody, ENT_QUOTES, 'UTF-8') ?></div></div>
</div>
<div style="margin-top:14px;"><a class="lc-btn lc-btn--secondary" href="/whatsapp-templates">Voltar</a></div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
