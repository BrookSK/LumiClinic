<?php
$title    = 'Detalhe da Assinatura';
$row      = $row ?? null;
$hash_ok  = (bool)($hash_ok ?? false);
$computed_hash = (string)($computed_hash ?? '');

$scopeLabels = [
    'patient_portal' => 'Portal do Paciente',
    'system_user'    => 'Equipe Interna',
    'clinic_owner'   => 'Owner',
];

$signedAt = (string)($row['signed_at'] ?? '');
$signedFmt = $signedAt;
try { $signedFmt = (new \DateTimeImmutable($signedAt))->format('d/m/Y \à\s H:i'); } catch (\Throwable $e) {}

$scopeRaw   = (string)($row['scope'] ?? '');
$scopeLabel = $scopeLabels[$scopeRaw] ?? $scopeRaw;

$who = '';
$whoSub = '';
if ((int)($row['patient_user_id'] ?? 0) > 0) {
    $who    = (string)($row['patient_name'] ?? '');
    $whoSub = (string)($row['patient_user_email'] ?? '');
} else {
    $who    = (string)($row['user_name'] ?? '');
    $whoSub = (string)($row['user_email'] ?? '');
}

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div style="font-weight:800; font-size:18px;">Detalhe da Assinatura</div>
    <a class="lc-btn lc-btn--secondary" href="/clinic/legal-signatures">Voltar</a>
</div>

<div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr;">

    <!-- Metadados -->
    <div class="lc-card" style="margin:0;">
        <div class="lc-card__header" style="font-weight:700;">Informações</div>
        <div class="lc-card__body" style="display:flex; flex-direction:column; gap:12px;">

            <div>
                <div class="lc-muted" style="font-size:12px;">Documento</div>
                <div style="font-weight:700; font-size:15px;"><?= htmlspecialchars((string)($row['document_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="lc-muted" style="font-size:12px;">Versão #<?= (int)($row['version_number'] ?? 0) ?> · <?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <div>
                <div class="lc-muted" style="font-size:12px;">Assinante</div>
                <div style="font-weight:600;"><?= htmlspecialchars($who, ENT_QUOTES, 'UTF-8') ?></div>
                <?php if ($whoSub !== ''): ?>
                    <div class="lc-muted" style="font-size:12px;"><?= htmlspecialchars($whoSub, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <div>
                <div class="lc-muted" style="font-size:12px;">Data e hora</div>
                <div style="font-weight:600;"><?= htmlspecialchars($signedFmt, ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <div>
                <div class="lc-muted" style="font-size:12px;">IP de origem</div>
                <div style="font-family:monospace; font-size:13px;"><?= htmlspecialchars((string)($row['ip_address'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <div>
                <div class="lc-muted" style="font-size:12px;">Integridade do documento</div>
                <?php if ($hash_ok): ?>
                    <div class="lc-flex lc-gap-sm" style="align-items:center; margin-top:4px;">
                        <span class="lc-badge lc-badge--success">✓ Verificado</span>
                        <span class="lc-muted" style="font-size:12px;">O conteúdo não foi alterado desde a assinatura.</span>
                    </div>
                <?php else: ?>
                    <div class="lc-flex lc-gap-sm" style="align-items:center; margin-top:4px;">
                        <span class="lc-badge lc-badge--danger">✗ Falhou</span>
                        <span class="lc-muted" style="font-size:12px;">O conteúdo pode ter sido alterado.</span>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Assinatura -->
    <div class="lc-card" style="margin:0;">
        <div class="lc-card__header" style="font-weight:700;">Assinatura capturada</div>
        <div class="lc-card__body" style="display:flex; align-items:center; justify-content:center; min-height:160px;">
            <?php if (!empty($row['signature_data_url'])): ?>
                <div style="border:1px solid rgba(0,0,0,.1); border-radius:10px; padding:16px; background:#fff; width:100%;">
                    <img
                        alt="Assinatura"
                        src="<?= htmlspecialchars((string)$row['signature_data_url'], ENT_QUOTES, 'UTF-8') ?>"
                        style="max-width:100%; height:auto; display:block; margin:0 auto;"
                    />
                </div>
            <?php else: ?>
                <div class="lc-muted" style="text-align:center;">Assinatura não disponível (aceite por checkbox).</div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Conteúdo assinado -->
<div class="lc-card" style="margin-top:14px;">
    <div class="lc-card__header" style="font-weight:700;">Conteúdo assinado (versão imutável)</div>
    <div class="lc-card__body">
        <div style="background:#f9fafb; border:1px solid rgba(0,0,0,.08); border-radius:8px; padding:16px; white-space:pre-wrap; line-height:1.7; font-size:13px; max-height:400px; overflow-y:auto;">
            <?= nl2br(htmlspecialchars((string)($row['version_body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
        </div>
    </div>
</div>

<!-- Hashes técnicos (colapsado) -->
<details style="margin-top:10px;">
    <summary class="lc-muted" style="cursor:pointer; font-size:12px; padding:6px 0;">Detalhes técnicos (hashes)</summary>
    <div class="lc-card" style="margin-top:8px;">
        <div class="lc-card__body" style="font-size:12px;">
            <div style="margin-bottom:8px;">
                <div class="lc-muted">Hash armazenado</div>
                <code style="word-break:break-all; font-size:11px;"><?= htmlspecialchars((string)($row['document_hash_sha256'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
            </div>
            <div>
                <div class="lc-muted">Hash recalculado</div>
                <code style="word-break:break-all; font-size:11px;"><?= htmlspecialchars($computed_hash, ENT_QUOTES, 'UTF-8') ?></code>
            </div>
            <?php if (!empty($row['user_agent'])): ?>
            <div style="margin-top:8px;">
                <div class="lc-muted">User-Agent</div>
                <div style="font-size:11px; color:#6b7280; word-break:break-all;"><?= htmlspecialchars((string)$row['user_agent'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</details>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
