<?php
$title = 'Prontuário';
$patient = $patient ?? null;
$records = $records ?? [];
ob_start();
?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:10px; flex-wrap:wrap;">
    <div class="lc-badge lc-badge--gold">Prontuário</div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)($patient['id'] ?? 0) ?>">Voltar ao paciente</a>
        <a class="lc-btn lc-btn--primary" href="/medical-records/create?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Novo registro</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="lc-card__body">
        CPF: <?= isset($patient['cpf_last4']) && $patient['cpf_last4'] ? '***.' . htmlspecialchars((string)$patient['cpf_last4'], ENT_QUOTES, 'UTF-8') : '' ?>
    </div>
</div>

<?php foreach ($records as $r): ?>
    <div id="mr-<?= (int)$r['id'] ?>" class="lc-card" style="margin-bottom:12px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
            <div>
                <div class="lc-card__title">Atendimento em <?= htmlspecialchars((string)$r['attended_at'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="lc-card__body">
                    <?= htmlspecialchars((string)($r['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
            <div>
                <a class="lc-btn lc-btn--secondary" href="/medical-records/edit?patient_id=<?= (int)$patient['id'] ?>&id=<?= (int)$r['id'] ?>">Editar</a>
            </div>
        </div>

        <?php if (($r['clinical_description'] ?? '') !== ''): ?>
            <div style="margin-top:12px;">
                <div class="lc-label">Descrição clínica</div>
                <div><?= nl2br(htmlspecialchars((string)$r['clinical_description'], ENT_QUOTES, 'UTF-8')) ?></div>
            </div>
        <?php endif; ?>

        <?php if (($r['clinical_evolution'] ?? '') !== ''): ?>
            <div style="margin-top:12px;">
                <div class="lc-label">Evolução</div>
                <div><?= nl2br(htmlspecialchars((string)$r['clinical_evolution'], ENT_QUOTES, 'UTF-8')) ?></div>
            </div>
        <?php endif; ?>

        <?php if (($r['notes'] ?? '') !== ''): ?>
            <div style="margin-top:12px;">
                <div class="lc-label">Notas</div>
                <div><?= nl2br(htmlspecialchars((string)$r['notes'], ENT_QUOTES, 'UTF-8')) ?></div>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
