<?php
 $title = 'Exportar termo';
 $patient = $patient ?? null;
 $term = $term ?? null;
 $acceptance = $acceptance ?? null;
 $signature = $signature ?? null;
 
 $patientName = htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8');
 
 $acceptedAt = htmlspecialchars((string)($acceptance['accepted_at'] ?? ''), ENT_QUOTES, 'UTF-8');
 $procedureType = (string)($acceptance['term_procedure_type_snapshot'] ?? $acceptance['procedure_type'] ?? $term['procedure_type'] ?? '');
 $termTitle = (string)($acceptance['term_title_snapshot'] ?? $term['title'] ?? '');
 $termBody = (string)($acceptance['term_body_snapshot'] ?? $term['body'] ?? '');
 $termUpdatedAt = (string)($acceptance['term_updated_at_snapshot'] ?? $term['updated_at'] ?? '');
 
 ob_start();
 ?>
 
 <div class="lc-card" style="margin-bottom:14px;">
     <div class="lc-card__title">Termo aceito</div>
     <div class="lc-card__body">
         <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr;">
             <div>
                 <div class="lc-muted" style="font-size:12px;">Paciente</div>
                 <div style="font-weight:700;"><?= $patientName ?></div>
             </div>
             <div>
                 <div class="lc-muted" style="font-size:12px;">Aceito em</div>
                 <div style="font-weight:700;"><?= $acceptedAt ?></div>
             </div>
             <div>
                 <div class="lc-muted" style="font-size:12px;">Procedimento</div>
                 <div><?= htmlspecialchars($procedureType, ENT_QUOTES, 'UTF-8') ?></div>
             </div>
             <div>
                 <div class="lc-muted" style="font-size:12px;">Versão do termo</div>
                 <div><?= htmlspecialchars($termUpdatedAt !== '' ? $termUpdatedAt : '-', ENT_QUOTES, 'UTF-8') ?></div>
             </div>
         </div>
     </div>
 </div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title"><?= htmlspecialchars($termTitle, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="lc-card__body">
        <div style="white-space:pre-wrap; line-height:1.6;">
            <?= nl2br(htmlspecialchars($termBody, ENT_QUOTES, 'UTF-8')) ?>
        </div>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Assinatura</div>
    <div class="lc-card__body">
        <?php if ($signature !== null && isset($signature['id'])): ?>
            <div style="margin-bottom:10px;">
                <a class="lc-btn lc-btn--secondary" href="/signatures/file?id=<?= (int)($signature['id'] ?? 0) ?>" target="_blank">Abrir assinatura (arquivo)</a>
            </div>
            <div class="lc-muted" style="font-size:12px;">ID assinatura: <?= (int)($signature['id'] ?? 0) ?></div>
        <?php else: ?>
            <div class="lc-alert lc-alert--danger">Assinatura não encontrada para este aceite.</div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
