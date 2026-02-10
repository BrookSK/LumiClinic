<?php
$title = 'Anamnese';
$patient = $patient ?? null;
$templates = $templates ?? [];
$responses = $responses ?? [];

$templateMap = [];
if (is_array($templates)) {
    foreach ($templates as $t) {
        $tid = isset($t['id']) ? (int)$t['id'] : 0;
        if ($tid > 0) {
            $templateMap[$tid] = (string)($t['name'] ?? '');
        }
    }
}
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Anamnese</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)($patient['id'] ?? 0) ?>">Voltar ao paciente</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="lc-card__body">
        CPF: <?= isset($patient['cpf_last4']) && $patient['cpf_last4'] ? '***.' . htmlspecialchars((string)$patient['cpf_last4'], ENT_QUOTES, 'UTF-8') : '' ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Preencher</div>

    <form method="get" action="/anamnesis/fill" class="lc-form">
        <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />

        <label class="lc-label">Template</label>
        <select class="lc-select" name="template_id" required>
            <option value="">Selecione</option>
            <?php foreach ($templates as $t): ?>
                <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars((string)$t['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Continuar</button>
        </div>
    </form>
</div>

<div class="lc-card">
    <div class="lc-card__title">Respostas registradas</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Template</th>
                <th>Criado em</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($responses as $r): ?>
                <tr>
                    <td><?= (int)$r['id'] ?></td>
                    <?php $tid = (int)($r['template_id'] ?? 0); ?>
                    <td><?= htmlspecialchars(($templateMap[$tid] ?? '') !== '' ? (string)$templateMap[$tid] : ('Template #' . $tid), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$r['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
