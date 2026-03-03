<?php
$title = 'Editar registro';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$patient = $patient ?? null;
$record = $record ?? null;
$professionals = $professionals ?? [];
$templates = $templates ?? [];
$template = $template ?? null;
$fields = $fields ?? [];

$values = [];
$rawValues = is_array($record) ? (string)($record['fields_json'] ?? '') : '';
if ($rawValues !== '') {
    $decoded = json_decode($rawValues, true);
    if (is_array($decoded)) {
        $values = $decoded;
    }
}
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Editar registro - <?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/medical-records/edit">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />
        <input type="hidden" name="id" value="<?= (int)($record['id'] ?? 0) ?>" />

        <label class="lc-label">Atendido em</label>
        <?php
        $att = (string)($record['attended_at'] ?? '');
        $attValue = $att !== '' ? str_replace(' ', 'T', substr($att, 0, 16)) : '';
        ?>
        <input class="lc-input" type="datetime-local" name="attended_at" value="<?= htmlspecialchars($attValue, ENT_QUOTES, 'UTF-8') ?>" required />

        <label class="lc-label">Profissional</label>
        <?php $currentProf = (int)($record['professional_id'] ?? 0); ?>
        <select class="lc-select" name="professional_id">
            <option value="" <?= $currentProf === 0 ? 'selected' : '' ?>>(opcional)</option>
            <?php foreach ($professionals as $pr): ?>
                <option value="<?= (int)$pr['id'] ?>" <?= (int)$pr['id'] === $currentProf ? 'selected' : '' ?>><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <label class="lc-label">Procedimento</label>
        <input class="lc-input" type="text" name="procedure_type" value="<?= htmlspecialchars((string)($record['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Template (opcional)</label>
        <?php $curTplId = (int)($template['id'] ?? ($record['template_id'] ?? 0)); ?>
        <select class="lc-select" name="template_id" onchange="if(this.value){ window.location.href = '/medical-records/edit?patient_id=<?= (int)($patient['id'] ?? 0) ?>&id=<?= (int)($record['id'] ?? 0) ?>&template_id=' + encodeURIComponent(this.value);} else { window.location.href = '/medical-records/edit?patient_id=<?= (int)($patient['id'] ?? 0) ?>&id=<?= (int)($record['id'] ?? 0) ?>'; }">
            <option value="">(sem template)</option>
            <?php foreach ($templates as $t): ?>
                <option value="<?= (int)$t['id'] ?>" <?= (int)$t['id'] === $curTplId ? 'selected' : '' ?>><?= htmlspecialchars((string)$t['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <?php if (is_array($fields) && $fields !== []): ?>
            <div class="lc-card" style="margin-top:12px;">
                <div class="lc-card__title">Campos do template</div>
                <div class="lc-card__body">
                    <?php foreach ($fields as $f): ?>
                        <?php
                        $key = (string)($f['field_key'] ?? '');
                        $label = (string)($f['label'] ?? $key);
                        $type = (string)($f['field_type'] ?? 'text');
                        $req = (int)($f['required'] ?? 0) === 1;
                        $opts = [];
                        if (isset($f['options_json']) && $f['options_json']) {
                            $decoded = json_decode((string)$f['options_json'], true);
                            if (is_array($decoded)) {
                                $opts = $decoded;
                            }
                        }
                        $name = 'f_' . $key;
                        $val = array_key_exists($key, $values) ? $values[$key] : '';
                        ?>
                        <label class="lc-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?><?= $req ? ' *' : '' ?></label>
                        <?php if ($type === 'textarea'): ?>
                            <textarea class="lc-input" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" rows="4" <?= $req ? 'required' : '' ?>><?= htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') ?></textarea>
                        <?php elseif ($type === 'checkbox'): ?>
                            <?php $chk = (string)$val === '1' || $val === 1 ? '1' : '0'; ?>
                            <select class="lc-select" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
                                <option value="0" <?= $chk === '0' ? 'selected' : '' ?>>Não</option>
                                <option value="1" <?= $chk === '1' ? 'selected' : '' ?>>Sim</option>
                            </select>
                        <?php elseif ($type === 'select'): ?>
                            <select class="lc-select" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" <?= $req ? 'required' : '' ?>>
                                <option value="">Selecione</option>
                                <?php foreach ($opts as $o): ?>
                                    <?php $ov = (string)$o; ?>
                                    <option value="<?= htmlspecialchars($ov, ENT_QUOTES, 'UTF-8') ?>" <?= (string)$val === $ov ? 'selected' : '' ?>><?= htmlspecialchars($ov, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($type === 'number'): ?>
                            <input class="lc-input" type="number" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') ?>" <?= $req ? 'required' : '' ?> />
                        <?php elseif ($type === 'date'): ?>
                            <input class="lc-input" type="date" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') ?>" <?= $req ? 'required' : '' ?> />
                        <?php else: ?>
                            <input class="lc-input" type="text" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') ?>" <?= $req ? 'required' : '' ?> />
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <label class="lc-label">Descrição clínica</label>
        <textarea class="lc-input" name="clinical_description" rows="5"><?= htmlspecialchars((string)($record['clinical_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

        <label class="lc-label">Evolução</label>
        <textarea class="lc-input" name="clinical_evolution" rows="5"><?= htmlspecialchars((string)($record['clinical_evolution'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

        <label class="lc-label">Notas</label>
        <textarea class="lc-input" name="notes" rows="4"><?= htmlspecialchars((string)($record['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/medical-records?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Voltar</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
