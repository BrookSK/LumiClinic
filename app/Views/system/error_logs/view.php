<?php
$title = 'Admin - Detalhe do Erro';
$item = $item ?? [];
if (!is_array($item)) $item = [];

$st = (int)($item['status_code'] ?? 0);
$statusInfo = [402=>['label'=>'402 Assinatura','color'=>'#b5841e'],403=>['label'=>'403 Acesso negado','color'=>'#6b7280'],404=>['label'=>'404 Não encontrado','color'=>'#6b7280'],500=>['label'=>'500 Erro interno','color'=>'#b91c1c'],503=>['label'=>'503 Indisponível','color'=>'#b91c1c']];
$si = $statusInfo[$st] ?? ['label'=>(string)$st,'color'=>'#6b7280'];
$createdAt = (string)($item['created_at'] ?? '');
$createdFmt = $createdAt !== '' ? date('d/m/Y H:i:s', strtotime($createdAt)) : '—';

ob_start();
?>

<a href="/sys/error-logs" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para logs
</a>

<!-- Header -->
<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
    <span style="display:inline-flex;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;background:<?= $si['color'] ?>18;color:<?= $si['color'] ?>;border:1px solid <?= $si['color'] ?>30"><?= htmlspecialchars($si['label'], ENT_QUOTES, 'UTF-8') ?></span>
    <span style="font-size:13px;font-family:monospace;color:rgba(31,41,55,.70);"><?= htmlspecialchars(trim((string)($item['method'] ?? '') . ' ' . (string)($item['path'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
    <span style="font-size:12px;color:rgba(31,41,55,.40);"><?= htmlspecialchars($createdFmt, ENT_QUOTES, 'UTF-8') ?></span>
</div>

<!-- Resumo -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:16px;">
    <div style="padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);">
        <div style="font-size:11px;color:rgba(31,41,55,.45);">Tipo</div>
        <div style="font-weight:700;font-size:13px;margin-top:2px;"><?= htmlspecialchars((string)($item['error_type'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div style="padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);">
        <div style="font-size:11px;color:rgba(31,41,55,.45);">Clínica</div>
        <div style="font-weight:700;font-size:13px;margin-top:2px;"><?= ((int)($item['clinic_id'] ?? 0) > 0) ? '#' . (int)$item['clinic_id'] : '—' ?></div>
    </div>
    <div style="padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);">
        <div style="font-size:11px;color:rgba(31,41,55,.45);">Usuário</div>
        <div style="font-weight:700;font-size:13px;margin-top:2px;"><?= ((int)($item['user_id'] ?? 0) > 0) ? '#' . (int)$item['user_id'] : '—' ?></div>
    </div>
    <div style="padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);">
        <div style="font-size:11px;color:rgba(31,41,55,.45);">IP</div>
        <div style="font-weight:700;font-size:13px;margin-top:2px;font-family:monospace;"><?= htmlspecialchars((string)($item['ip'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
</div>

<!-- Mensagem -->
<?php $msg = trim((string)($item['message'] ?? '')); ?>
<?php if ($msg !== ''): ?>
<div style="padding:16px;border-radius:14px;border:1px solid rgba(185,28,28,.18);background:rgba(185,28,28,.03);margin-bottom:16px;">
    <div style="font-weight:750;font-size:13px;color:rgba(185,28,28,.80);margin-bottom:8px;">Mensagem</div>
    <div style="white-space:pre-wrap;line-height:1.5;font-size:13px;color:rgba(31,41,55,.80);"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
</div>
<?php endif; ?>

<!-- Contexto -->
<?php $ctx = trim((string)($item['context_json'] ?? '')); ?>
<?php if ($ctx !== ''): ?>
<details style="margin-bottom:16px;">
    <summary style="padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);cursor:pointer;list-style:none;font-weight:750;font-size:13px;color:rgba(31,41,55,.70);">Contexto (JSON)</summary>
    <div style="margin-top:8px;padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.02);">
        <pre style="white-space:pre-wrap;line-height:1.4;font-size:11px;color:rgba(31,41,55,.60);margin:0;"><?= htmlspecialchars($ctx, ENT_QUOTES, 'UTF-8') ?></pre>
    </div>
</details>
<?php endif; ?>

<!-- Stack trace -->
<?php $trace = trim((string)($item['trace_text'] ?? '')); ?>
<?php if ($trace !== ''): ?>
<details>
    <summary style="padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);cursor:pointer;list-style:none;font-weight:750;font-size:13px;color:rgba(31,41,55,.70);">Stack trace</summary>
    <div style="margin-top:8px;padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.02);max-height:400px;overflow:auto;">
        <pre style="white-space:pre-wrap;line-height:1.4;font-size:11px;color:rgba(31,41,55,.50);margin:0;"><?= htmlspecialchars($trace, ENT_QUOTES, 'UTF-8') ?></pre>
    </div>
</details>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
