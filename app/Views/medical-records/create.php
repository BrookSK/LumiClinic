<?php
$title = 'Novo registro';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$patient = $patient ?? null;
$professionals = $professionals ?? [];
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

        <label class="lc-label">Descrição clínica</label>
        <textarea class="lc-input" name="clinical_description" rows="5"></textarea>

        <label class="lc-label">Evolução</label>
        <textarea class="lc-input" name="clinical_evolution" rows="5"></textarea>

        <label class="lc-label">Notas</label>
        <textarea class="lc-input" name="notes" rows="4"></textarea>

        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/medical-records?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Voltar</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
