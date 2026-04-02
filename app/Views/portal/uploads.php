<?php
$title = 'Enviar Fotos';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$uploads = $uploads ?? [];

$statusLabel = ['pending'=>'Aguardando','approved'=>'Aprovado','rejected'=>'Rejeitado'];
$statusColor = ['pending'=>'#eeb810','approved'=>'#16a34a','rejected'=>'#b91c1c'];
$kindLabel = ['before'=>'Antes','after'=>'Depois','other'=>'Outro'];

ob_start();
?>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:6px;">Enviar fotos</div>
<div style="font-size:13px;color:rgba(31,41,55,.50);margin-bottom:18px;">Envie fotos para a clínica. Elas passam por moderação antes de ficarem disponíveis.</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Upload -->
<div style="padding:18px;border-radius:14px;border:2px dashed rgba(238,184,16,.30);background:rgba(253,229,159,.06);margin-bottom:16px;">
    <form method="post" action="/portal/uploads" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <div style="text-align:center;margin-bottom:12px;">
            <div style="font-size:32px;">📸</div>
            <div style="font-weight:700;font-size:14px;color:rgba(31,41,55,.80);margin-top:4px;">Selecione uma foto</div>
        </div>
        <div style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;justify-content:center;">
            <div class="lc-field" style="min-width:200px;">
                <input class="lc-input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required />
            </div>
            <div class="lc-field" style="min-width:120px;">
                <label class="lc-label">Tipo</label>
                <select class="lc-select" name="kind">
                    <option value="other">Outro</option>
                    <option value="before">Antes</option>
                    <option value="after">Depois</option>
                </select>
            </div>
            <div class="lc-field" style="min-width:180px;">
                <label class="lc-label">Observação</label>
                <input class="lc-input" type="text" name="note" placeholder="Opcional..." />
            </div>
            <div style="padding-bottom:1px;">
                <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Enviar</button>
            </div>
        </div>
        <input type="hidden" name="taken_at" value="" />
    </form>
</div>

<!-- Lista -->
<?php if (!is_array($uploads) || $uploads === []): ?>
    <div style="text-align:center;padding:30px 20px;color:rgba(31,41,55,.40);font-size:13px;">Nenhuma foto enviada ainda.</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:8px;">
    <?php foreach ($uploads as $u): ?>
        <?php
        $st = (string)($u['status'] ?? 'pending');
        $stLbl = $statusLabel[$st] ?? $st;
        $stClr = $statusColor[$st] ?? '#6b7280';
        $kLbl = $kindLabel[(string)($u['kind'] ?? '')] ?? (string)($u['kind'] ?? '');
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="font-size:18px;">🖼️</span>
                <div>
                    <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($kLbl, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if (!empty($u['note'])): ?>
                        <div style="font-size:12px;color:rgba(31,41,55,.50);"><?= htmlspecialchars((string)$u['note'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <div style="font-size:11px;color:rgba(31,41,55,.35);"><?= htmlspecialchars((string)($u['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
            <span style="display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $stClr ?>18;color:<?= $stClr ?>;border:1px solid <?= $stClr ?>30"><?= htmlspecialchars($stLbl, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'uploads';
require __DIR__ . '/_shell.php';
