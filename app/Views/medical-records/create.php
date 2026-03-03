<?php
$title = 'Novo registro';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$patient = $patient ?? null;
$professionals = $professionals ?? [];
$templates = $templates ?? [];
$template = $template ?? null;
$fields = $fields ?? [];
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Novo registro - <?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/medical-records/create">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />

        <label class="lc-label">Atendido em</label>
        <input class="lc-input" type="datetime-local" name="attended_at" required />

        <label class="lc-label">Profissional</label>
        <select class="lc-select" name="professional_id">
            <option value="">(opcional)</option>
            <?php foreach ($professionals as $pr): ?>
                <option value="<?= (int)$pr['id'] ?>"><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <label class="lc-label">Procedimento</label>
        <input class="lc-input" type="text" name="procedure_type" />

        <label class="lc-label">Template (opcional)</label>
        <select class="lc-select" name="template_id" onchange="if(this.value){ window.location.href = '/medical-records/create?patient_id=<?= (int)($patient['id'] ?? 0) ?>&template_id=' + encodeURIComponent(this.value);} else { window.location.href = '/medical-records/create?patient_id=<?= (int)($patient['id'] ?? 0) ?>'; }">
            <option value="">(sem template)</option>
            <?php $curTplId = (int)($template['id'] ?? 0); ?>
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
                        ?>
                        <label class="lc-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?><?= $req ? ' *' : '' ?></label>
                        <?php if ($type === 'textarea'): ?>
                            <textarea class="lc-input" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" rows="4" <?= $req ? 'required' : '' ?>></textarea>
                        <?php elseif ($type === 'checkbox'): ?>
                            <select class="lc-select" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
                                <option value="0">Não</option>
                                <option value="1">Sim</option>
                            </select>
                        <?php elseif ($type === 'select'): ?>
                            <select class="lc-select" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" <?= $req ? 'required' : '' ?>>
                                <option value="">Selecione</option>
                                <?php foreach ($opts as $o): ?>
                                    <option value="<?= htmlspecialchars((string)$o, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$o, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($type === 'number'): ?>
                            <input class="lc-input" type="number" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" <?= $req ? 'required' : '' ?> />
                        <?php elseif ($type === 'date'): ?>
                            <input class="lc-input" type="date" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" <?= $req ? 'required' : '' ?> />
                        <?php else: ?>
                            <input class="lc-input" type="text" name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" <?= $req ? 'required' : '' ?> />
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <label class="lc-label">Descrição clínica</label>
        <textarea class="lc-input" name="clinical_description" rows="5"></textarea>

        <label class="lc-label">Evolução</label>
        <textarea class="lc-input" name="clinical_evolution" rows="5"></textarea>

        <label class="lc-label">Notas</label>
        <textarea class="lc-input" name="notes" rows="4"></textarea>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/medical-records?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Voltar</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
