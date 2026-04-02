<?php
$title = 'Assinaturas';
$rows  = $rows ?? [];
$limit = (int)($limit ?? 200);
$scope = (string)($scope ?? 'all');

$scopeLabels = ['patient_portal'=>'Portal','system_user'=>'Equipe','clinic_owner'=>'Owner'];
$scopeColors = ['patient_portal'=>'#eeb810','system_user'=>'#16a34a','clinic_owner'=>'#815901'];

ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Assinaturas</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Registro de todos os aceites e assinaturas de documentos LGPD.</div>
    </div>
    <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/settings/lgpd">Gerenciar documentos</a>
</div>

<!-- Filtro -->
<div style="padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <form method="get" action="/clinic/legal-signatures" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
        <div class="lc-field" style="min-width:200px;">
            <label class="lc-label">Filtrar por tipo</label>
            <select class="lc-select" name="scope">
                <option value="all" <?= $scope === 'all' ? 'selected' : '' ?>>Todos</option>
                <option value="patient_portal" <?= $scope === 'patient_portal' ? 'selected' : '' ?>>Portal do Paciente</option>
                <option value="system_user" <?= $scope === 'system_user' ? 'selected' : '' ?>>Equipe Interna</option>
                <option value="clinic_owner" <?= $scope === 'clinic_owner' ? 'selected' : '' ?>>Owner</option>
            </select>
        </div>
        <div style="padding-bottom:1px;">
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Filtrar</button>
        </div>
    </form>
</div>

<!-- Lista -->
<?php if (empty($rows)): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
        <div style="font-size:32px;margin-bottom:8px;">📝</div>
        <div style="font-size:14px;">Nenhuma assinatura registrada<?= $scope !== 'all' ? ' com esse filtro' : '' ?>.</div>
    </div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:8px;">
    <?php foreach ($rows as $r): ?>
        <?php
        $who = '';
        $whoSub = '';
        if ((int)($r['patient_user_id'] ?? 0) > 0) {
            $who = (string)($r['patient_name'] ?? '');
            $whoSub = (string)($r['patient_user_email'] ?? '');
        } else {
            $who = (string)($r['user_name'] ?? '');
            $whoSub = (string)($r['user_email'] ?? '');
        }
        $scopeRaw = (string)($r['scope'] ?? '');
        $scopeLabel = $scopeLabels[$scopeRaw] ?? $scopeRaw;
        $scopeClr = $scopeColors[$scopeRaw] ?? '#6b7280';
        $hashOk = isset($r['hash_ok']) ? (bool)$r['hash_ok'] : null;
        $signedAt = (string)($r['signed_at'] ?? '');
        $signedFmt = $signedAt;
        try { $signedFmt = (new \DateTimeImmutable($signedAt))->format('d/m/Y H:i'); } catch (\Throwable $e) {}
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 2px 8px rgba(17,24,39,.04);flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:14px;min-width:0;flex-wrap:wrap;">
                <div style="min-width:100px;">
                    <div style="font-size:12px;color:rgba(31,41,55,.45);"><?= htmlspecialchars($signedFmt, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div>
                    <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);"><?= htmlspecialchars((string)($r['document_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    <div style="font-size:11px;color:rgba(31,41,55,.45);">v<?= (int)($r['version_number'] ?? 0) ?></div>
                </div>
                <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:<?= $scopeClr ?>18;color:<?= $scopeClr ?>;border:1px solid <?= $scopeClr ?>30"><?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <div>
                    <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($who, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if ($whoSub !== ''): ?>
                        <div style="font-size:11px;color:rgba(31,41,55,.45);"><?= htmlspecialchars($whoSub, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($hashOk === true): ?>
                    <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:rgba(22,163,74,.12);color:#16a34a;border:1px solid rgba(22,163,74,.22);">✓ Íntegro</span>
                <?php elseif ($hashOk === false): ?>
                    <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:rgba(185,28,28,.08);color:#b91c1c;border:1px solid rgba(185,28,28,.16);">✗ Alterado</span>
                <?php endif; ?>
            </div>
            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/clinic/legal-signatures/view?id=<?= (int)($r['id'] ?? 0) ?>">Ver</a>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
