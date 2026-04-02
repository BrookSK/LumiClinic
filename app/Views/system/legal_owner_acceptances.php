<?php
$title = 'Admin - Aceites (Owners)';
$rows = $rows ?? [];

ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Relatório de aceites</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Acompanhe quais owners já aceitaram os termos obrigatórios.</div>
    </div>
    <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/legal-owner-documents">Gerenciar termos</a>
</div>

<?php if (!$rows): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);"><div style="font-size:32px;margin-bottom:8px;">✅</div><div>Nenhum registro de aceite.</div></div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:8px;">
    <?php foreach ($rows as $r): ?>
        <?php
        $total = (int)($r['required_total'] ?? 0);
        $accepted = (int)($r['required_accepted'] ?? 0);
        $pending = max(0, $total - $accepted);
        $allOk = $pending === 0 && $total > 0;
        $lastAt = trim((string)($r['last_accepted_at'] ?? ''));
        $lastFmt = $lastAt !== '' ? date('d/m/Y H:i', strtotime($lastAt)) : '—';
        $ownerName = trim((string)($r['owner_name'] ?? ''));
        $ownerEmail = trim((string)($r['owner_email'] ?? ''));
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-radius:14px;border:1px solid <?= $allOk ? 'rgba(22,163,74,.18)' : ($pending > 0 ? 'rgba(238,184,16,.22)' : 'rgba(17,24,39,.08)') ?>;background:<?= $allOk ? 'rgba(22,163,74,.03)' : 'var(--lc-surface)' ?>;box-shadow:0 2px 8px rgba(17,24,39,.04);flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:14px;min-width:0;flex-wrap:wrap;">
                <div>
                    <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.96);"><?= htmlspecialchars((string)($r['clinic_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if ($ownerName !== '' || $ownerEmail !== ''): ?>
                        <div style="font-size:12px;color:rgba(31,41,55,.45);"><?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?> <?= $ownerEmail !== '' ? '(' . htmlspecialchars($ownerEmail, ENT_QUOTES, 'UTF-8') . ')' : '' ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($allOk): ?>
                    <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:rgba(22,163,74,.12);color:#16a34a;border:1px solid rgba(22,163,74,.22);">Tudo aceito</span>
                <?php elseif ($pending > 0): ?>
                    <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:rgba(238,184,16,.12);color:rgba(129,89,1,1);border:1px solid rgba(238,184,16,.22);"><?= $pending ?> pendente<?= $pending > 1 ? 's' : '' ?></span>
                <?php endif; ?>
            </div>
            <div style="font-size:12px;color:rgba(31,41,55,.40);text-align:right;">
                <div><?= $accepted ?>/<?= $total ?> aceitos</div>
                <div>Último: <?= htmlspecialchars($lastFmt, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
