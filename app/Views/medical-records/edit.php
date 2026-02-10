<?php
$title = 'Editar registro';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$patient = $patient ?? null;
$record = $record ?? null;
$professionals = $professionals ?? [];
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
