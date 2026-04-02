<?php
/** @var list<array<string,mixed>> $rows */
/** @var list<array<string,mixed>> $campaigns */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Automação - Logs de Envio';

$rows = $rows ?? [];
$campaigns = $campaigns ?? [];

$status = $status ?? '';
$campaign_id = isset($campaign_id) ? (int)$campaign_id : 0;
$q = $q ?? '';

$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) return false;
    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) return false;
        return in_array($permissionCode, $permissions['allow'], true);
    }
    return in_array($permissionCode, $permissions, true);
};

$statusLabel = [
    'queued'=>['label'=>'Na fila','color'=>'#6b7280'],
    'processing'=>['label'=>'Processando','color'=>'#eeb810'],
    'sent'=>['label'=>'Enviado','color'=>'#16a34a'],
    'failed'=>['label'=>'Falhou','color'=>'#b91c1c'],
    'delivered'=>['label'=>'Entregue','color'=>'#16a34a'],
    'read'=>['label'=>'Lido','color'=>'#815901'],
    'clicked'=>['label'=>'Clicou','color'=>'#815901'],
    'skipped'=>['label'=>'Ignorado','color'=>'#6b7280'],
    'cancelled'=>['label'=>'Cancelado','color'=>'#6b7280'],
];

ob_start();
?>

<style>
.ma-nav{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px}
.ma-nav a{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:12px;font-weight:700;font-size:13px;text-decoration:none;border:1px solid rgba(17,24,39,.10);color:rgba(31,41,55,.72);background:var(--lc-surface-3);transition:all 160ms ease}
.ma-nav a:hover{border-color:rgba(129,89,1,.22);color:rgba(129,89,1,1);background:rgba(238,184,16,.06)}
.ma-nav a.active{background:rgba(238,184,16,.14);border-color:rgba(129,89,1,.24);color:rgba(31,41,55,.96)}
.mal-filters{padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px}
.mal-badge{display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.3px;text-transform:uppercase}
.mal-empty{text-align:center;padding:40px 20px;color:rgba(31,41,55,.50)}
.mal-empty__icon{font-size:32px;margin-bottom:8px}
</style>

<!-- Navegação -->
<div class="ma-nav">
    <a href="/marketing/automation/segments">Segmentos</a>
    <a href="/marketing/automation/campaigns">Campanhas</a>
    <a href="/marketing/automation/logs" class="active">Logs de envio</a>
</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Explicação -->
<div style="margin-bottom:16px;">
    <div style="font-weight:850;font-size:18px;color:rgba(31,41,55,.96);">Logs de envio</div>
    <div style="font-size:13px;color:rgba(31,41,55,.55);margin-top:4px;">Acompanhe o status de cada mensagem enviada pelas campanhas. Veja quem recebeu, leu e clicou.</div>
</div>

<!-- Filtros -->
<div class="mal-filters">
    <form method="get" action="/marketing/automation/logs" style="display:grid;grid-template-columns:180px 1fr 220px auto;gap:12px;align-items:end;">
        <div class="lc-field">
            <label class="lc-label">Status</label>
            <select class="lc-select" name="status">
                <option value="">Todos</option>
                <?php foreach ($statusLabel as $k=>$v): ?>
                    <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $status === $k ? 'selected' : '' ?>><?= htmlspecialchars($v['label'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="lc-field">
            <label class="lc-label">Buscar paciente</label>
            <input class="lc-input" type="text" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome, e-mail ou telefone..." />
        </div>
        <div class="lc-field">
            <label class="lc-label">Campanha</label>
            <select class="lc-select" name="campaign_id">
                <option value="">Todas</option>
                <?php foreach ($campaigns as $c): ?>
                    <?php $cid = (int)($c['id'] ?? 0); if ($cid <= 0) continue; ?>
                    <option value="<?= $cid ?>" <?= $campaign_id === $cid ? 'selected' : '' ?>><?= htmlspecialchars((string)($c['name'] ?? ('Campanha #' . $cid)), ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="lc-btn lc-btn--secondary" type="submit">Filtrar</button>
    </form>
</div>

<!-- Resultados -->
<?php if ($rows === []): ?>
    <div class="mal-empty">
        <div class="mal-empty__icon">📭</div>
        <div>Nenhum log encontrado<?= ($status !== '' || $q !== '' || $campaign_id > 0) ? ' com esses filtros' : '' ?>.</div>
    </div>
<?php else: ?>
<div style="border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);overflow:hidden;">
    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>Paciente</th>
                <th>Campanha</th>
                <th>Canal</th>
                <th>Status</th>
                <th>Erro</th>
                <th>Data</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <?php
                $rid = (int)($r['id'] ?? 0);
                if ($rid <= 0) continue;
                $st = (string)($r['status'] ?? 'queued');
                $stInfo = $statusLabel[$st] ?? ['label'=>$st,'color'=>'#6b7280'];
                $ch = (string)($r['channel'] ?? '');
                ?>
                <tr>
                    <td>
                        <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars((string)($r['patient_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div style="font-size:11px;color:rgba(31,41,55,.50);">
                            <?= htmlspecialchars(trim((string)($r['patient_phone'] ?? '') . ' ' . (string)($r['patient_email'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </td>
                    <td style="font-size:13px;"><?= htmlspecialchars((string)($r['campaign_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size:13px;"><?= $ch === 'whatsapp' ? 'WhatsApp' : ($ch === 'email' ? 'E-mail' : htmlspecialchars($ch, ENT_QUOTES, 'UTF-8')) ?></td>
                    <td>
                        <span class="mal-badge" style="background:<?= $stInfo['color'] ?>18;color:<?= $stInfo['color'] ?>;border:1px solid <?= $stInfo['color'] ?>30"><?= htmlspecialchars($stInfo['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td style="font-size:12px;color:rgba(185,28,28,.80);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars((string)($r['error_message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string)($r['error_message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td style="font-size:12px;color:rgba(31,41,55,.55);white-space:nowrap;">
                        <?php
                        $dt = (string)($r['created_at'] ?? '');
                        echo $dt !== '' ? htmlspecialchars(date('d/m/Y H:i', strtotime($dt)), ENT_QUOTES, 'UTF-8') : '—';
                        ?>
                    </td>
                    <td style="text-align:right;">
                        <?php if ($can('marketing.automation.manage') && in_array($st, ['failed','queued'], true)): ?>
                            <form method="post" action="/marketing/automation/log/retry" style="display:inline;" onsubmit="return confirm('Reenfileirar envio?');">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="id" value="<?= $rid ?>" />
                                <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Reenviar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
