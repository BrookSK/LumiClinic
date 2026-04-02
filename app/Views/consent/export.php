<?php
$title      = 'Termo aceito';
$patient    = $patient ?? null;
$term       = $term ?? null;
$acceptance = $acceptance ?? null;
$signature  = $signature ?? null;

$patientId   = (int)($patient['id'] ?? 0);
$patientName = (string)($patient['name'] ?? '');

$acceptedAt    = (string)($acceptance['accepted_at'] ?? '');
$procedureType = (string)($acceptance['term_procedure_type_snapshot'] ?? $acceptance['procedure_type'] ?? $term['procedure_type'] ?? '');
$termTitle     = (string)($acceptance['term_title_snapshot'] ?? $term['title'] ?? '');
$termBody      = (string)($acceptance['term_body_snapshot'] ?? $term['body'] ?? '');

$dateFmt = '';
try { $dateFmt = $acceptedAt !== '' ? (new \DateTimeImmutable($acceptedAt))->format('d/m/Y H:i') : ''; } catch (\Throwable $e) { $dateFmt = $acceptedAt; }

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;"><?= htmlspecialchars($termTitle, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px;">
            <?= htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') ?>
            · Aceito em <?= htmlspecialchars($dateFmt, ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/consent?patient_id=<?= $patientId ?>">Voltar</a>
    </div>
</div>

<!-- Dados do aceite -->
<div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr; margin-bottom:14px;">
    <div class="lc-card" style="margin:0;">
        <div class="lc-card__body">
            <div class="lc-muted" style="font-size:12px;">Paciente</div>
            <div style="font-weight:700;"><?= htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
    <div class="lc-card" style="margin:0;">
        <div class="lc-card__body">
            <div class="lc-muted" style="font-size:12px;">Procedimento</div>
            <div style="font-weight:600;"><?= htmlspecialchars($procedureType !== '' ? $procedureType : '—', ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
</div>

<!-- Conteúdo do termo -->
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__header" style="font-weight:700;">Conteúdo do termo</div>
    <div class="lc-card__body">
        <div style="background:#f9fafb; border:1px solid rgba(0,0,0,.08); border-radius:8px; padding:16px; white-space:pre-wrap; line-height:1.7; font-size:13px; max-height:400px; overflow-y:auto;">
            <?= nl2br(htmlspecialchars($termBody, ENT_QUOTES, 'UTF-8')) ?>
        </div>
    </div>
</div>

<!-- Assinatura -->
<div class="lc-card">
    <div class="lc-card__header" style="font-weight:700;">Assinatura</div>
    <div class="lc-card__body" style="display:flex; justify-content:center;">
        <?php if ($signature !== null && isset($signature['id'])): ?>
            <div style="text-align:center;">
                <div style="border:1px solid rgba(0,0,0,.1); border-radius:10px; padding:16px; background:#fff; display:inline-block;">
                    <img src="/signatures/file?id=<?= (int)$signature['id'] ?>" alt="Assinatura" style="max-width:300px; height:auto; display:block;" />
                </div>
                <div class="lc-muted" style="font-size:12px; margin-top:8px;">
                    Assinatura #<?= (int)$signature['id'] ?>
                    <?php if (!empty($signature['created_at'])): ?>
                        · <?php try { echo (new \DateTimeImmutable((string)$signature['created_at']))->format('d/m/Y H:i'); } catch (\Throwable $e) {} ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:20px;">
                <span class="lc-badge lc-badge--secondary">Sem assinatura digital</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
