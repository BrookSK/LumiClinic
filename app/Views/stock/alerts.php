<?php
$title = 'Alertas de Estoque';
$days  = (int)($days ?? 30);
$low_stock     = $low_stock ?? [];
$out_of_stock  = $out_of_stock ?? [];
$expiring_soon = $expiring_soon ?? [];
$expired       = $expired ?? [];

$totalAlerts = count($out_of_stock) + count($low_stock) + count($expired) + count($expiring_soon);

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;">Alertas de Estoque</div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px;">
            <?= $totalAlerts ?> alerta<?= $totalAlerts !== 1 ? 's' : '' ?> encontrado<?= $totalAlerts !== 1 ? 's' : '' ?>
        </div>
    </div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/stock/materials">Materiais</a>
        <form method="get" action="/stock/alerts" class="lc-flex lc-gap-sm" style="align-items:center;">
            <div class="lc-field" style="margin:0;">
                <input class="lc-input" type="number" name="days" min="1" max="365" value="<?= $days ?>" style="width:80px;" title="Dias para validade próxima" />
            </div>
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Atualizar</button>
        </form>
    </div>
</div>

<!-- Contadores -->
<div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(4, minmax(0,1fr)); margin-bottom:16px;">
    <div class="lc-card" style="margin:0; border-left:4px solid #b91c1c;">
        <div class="lc-card__body" style="padding:12px;">
            <div class="lc-muted" style="font-size:12px;">Sem estoque</div>
            <div style="font-weight:800; font-size:24px; color:#b91c1c; margin-top:4px;"><?= count($out_of_stock) ?></div>
        </div>
    </div>
    <div class="lc-card" style="margin:0; border-left:4px solid #d97706;">
        <div class="lc-card__body" style="padding:12px;">
            <div class="lc-muted" style="font-size:12px;">Estoque baixo</div>
            <div style="font-weight:800; font-size:24px; color:#d97706; margin-top:4px;"><?= count($low_stock) ?></div>
        </div>
    </div>
    <div class="lc-card" style="margin:0; border-left:4px solid #7c3aed;">
        <div class="lc-card__body" style="padding:12px;">
            <div class="lc-muted" style="font-size:12px;">Vencidos</div>
            <div style="font-weight:800; font-size:24px; color:#7c3aed; margin-top:4px;"><?= count($expired) ?></div>
        </div>
    </div>
    <div class="lc-card" style="margin:0; border-left:4px solid #eeb810;">
        <div class="lc-card__body" style="padding:12px;">
            <div class="lc-muted" style="font-size:12px;">Vencendo em <?= $days ?> dias</div>
            <div style="font-weight:800; font-size:24px; color:#eeb810; margin-top:4px;"><?= count($expiring_soon) ?></div>
        </div>
    </div>
</div>

<?php
function renderAlertTable(array $items, string $emptyMsg, bool $showValidity = false, bool $showSuggestion = false): void {
    if (empty($items)) {
        echo '<div class="lc-muted" style="padding:16px; text-align:center;">' . htmlspecialchars($emptyMsg, ENT_QUOTES, 'UTF-8') . '</div>';
        return;
    }
    echo '<table class="lc-table"><thead><tr><th>Material</th><th>Estoque atual</th>';
    if ($showSuggestion) echo '<th>Mínimo</th><th>Sugestão de compra</th>';
    if ($showValidity) echo '<th>Validade</th>';
    echo '</tr></thead><tbody>';
    foreach ($items as $it) {
        echo '<tr>';
        echo '<td style="font-weight:600;">' . htmlspecialchars((string)($it['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . number_format((float)($it['stock_current'] ?? 0), 2, ',', '.') . ' ' . htmlspecialchars((string)($it['unit'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
        if ($showSuggestion) {
            echo '<td class="lc-muted">' . number_format((float)($it['stock_minimum'] ?? 0), 2, ',', '.') . '</td>';
            echo '<td style="font-weight:700; color:#16a34a;">' . number_format((float)($it['suggested_buy'] ?? 0), 2, ',', '.') . ' ' . htmlspecialchars((string)($it['unit'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
        }
        if ($showValidity) {
            $vd = (string)($it['validity_date'] ?? '');
            $vfmt = '';
            try { $vfmt = $vd !== '' ? (new \DateTimeImmutable($vd))->format('d/m/Y') : '—'; } catch (\Throwable $e) { $vfmt = $vd; }
            echo '<td>' . htmlspecialchars($vfmt, ENT_QUOTES, 'UTF-8') . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
}
?>

<!-- Sem estoque -->
<?php if (!empty($out_of_stock)): ?>
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__header" style="font-weight:700; color:#b91c1c;">🚨 Sem estoque (ruptura)</div>
    <div class="lc-card__body" style="padding:0;">
        <?php renderAlertTable($out_of_stock, '', false, true); ?>
    </div>
</div>
<?php endif; ?>

<!-- Estoque baixo -->
<?php if (!empty($low_stock)): ?>
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__header" style="font-weight:700; color:#d97706;">⚠ Estoque baixo</div>
    <div class="lc-card__body" style="padding:0;">
        <?php renderAlertTable($low_stock, '', false, true); ?>
    </div>
</div>
<?php endif; ?>

<!-- Vencidos -->
<?php if (!empty($expired)): ?>
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__header" style="font-weight:700; color:#7c3aed;">❌ Vencidos</div>
    <div class="lc-card__body" style="padding:0;">
        <?php renderAlertTable($expired, '', true, false); ?>
    </div>
</div>
<?php endif; ?>

<!-- Vencendo em breve -->
<?php if (!empty($expiring_soon)): ?>
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__header" style="font-weight:700; color:#eeb810;">⏰ Vencendo em até <?= $days ?> dias</div>
    <div class="lc-card__body" style="padding:0;">
        <?php renderAlertTable($expiring_soon, '', true, false); ?>
    </div>
</div>
<?php endif; ?>

<?php if ($totalAlerts === 0): ?>
<div class="lc-card">
    <div class="lc-card__body" style="text-align:center; padding:40px 20px;">
        <div style="font-size:36px; margin-bottom:10px;">✅</div>
        <div style="font-weight:700; margin-bottom:6px;">Tudo em ordem</div>
        <div class="lc-muted">Nenhum alerta de estoque no momento.</div>
    </div>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
