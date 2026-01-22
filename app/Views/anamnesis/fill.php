<?php
$title = 'Preencher anamnese';
$csrf = $_SESSION['_csrf'] ?? '';
$patient = $patient ?? null;
$template = $template ?? null;
$fields = $fields ?? [];
$professionals = $professionals ?? [];
ob_start();
?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:10px; flex-wrap:wrap;">
    <div class="lc-badge lc-badge--gold">Preencher anamnese</div>
    <div>
        <a class="lc-btn lc-btn--secondary" href="/anamnesis?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Voltar</a>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title"><?= htmlspecialchars((string)($template['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>

    <form method="post" action="/anamnesis/fill" class="lc-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />
        <input type="hidden" name="template_id" value="<?= (int)($template['id'] ?? 0) ?>" />

        <label class="lc-label">Profissional (opcional)</label>
        <select class="lc-select" name="professional_id">
            <option value="">(opcional)</option>
            <?php foreach ($professionals as $pr): ?>
                <option value="<?= (int)$pr['id'] ?>"><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <?php foreach ($fields as $f): ?>
            <?php
            $key = (string)$f['field_key'];
            $label = (string)$f['label'];
            $type = (string)$f['field_type'];
            $opts = [];
            if (isset($f['options_json']) && $f['options_json']) {
                $decoded = json_decode((string)$f['options_json'], true);
                if (is_array($decoded)) {
                    $opts = $decoded;
                }
            }
            ?>
            <label class="lc-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>

            <?php if ($type === 'textarea'): ?>
                <textarea class="lc-input" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" rows="4"></textarea>
            <?php elseif ($type === 'checkbox'): ?>
                <select class="lc-select" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                    <option value="0">Não</option>
                    <option value="1">Sim</option>
                </select>
            <?php elseif ($type === 'select'): ?>
                <select class="lc-select" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                    <option value="">Selecione</option>
                    <?php foreach ($opts as $o): ?>
                        <option value="<?= htmlspecialchars((string)$o, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$o, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input class="lc-input" type="text" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" />
            <?php endif; ?>
        <?php endforeach; ?>

        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
