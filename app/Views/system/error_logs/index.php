<?php
$title = 'Admin - Logs de Erros';
$items = $items ?? [];
$status = $status ?? null;
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 50;
$hasNext = isset($has_next) ? (bool)$has_next : false;

$selected = $status !== null ? (string)$status : '';
$statusInfo = [
    '402'=>['label'=>'402 Assinatura','color'=>'#b5841e'],
    '403'=>['label'=>'403 Acesso negado','color'=>'#6b7280'],
    '404'=>['label'=>'404 Não encontrado','color'=>'#6b7280'],
    '500'=>['label'=>'500 Erro interno','color'=>'#b91c1c'],
    '503'=>['label'=>'503 Indisponível','color'=>'#b91c1c'],
];

ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Logs de erros</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Erros HTTP registrados pelo sistema (402, 403, 404, 500, 503).</div>
    </div>
    <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/settings/dev-alerts">Configurar alertas</a>
</div>

<!-- Filtro -->
<div style="padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <form method="get" action="/sys/error-logs" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
        <div class="lc-field" style="min-width:220px;">
            <label class="lc-label">Tipo de erro</label>
            <select class="lc-select" name="status">
                <option value="" <?= $selected === '' ? 'selected' : '' ?>>Todos</option>
                <?php foreach ($statusInfo as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= $selected === $k ? 'selected' : '' ?>><?= htmlspecialchars($v['label'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex;gap:8px;padding-bottom:1px;">
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Filtrar</button>
            <?php if ($selected !== ''): ?><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/error-logs">Limpar</a><?php endif; ?>
        </div>
    </form>
</div>

<!-- Lista -->
<?php if ($items === []): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
        <div style="font-size:32px;margin-bottom:8px;">✅</div>
        <div style="font-size:14px;">Nenhum erro registrado<?= $selected !== '' ? ' com esse filtro' : '' ?>.</div>
    </div>
<?php else: ?>
<div style="border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);overflow:hidden;">
    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead><tr><th>Data</th><th>Status</th><th>Rota</th><th>Tipo</th><th>Clínica</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <?php
                $id = (int)($it['id'] ?? 0);
                $st = (string)($it['status_code'] ?? '');
                $si = $statusInfo[$st] ?? ['label'=>$st,'color'=>'#6b7280'];
                $type = trim((string)($it['error_type'] ?? ''));
                $method = trim((string)($it['method'] ?? ''));
                $path = trim((string)($it['path'] ?? ''));
                $clinicId = (int)($it['clinic_id'] ?? 0);
                $createdAt = (string)($it['created_at'] ?? '');
                $createdFmt = $createdAt !== '' ? date('d/m H:i', strtotime($createdAt)) : '—';
                ?>
                <tr>
                    <td style="font-size:12px;white-space:nowrap;color:rgba(31,41,55,.55);"><?= htmlspecialchars($createdFmt, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $si['color'] ?>18;color:<?= $si['color'] ?>;border:1px solid <?= $si['color'] ?>30"><?= htmlspecialchars($si['label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td style="font-size:12px;font-family:monospace;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars(trim($method . ' ' . $path), ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size:12px;color:rgba(31,41,55,.55);"><?= htmlspecialchars($type !== '' ? $type : '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size:12px;"><?= $clinicId > 0 ? '#' . $clinicId : '—' ?></td>
                    <td style="text-align:right;"><?php if ($id > 0): ?><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/error-logs/view?id=<?= $id ?>">Ver</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;flex-wrap:wrap;gap:10px;">
    <span style="font-size:12px;color:rgba(31,41,55,.40);">Página <?= (int)$page ?></span>
    <div style="display:flex;gap:8px;">
        <?php $qs = $selected !== '' ? '&status=' . urlencode($selected) : ''; ?>
        <?php if ($page > 1): ?><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/error-logs?page=<?= $page - 1 ?><?= $qs ?>">← Anterior</a><?php endif; ?>
        <?php if ($hasNext): ?><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/error-logs?page=<?= $page + 1 ?><?= $qs ?>">Próxima →</a><?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
