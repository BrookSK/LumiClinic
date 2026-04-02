<?php
$title = 'Termos e Consentimentos';
$patient     = $patient ?? null;
$terms       = $terms ?? [];
$acceptances = $acceptances ?? [];
$signatures  = $signatures ?? [];

$patientId = (int)($patient['id'] ?? 0);

$termTitleMap = [];
foreach ($terms as $t) {
    $tid = (int)($t['id'] ?? 0);
    if ($tid > 0) $termTitleMap[$tid] = (string)($t['title'] ?? '');
}

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px;">Termos e Consentimentos</div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= $patientId ?>">Paciente</a>
        <a class="lc-btn lc-btn--primary" href="/consent/accept?patient_id=<?= $patientId ?>">+ Novo aceite</a>
    </div>
</div>

<!-- Aceites -->
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__header" style="font-weight:700;">
        Aceites registrados
        <span class="lc-badge lc-badge--secondary" style="margin-left:6px;"><?= count($acceptances) ?></span>
    </div>
    <div class="lc-card__body" style="padding:0;">
        <?php if (empty($acceptances)): ?>
            <div class="lc-muted" style="padding:20px; text-align:center;">Nenhum aceite registrado.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Termo</th>
                    <th>Procedimento</th>
                    <th>Assinatura</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($acceptances as $a): ?>
                    <?php
                    $tid = (int)($a['term_id'] ?? 0);
                    $snapTitle = trim((string)($a['term_title_snapshot'] ?? ''));
                    $tName = $snapTitle !== '' ? $snapTitle : ($termTitleMap[$tid] ?? ('Termo #' . $tid));
                    $acceptedAt = (string)($a['accepted_at'] ?? '');
                    $dateFmt = '';
                    try { $dateFmt = (new \DateTimeImmutable($acceptedAt))->format('d/m/Y H:i'); } catch (\Throwable $e) { $dateFmt = $acceptedAt; }

                    // Verificar se tem assinatura vinculada
                    $hasSig = false;
                    $sigId = null;
                    foreach ($signatures as $s) {
                        if ((int)($s['term_acceptance_id'] ?? 0) === (int)($a['id'] ?? 0)) {
                            $hasSig = true;
                            $sigId = (int)$s['id'];
                            break;
                        }
                    }
                    ?>
                    <tr>
                        <td style="white-space:nowrap; font-size:13px;"><?= htmlspecialchars($dateFmt, ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($tName, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($a['procedure_type'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($hasSig): ?>
                                <span class="lc-badge lc-badge--success" style="font-size:11px;">✓ Assinado</span>
                            <?php else: ?>
                                <span class="lc-badge lc-badge--secondary" style="font-size:11px;">Sem assinatura</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="lc-flex lc-gap-sm">
                                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/consent/export?id=<?= (int)$a['id'] ?>" target="_blank">Ver</a>
                                <?php if ($hasSig && $sigId !== null): ?>
                                    <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/signatures/file?id=<?= $sigId ?>" target="_blank">Assinatura</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
