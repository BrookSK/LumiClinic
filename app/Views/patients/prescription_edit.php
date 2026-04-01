<?php
$title = 'Editar Receita';
$rx = $rx ?? null;
$professionals = $professionals ?? [];
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? '';
$success = $success ?? '';

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Editar Receita</div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/patients/prescriptions?patient_id=<?= (int)($rx['patient_id'] ?? 0) ?>">Voltar</a>
        <a class="lc-btn lc-btn--secondary" href="/patients/prescription/print?id=<?= (int)($rx['id'] ?? 0) ?>" target="_blank">Imprimir</a>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__body">
        <form method="post" action="/patients/prescriptions/update" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= (int)($rx['id'] ?? 0) ?>" />

            <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr; align-items:end;">
                <div class="lc-field">
                    <label class="lc-label">Título</label>
                    <input class="lc-input" type="text" name="title" value="<?= htmlspecialchars((string)($rx['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Data de emissão</label>
                    <input class="lc-input" type="date" name="issued_at" value="<?= htmlspecialchars((string)($rx['issued_at'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>" required />
                </div>
            </div>

            <div class="lc-field" style="margin-top:10px;">
                <label class="lc-label">Conteúdo</label>
                <textarea class="lc-input" name="body" rows="10" required><?= htmlspecialchars((string)($rx['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="lc-flex lc-gap-sm" style="margin-top:12px;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <a class="lc-btn lc-btn--secondary" href="/patients/prescriptions?patient_id=<?= (int)($rx['patient_id'] ?? 0) ?>">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
