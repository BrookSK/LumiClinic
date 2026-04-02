<?php
$title = 'Documentos';
$csrf = $_SESSION['_csrf'] ?? '';
$acceptances = $acceptances ?? [];
$signatures = $signatures ?? [];
$images = $images ?? [];

ob_start();
?>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:18px;">Meus documentos</div>

<!-- Termos aceitos -->
<div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:10px;">Termos aceitos</div>
    <?php if (!is_array($acceptances) || $acceptances === []): ?>
        <div style="font-size:13px;color:rgba(31,41,55,.40);">Nenhum termo aceito ainda.</div>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:6px;">
            <?php foreach ($acceptances as $a): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);">
                    <div>
                        <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars((string)($a['procedure_type'] ?? 'Termo'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div style="font-size:11px;color:rgba(31,41,55,.40);">Aceito em <?= htmlspecialchars((string)($a['accepted_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:rgba(22,163,74,.12);color:#16a34a;border:1px solid rgba(22,163,74,.22);">Aceito</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Assinaturas -->
<?php if (is_array($signatures) && $signatures !== []): ?>
<div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:10px;">Assinaturas</div>
    <div style="display:flex;flex-direction:column;gap:6px;">
        <?php foreach ($signatures as $s): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);">
                <div style="font-size:13px;color:rgba(31,41,55,.70);">Assinatura #<?= (int)($s['id'] ?? 0) ?> · <?= htmlspecialchars((string)($s['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/portal/signatures/file?id=<?= (int)($s['id'] ?? 0) ?>" target="_blank">Abrir</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Imagens -->
<?php if (is_array($images) && $images !== []): ?>
<div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
    <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:10px;">Imagens clínicas</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;">
        <?php foreach ($images as $img): ?>
            <?php
            $kindLabel = ['before'=>'Antes','after'=>'Depois','other'=>'Outro'];
            $kLbl = $kindLabel[(string)($img['kind'] ?? '')] ?? (string)($img['kind'] ?? '');
            ?>
            <a href="/portal/medical-images/file?id=<?= (int)($img['id'] ?? 0) ?>" target="_blank" style="padding:12px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:rgba(0,0,0,.01);text-decoration:none;color:inherit;text-align:center;">
                <div style="font-size:24px;margin-bottom:4px;">🖼️</div>
                <div style="font-weight:700;font-size:12px;"><?= htmlspecialchars($kLbl, ENT_QUOTES, 'UTF-8') ?></div>
                <div style="font-size:11px;color:rgba(31,41,55,.40);"><?= htmlspecialchars((string)($img['taken_at'] ?? $img['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if ((!is_array($acceptances) || $acceptances === []) && (!is_array($signatures) || $signatures === []) && (!is_array($images) || $images === [])): ?>
<div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
    <div style="font-size:32px;margin-bottom:8px;">📄</div>
    <div style="font-size:14px;">Nenhum documento disponível ainda.</div>
</div>
<?php endif; ?>

<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'documentos';
require __DIR__ . '/_shell.php';
