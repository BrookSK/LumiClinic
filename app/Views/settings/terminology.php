<?php
$title = 'Terminologia';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$terminology = $terminology ?? null;
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Customização de termos</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/settings/terminology">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Paciente / Cliente</label>
        <input class="lc-input" type="text" name="patient_label" value="<?= htmlspecialchars((string)($terminology['patient_label'] ?? 'Paciente'), ENT_QUOTES, 'UTF-8') ?>" required />

        <label class="lc-label">Consulta / Sessão</label>
        <input class="lc-input" type="text" name="appointment_label" value="<?= htmlspecialchars((string)($terminology['appointment_label'] ?? 'Consulta'), ENT_QUOTES, 'UTF-8') ?>" required />

        <label class="lc-label">Profissional / Especialista</label>
        <input class="lc-input" type="text" name="professional_label" value="<?= htmlspecialchars((string)($terminology['professional_label'] ?? 'Profissional'), ENT_QUOTES, 'UTF-8') ?>" required />

        <div style="margin-top:14px; display:flex; gap:10px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/settings">Voltar</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
