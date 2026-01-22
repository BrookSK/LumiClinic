<?php
$title = 'Editar template de anamnese';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$template = $template ?? null;
$fields = $fields ?? [];
ob_start();

$fieldsForJson = [];
foreach ($fields as $f) {
    $opts = null;
    if (isset($f['options_json']) && $f['options_json']) {
        $decoded = json_decode((string)$f['options_json'], true);
        if (is_array($decoded)) {
            $opts = $decoded;
        }
    }

    $fieldsForJson[] = [
        'field_key' => (string)$f['field_key'],
        'label' => (string)$f['label'],
        'field_type' => (string)$f['field_type'],
        'options' => $opts,
        'sort_order' => (int)$f['sort_order'],
    ];
}

$fieldsJson = json_encode($fieldsForJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
?>
<div class="lc-card">
    <div class="lc-card__title">Template</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/anamnesis/templates/edit">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="id" value="<?= (int)($template['id'] ?? 0) ?>" />

        <label class="lc-label">Nome</label>
        <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($template['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />

        <label class="lc-label">Status</label>
        <?php $status = (string)($template['status'] ?? 'active'); ?>
        <select class="lc-select" name="status">
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativo</option>
            <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>Desativado</option>
        </select>

        <label class="lc-label">Campos (JSON)</label>
        <textarea class="lc-input" name="fields_json" rows="12"><?= htmlspecialchars((string)($fieldsJson ?: '[]'), ENT_QUOTES, 'UTF-8') ?></textarea>

        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/anamnesis/templates">Voltar</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
