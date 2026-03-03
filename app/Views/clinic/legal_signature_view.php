<?php
$title = 'Assinatura';
$row = $row ?? null;
$hash_ok = (bool)($hash_ok ?? false);
$computed_hash = (string)($computed_hash ?? '');
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Detalhes da assinatura</div>

    <div class="lc-flex lc-gap-sm" style="margin-top:12px;">
        <a class="lc-btn lc-btn--secondary" href="/clinic/legal-signatures">Voltar</a>
    </div>

    <div style="margin-top:12px;">
        <div><strong>Documento:</strong> <?= htmlspecialchars((string)($row['document_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Escopo:</strong> <?= htmlspecialchars((string)($row['scope'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Versão:</strong> #<?= (int)($row['version_number'] ?? 0) ?></div>
        <div><strong>Assinado em:</strong> <?= htmlspecialchars((string)($row['signed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>IP:</strong> <?= htmlspecialchars((string)($row['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>User-Agent:</strong> <?= htmlspecialchars((string)($row['user_agent'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div style="margin-top:12px;">
        <div><strong>Hash armazenado:</strong> <code><?= htmlspecialchars((string)($row['document_hash_sha256'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></div>
        <div><strong>Hash recalculado:</strong> <code><?= htmlspecialchars($computed_hash, ENT_QUOTES, 'UTF-8') ?></code></div>
        <div style="margin-top:6px;">
            <?php if ($hash_ok): ?>
                <div class="lc-alert lc-alert--success">Integridade OK.</div>
            <?php else: ?>
                <div class="lc-alert lc-alert--danger">Integridade FALHOU (conteúdo não confere com o hash).</div>
            <?php endif; ?>
        </div>
    </div>

    <div style="margin-top:12px;">
        <div class="lc-alert lc-alert--info">Assinatura (imagem capturada no momento):</div>
        <div style="margin-top:10px; border:1px solid #ddd; border-radius:10px; padding:10px; background:#fff;">
            <img alt="Assinatura" src="<?= htmlspecialchars((string)($row['signature_data_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="max-width:100%; height:auto; display:block;" />
        </div>
    </div>

    <div style="margin-top:12px;">
        <div class="lc-alert lc-alert--secondary" style="white-space:pre-wrap; line-height:1.6;">
            <strong>Conteúdo assinado (versão imutável):</strong>
            \n\n<?= nl2br(htmlspecialchars((string)($row['version_body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
        </div>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
