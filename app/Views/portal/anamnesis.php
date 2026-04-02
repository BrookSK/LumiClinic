<?php
$title = 'Anamnese';
$pending = $pending ?? [];
$completed = $completed ?? [];

ob_start();
?>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:6px;">Anamnese</div>
<div style="font-size:13px;color:rgba(31,41,55,.50);margin-bottom:18px;">Formulários de anamnese enviados pela clínica. Preencha os pendentes para agilizar seu atendimento.</div>

<!-- Pendentes -->
<?php if (is_array($pending) && $pending !== []): ?>
<div style="padding:18px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.06);margin-bottom:16px;">
    <div style="font-weight:750;font-size:14px;color:rgba(129,89,1,1);margin-bottom:10px;">📋 Pendentes (<?= count($pending) ?>)</div>
    <div style="display:flex;flex-direction:column;gap:8px;">
        <?php foreach ($pending as $p): ?>
            <?php
            $tplName = trim((string)($p['template_name'] ?? $p['template_name_snapshot'] ?? ''));
            $createdAt = (string)($p['created_at'] ?? '');
            $createdFmt = $createdAt;
            try { $createdFmt = (new \DateTimeImmutable($createdAt))->format('d/m/Y'); } catch (\Throwable $e) {}
            $fillUrl = '/portal/anamnese/preencher?id=' . (int)($p['id'] ?? 0);
            ?>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid rgba(238,184,16,.18);background:rgba(255,255,255,.60);flex-wrap:wrap;">
                <div>
                    <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);"><?= htmlspecialchars($tplName !== '' ? $tplName : 'Anamnese', ENT_QUOTES, 'UTF-8') ?></div>
                    <div style="font-size:11px;color:rgba(31,41,55,.45);">Enviado em <?= htmlspecialchars($createdFmt, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <a class="lc-btn lc-btn--primary lc-btn--sm" href="<?= htmlspecialchars($fillUrl, ENT_QUOTES, 'UTF-8') ?>">Preencher</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Preenchidas -->
<div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
    <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:10px;">Preenchidas</div>

    <?php if (!is_array($completed) || $completed === []): ?>
        <div style="text-align:center;padding:20px;color:rgba(31,41,55,.40);font-size:13px;">Nenhuma anamnese preenchida ainda.</div>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:6px;">
            <?php foreach ($completed as $c): ?>
                <?php
                $tplName = trim((string)($c['template_name'] ?? $c['template_name_snapshot'] ?? ''));
                $createdAt = (string)($c['created_at'] ?? '');
                $createdFmt = $createdAt;
                try { $createdFmt = (new \DateTimeImmutable($createdAt))->format('d/m/Y'); } catch (\Throwable $e) {}
                $hasSig = !empty($c['signature_data_url']);
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:rgba(22,163,74,.12);color:#16a34a;border:1px solid rgba(22,163,74,.22);">Preenchida</span>
                        <span style="font-weight:700;font-size:13px;"><?= htmlspecialchars($tplName !== '' ? $tplName : 'Anamnese', ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($hasSig): ?>
                            <span style="font-size:11px;color:rgba(31,41,55,.40);">✍️ Assinada</span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:11px;color:rgba(31,41,55,.35);"><?= htmlspecialchars($createdFmt, ENT_QUOTES, 'UTF-8') ?></span>
                        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/portal/anamnesis/export-pdf?id=<?= (int)($c['id'] ?? 0) ?>">PDF</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ((!is_array($pending) || $pending === []) && (!is_array($completed) || $completed === [])): ?>
<div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
    <div style="font-size:32px;margin-bottom:8px;">📋</div>
    <div style="font-size:14px;">Nenhuma anamnese disponível. A clínica enviará quando necessário.</div>
</div>
<?php endif; ?>

<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'anamnese';
require __DIR__ . '/_shell.php';
