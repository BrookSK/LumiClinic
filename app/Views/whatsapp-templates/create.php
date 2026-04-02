<?php
$title = 'Novo Template WhatsApp';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
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

ob_start();
?>

<a href="/whatsapp-templates" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para templates
</a>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:18px;">Novo template</div>

<?php if ($can('settings.update')): ?>
<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
    <form method="post" class="lc-form" action="/whatsapp-templates/create">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="lc-field">
                <label class="lc-label">Código identificador</label>
                <input class="lc-input" type="text" name="code" value="<?= htmlspecialchars((string)($template['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="ex: reminder_24h" required />
                <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Usado internamente. Sem espaços, use underline.</div>
            </div>
            <div class="lc-field">
                <label class="lc-label">Nome do template</label>
                <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($template['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="ex: Lembrete 24h antes" required />
            </div>
        </div>

        <div class="lc-field">
            <label class="lc-label">Mensagem</label>
            <textarea class="lc-input" name="body" rows="6" required placeholder="Olá {patient_name}, lembramos que sua consulta está marcada para {date} às {time}. Clínica {clinic_name}."><?= htmlspecialchars((string)($template['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">
                Variáveis: <code>{patient_name}</code> <code>{date}</code> <code>{time}</code> <code>{clinic_name}</code> <code>{click_url}</code>
            </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Criar template</button>
            <a class="lc-btn lc-btn--secondary" href="/whatsapp-templates">Cancelar</a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
