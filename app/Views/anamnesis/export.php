<?php
$title    = 'Exportar anamnese';
$patient  = $patient ?? null;
$template = $template ?? null;
$response = $response ?? null;
$answers  = $answers ?? [];
$fields   = $fields ?? [];

$patientName  = (string)($patient['name'] ?? '');
$templateName = (string)($response['template_name_snapshot'] ?? $template['name'] ?? '');
$createdAt    = (string)($response['created_at'] ?? '');
$dateFmt = '';
try { $dateFmt = $createdAt !== '' ? (new \DateTimeImmutable($createdAt))->format('d/m/Y H:i') : ''; } catch (\Throwable $e) { $dateFmt = $createdAt; }

$fieldMap = [];
foreach ($fields as $f) {
    $k = (string)($f['field_key'] ?? '');
    if ($k !== '') $fieldMap[$k] = $f;
}

$hasSig = !empty($response['signature_data_url']);

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;">Anamnese</div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px;">
            <?= htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') ?>
            · <?= htmlspecialchars($templateName, ENT_QUOTES, 'UTF-8') ?>
            · <?= htmlspecialchars($dateFmt, ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/anamnesis?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Voltar</a>
        <a class="lc-btn lc-btn--secondary" href="/anamnesis/export-pdf?id=<?= (int)($response['id'] ?? 0) ?>" target="_blank">PDF</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <?php if (empty($answers)): ?>
            <div class="lc-muted">Nenhuma resposta registrada.</div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:14px;">
                <?php foreach ($answers as $k => $v): ?>
                    <?php
                    $key   = (string)$k;
                    $label = $key;
                    $type  = null;
                    if (isset($fieldMap[$key])) {
                        $label = (string)($fieldMap[$key]['label'] ?? $key);
                        $type  = (string)($fieldMap[$key]['field_type'] ?? '');
                    }
                    $display = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string)$v;
                    if ($type === 'checkbox') {
                        $display = in_array(strtolower(trim($display)), ['1','true','sim'], true) ? 'Sim' : 'Não';
                    }
                    ?>
                    <div style="border-bottom:1px solid rgba(0,0,0,.06); padding-bottom:10px;">
                        <div style="font-size:12px; color:#6b7280; font-weight:600; margin-bottom:3px;"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
                        <div style="font-size:14px;"><?= nl2br(htmlspecialchars($display, ENT_QUOTES, 'UTF-8')) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($hasSig): ?>
<div class="lc-card">
    <div class="lc-card__header" style="font-weight:700;">Assinatura do paciente</div>
    <div class="lc-card__body" style="display:flex; justify-content:center;">
        <div style="border:1px solid rgba(0,0,0,.1); border-radius:10px; padding:16px; background:#fff; max-width:400px; width:100%;">
            <img src="<?= htmlspecialchars((string)$response['signature_data_url'], ENT_QUOTES, 'UTF-8') ?>"
                 alt="Assinatura" style="max-width:100%; height:auto; display:block; margin:0 auto;" />
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
