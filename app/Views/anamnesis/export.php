<?php
 $title = 'Exportar anamnese';
 $patient = $patient ?? null;
 $template = $template ?? null;
 $response = $response ?? null;
 $answers = $answers ?? [];
 $fields = $fields ?? [];

 $patientName = htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8');
 $templateName = (string)($response['template_name_snapshot'] ?? $template['name'] ?? '');
 $createdAt = htmlspecialchars((string)($response['created_at'] ?? ''), ENT_QUOTES, 'UTF-8');
 $templateUpdatedAt = (string)($response['template_updated_at_snapshot'] ?? $template['updated_at'] ?? '');

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

 <div class="lc-card" style="margin-bottom:14px;">
     <div class="lc-card__title">Anamnese</div>
     <div class="lc-card__body">
         <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr;">
            <div>
                <div class="lc-muted" style="font-size:12px;">Paciente</div>
                <div style="font-weight:700;"><?= $patientName ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Registrado em</div>
                <div style="font-weight:700;"><?= $createdAt ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Template</div>
                <div><?= htmlspecialchars($templateName, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Versão do template</div>
                <div><?= htmlspecialchars($templateUpdatedAt !== '' ? $templateUpdatedAt : '-', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
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
