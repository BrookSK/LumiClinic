<?php
$title = 'Editar template WhatsApp';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$template = $template ?? null;

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
        return true;
    }

    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) {
        return false;
    }

    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) {
            return false;
        }
        return in_array($permissionCode, $permissions['allow'], true);
    }

    return in_array($permissionCode, $permissions, true);
};

ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Template</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="lc-alert lc-alert--success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($can('settings.update')): ?>
        <form method="post" class="lc-form" action="/whatsapp-templates/edit">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= (int)($template['id'] ?? 0) ?>" />

            <label class="lc-label">Código</label>
            <input class="lc-input" type="text" name="code" value="<?= htmlspecialchars((string)($template['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />

            <label class="lc-label">Nome</label>
            <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($template['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />

            <label class="lc-label">Status</label>
            <?php $status = (string)($template['status'] ?? 'active'); ?>
            <select class="lc-select" name="status">
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativo</option>
                <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>Desativado</option>
            </select>

            <label class="lc-label">Mensagem</label>
            <textarea class="lc-input" name="body" rows="8" required><?= htmlspecialchars((string)($template['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

            <div class="lc-muted" style="margin-top:8px;">
                Variáveis disponíveis: {patient_name}, {date}, {time}, {clinic_name}
            </div>

            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <a class="lc-btn lc-btn--secondary" href="/whatsapp-templates">Voltar</a>
            </div>
        </form>
    <?php else: ?>
        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <a class="lc-btn lc-btn--secondary" href="/whatsapp-templates">Voltar</a>
        </div>
    <?php endif; ?>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
