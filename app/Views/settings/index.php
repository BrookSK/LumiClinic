<?php
$title = 'Configurações';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$settings = $settings ?? null;
ob_start();
?>
<div class="lc-grid">
    <div class="lc-card">
        <div class="lc-card__title">Geral</div>

        <?php if ($error): ?>
            <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" class="lc-form" action="/settings">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <label class="lc-label">Timezone</label>
            <input class="lc-input" type="text" name="timezone" value="<?= htmlspecialchars((string)($settings['timezone'] ?? 'America/Sao_Paulo'), ENT_QUOTES, 'UTF-8') ?>" required />

            <label class="lc-label">Idioma</label>
            <input class="lc-input" type="text" name="language" value="<?= htmlspecialchars((string)($settings['language'] ?? 'pt-BR'), ENT_QUOTES, 'UTF-8') ?>" required />

            <?php
                $weekStart = isset($settings['week_start_weekday']) ? (int)$settings['week_start_weekday'] : 1;
                $weekEnd = isset($settings['week_end_weekday']) ? (int)$settings['week_end_weekday'] : 0;
                $weekdayNames = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
            ?>
            <div class="lc-grid lc-grid--2 lc-gap-grid">
                <div>
                    <label class="lc-label">Início da semana</label>
                    <select class="lc-select" name="week_start_weekday">
                        <?php for ($i=0; $i<7; $i++): ?>
                            <option value="<?= (int)$i ?>" <?= $weekStart === $i ? 'selected' : '' ?>><?= htmlspecialchars($weekdayNames[$i], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label class="lc-label">Fim da semana</label>
                    <select class="lc-select" name="week_end_weekday">
                        <?php for ($i=0; $i<7; $i++): ?>
                            <option value="<?= (int)$i ?>" <?= $weekEnd === $i ? 'selected' : '' ?>><?= htmlspecialchars($weekdayNames[$i], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="lc-flex lc-gap-sm" style="margin-top:14px; align-items:center;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <a class="lc-btn lc-btn--secondary" href="/settings/terminology">Editar terminologia</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
