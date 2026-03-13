<?php
$title = 'Configurações';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$settings = $settings ?? null;
$anamnesisTemplates = $anamnesis_templates ?? [];

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

$ro = $can('settings.update') ? '' : 'disabled';
ob_start();
?>
<div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr;">
    <div class="lc-card">
        <div class="lc-card__title">Geral</div>

        <?php if ($error): ?>
            <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" class="lc-form" action="/settings">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <label class="lc-label">Timezone</label>
            <input class="lc-input" type="text" name="timezone" value="<?= htmlspecialchars((string)($settings['timezone'] ?? 'America/Sao_Paulo'), ENT_QUOTES, 'UTF-8') ?>" required <?= $ro ?> />

            <label class="lc-label">Idioma</label>
            <input class="lc-input" type="text" name="language" value="<?= htmlspecialchars((string)($settings['language'] ?? 'pt-BR'), ENT_QUOTES, 'UTF-8') ?>" required <?= $ro ?> />

            <?php
                $weekStart = isset($settings['week_start_weekday']) ? (int)$settings['week_start_weekday'] : 1;
                $weekEnd = isset($settings['week_end_weekday']) ? (int)$settings['week_end_weekday'] : 0;
                $weekdayNames = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
            ?>
            <div class="lc-grid lc-grid--2 lc-gap-grid">
                <div>
                    <label class="lc-label">Início da semana</label>
                    <select class="lc-select" name="week_start_weekday" <?= $ro ?>>
                        <?php for ($i=0; $i<7; $i++): ?>
                            <option value="<?= (int)$i ?>" <?= $weekStart === $i ? 'selected' : '' ?>><?= htmlspecialchars($weekdayNames[$i], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div>
                    <label class="lc-label">Anamnese (template padrão)</label>
                    <?php $anamTpl = isset($settings['anamnesis_default_template_id']) ? (int)$settings['anamnesis_default_template_id'] : 0; ?>
                    <select class="lc-select" name="anamnesis_default_template_id" <?= $ro ?>>
                        <option value="0">(não configurado)</option>
                        <?php if (is_array($anamnesisTemplates)): ?>
                            <?php foreach ($anamnesisTemplates as $t): ?>
                                <option value="<?= (int)($t['id'] ?? 0) ?>" <?= ((int)($t['id'] ?? 0) === $anamTpl) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)($t['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="lc-muted" style="margin-top:6px;">Usado para enviar anamnese automaticamente quando a consulta é confirmada.</div>
                </div>
                <div>
                    <label class="lc-label">Fim da semana</label>
                    <select class="lc-select" name="week_end_weekday" <?= $ro ?>>
                        <?php for ($i=0; $i<7; $i++): ?>
                            <option value="<?= (int)$i ?>" <?= $weekEnd === $i ? 'selected' : '' ?>><?= htmlspecialchars($weekdayNames[$i], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="lc-flex lc-gap-sm" style="margin-top:14px; align-items:center;">
                <?php if ($can('settings.update')): ?>
                    <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <?php endif; ?>
                <a class="lc-btn lc-btn--secondary" href="/settings/terminology">Editar terminologia</a>
                <a class="lc-btn lc-btn--secondary" href="/settings/ai">Configurar IA</a>
                <a class="lc-btn lc-btn--secondary" href="/settings/whatsapp">Configurar WhatsApp</a>
                <a class="lc-btn lc-btn--secondary" href="/settings/google-calendar">Google Calendar</a>
                <?php if ($can('settings.update')): ?>
                    <a class="lc-btn lc-btn--secondary" href="/whatsapp-templates">Templates WhatsApp</a>
                    <a class="lc-btn lc-btn--secondary" href="/whatsapp-logs">Logs WhatsApp</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
