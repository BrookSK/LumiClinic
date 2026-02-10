<?php
$title = 'Admin do Sistema';
$item = $item ?? null;
if (!is_array($item)) {
    $item = [];
}

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Detalhe do erro</div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/sys/error-logs">Voltar</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Resumo</div>
    <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr;">
        <div><b>ID:</b> <?= (int)($item['id'] ?? 0) ?></div>
        <div><b>Status:</b> <?= (int)($item['status_code'] ?? 0) ?></div>
        <div><b>Tipo:</b> <?= htmlspecialchars((string)($item['error_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><b>Data:</b> <?= htmlspecialchars((string)($item['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div style="grid-column: 1 / -1;"><b>Rota:</b> <?= htmlspecialchars(trim((string)($item['method'] ?? '') . ' ' . (string)($item['path'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
        <div><b>Clínica:</b> <?= ((int)($item['clinic_id'] ?? 0) > 0) ? (int)$item['clinic_id'] : '-' ?></div>
        <div><b>Usuário:</b> <?= ((int)($item['user_id'] ?? 0) > 0) ? (int)$item['user_id'] : '-' ?></div>
        <div><b>Super Admin:</b> <?= ((int)($item['is_super_admin'] ?? 0) === 1) ? 'Sim' : 'Não' ?></div>
        <div><b>IP:</b> <?= htmlspecialchars((string)($item['ip'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Mensagem</div>
    <div style="white-space:pre-wrap; line-height:1.55;">
        <?= htmlspecialchars((string)($item['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>

<?php $ctx = (string)($item['context_json'] ?? ''); ?>
<?php if (trim($ctx) !== ''): ?>
    <div class="lc-card" style="margin-bottom:14px;">
        <div class="lc-card__title">Contexto</div>
        <pre style="white-space:pre-wrap; line-height:1.45; font-size:12px;"><?= htmlspecialchars($ctx, ENT_QUOTES, 'UTF-8') ?></pre>
    </div>
<?php endif; ?>

<?php $trace = (string)($item['trace_text'] ?? ''); ?>
<?php if (trim($trace) !== ''): ?>
    <div class="lc-card">
        <div class="lc-card__title">Detalhes técnicos</div>
        <pre style="white-space:pre-wrap; line-height:1.45; font-size:12px;"><?= htmlspecialchars($trace, ENT_QUOTES, 'UTF-8') ?></pre>
    </div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
