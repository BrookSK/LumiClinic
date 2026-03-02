<?php
$title = 'Respostas de anamnese';
$patient = $patient ?? null;
$template = $template ?? null;
$fields = $fields ?? [];
$response = $response ?? null;
$answers = $answers ?? [];

$fieldMap = [];
if (is_array($fields)) {
    foreach ($fields as $f) {
        $k = isset($f['field_key']) ? (string)$f['field_key'] : '';
        if ($k !== '') {
            $fieldMap[$k] = $f;
        }
    }
}

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Respostas</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/anamnesis?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Voltar</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title"><?= htmlspecialchars((string)($template['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="lc-card__body">
        <div class="lc-muted">Registro #<?= (int)($response['id'] ?? 0) ?> • <?= htmlspecialchars((string)($response['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Respostas</div>
    <div class="lc-card__body">
        <?php if (!is_array($answers) || $answers === []): ?>
            <div class="lc-muted">Nenhuma resposta registrada.</div>
        <?php else: ?>
            <div class="lc-grid lc-gap-grid">
                <?php foreach ($answers as $k => $v): ?>
                    <?php
                        $key = (string)$k;
                        $label = $key;
                        $type = null;
                        if (isset($fieldMap[$key]) && is_array($fieldMap[$key])) {
                            $label = (string)($fieldMap[$key]['label'] ?? $key);
                            $type = isset($fieldMap[$key]['field_type']) ? (string)$fieldMap[$key]['field_type'] : null;
                        }

                        $display = '';
                        if (is_array($v)) {
                            $display = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            $display = $display === false ? '' : $display;
                        } else {
                            $display = (string)$v;
                        }

                        if ($type === 'checkbox') {
                            $vv = trim((string)$display);
                            if ($vv === '1' || strtolower($vv) === 'true' || strtolower($vv) === 'sim') {
                                $display = 'Sim';
                            } elseif ($vv === '0' || strtolower($vv) === 'false' || strtolower($vv) === 'não' || strtolower($vv) === 'nao') {
                                $display = 'Não';
                            }
                        }
                    ?>
                    <div>
                        <div class="lc-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
                        <div><?= nl2br(htmlspecialchars($display, ENT_QUOTES, 'UTF-8')) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
