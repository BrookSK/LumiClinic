<?php
$title = 'Logs WhatsApp';
$payload = $items ?? [];
$items = (is_array($payload) && isset($payload['rows']) && is_array($payload['rows'])) ? $payload['rows'] : [];
$hasNext = (is_array($payload) && isset($payload['has_next'])) ? (bool)$payload['has_next'] : false;
$filters = $filters ?? ['status'=>'','template_code'=>'','from'=>'','to'=>'','appointment_id'=>'','patient_id'=>''];
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 50;
$csrf = $_SESSION['_csrf'] ?? '';

$can = function (string $p): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $perms = $_SESSION['permissions'] ?? [];
    if (!is_array($perms)) return false;
    if (isset($perms['allow'],$perms['deny'])&&is_array($perms['allow'])&&is_array($perms['deny'])) {
        if (in_array($p,$perms['deny'],true)) return false;
        return in_array($p,$perms['allow'],true);
    }
    return in_array($p,$perms,true);
};

$reconcileOk = isset($_GET['reconcile']) && (string)$_GET['reconcile'] !== '';

$statusInfo = [
    'pending'=>['label'=>'Pendente','color'=>'#6b7280'],
    'processing'=>['label'=>'Enviando','color'=>'#eeb810'],
    'sent'=>['label'=>'Enviado','color'=>'#16a34a'],
    'failed'=>['label'=>'Falhou','color'=>'#b91c1c'],
    'skipped'=>['label'=>'Ignorado','color'=>'#6b7280'],
    'cancelled'=>['label'=>'Cancelado','color'=>'#6b7280'],
];

$q = function (int $pg) use ($filters, $perPage): string {
    $p = [];
    foreach ($filters as $k=>$v) $p[] = urlencode($k).'='.urlencode((string)$v);
    $p[] = 'per_page='.(int)$perPage;
    $p[] = 'page='.$pg;
    return implode('&', $p);
};

ob_start();
?>

<a href="/settings" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para configurações
</a>

<?php if ($reconcileOk): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;">Verificação enfileirada. Os status serão atualizados em instantes.</div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Logs de WhatsApp</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Histórico de todas as mensagens enviadas pelo sistema via WhatsApp.</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php if ($can('settings.update')): ?>
            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/settings/whatsapp">Configuração WhatsApp</a>
            <form method="post" action="/whatsapp-logs/force-reconcile" style="margin:0;display:inline;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Verificar status</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Filtros -->
<div style="padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <form method="get" action="/whatsapp-logs" style="display:grid;grid-template-columns:160px 1fr 160px 160px auto;gap:12px;align-items:end;">
        <input type="hidden" name="per_page" value="<?= (int)$perPage ?>" />
        <input type="hidden" name="page" value="1" />
        <div class="lc-field">
            <label class="lc-label">Status</label>
            <select class="lc-select" name="status">
                <option value="">Todos</option>
                <?php foreach ($statusInfo as $k=>$v): ?>
                    <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['status'] ?? '') === $k ? 'selected' : '' ?>><?= htmlspecialchars($v['label'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="lc-field">
            <label class="lc-label">Template</label>
            <input class="lc-input" type="text" name="template_code" value="<?= htmlspecialchars((string)($filters['template_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="ex: reminder_24h" />
        </div>
        <div class="lc-field">
            <label class="lc-label">De</label>
            <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars((string)($filters['from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
        </div>
        <div class="lc-field">
            <label class="lc-label">Até</label>
            <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars((string)($filters['to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
        </div>
        <div style="display:flex;gap:8px;">
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Filtrar</button>
            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/whatsapp-logs">Limpar</a>
        </div>
        <input type="hidden" name="appointment_id" value="<?= htmlspecialchars((string)($filters['appointment_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="patient_id" value="<?= htmlspecialchars((string)($filters['patient_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
    </form>
</div>

<!-- Resultados -->
<?php if ($items === []): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
        <div style="font-size:32px;margin-bottom:8px;">📭</div>
        <div style="font-size:14px;">Nenhuma mensagem encontrada<?= ($filters['status'] ?? '') !== '' || ($filters['template_code'] ?? '') !== '' ? ' com esses filtros' : '' ?>.</div>
    </div>
<?php else: ?>
<div style="border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);overflow:hidden;">
    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead><tr><th>Paciente</th><th>Template</th><th>Status</th><th>Agendado</th><th>Enviado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <?php
                $stRow = (string)($it['status'] ?? '');
                $si = $statusInfo[$stRow] ?? ['label'=>$stRow,'color'=>'#6b7280'];
                ?>
                <tr>
                    <td>
                        <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars((string)($it['patient_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if (!empty($it['patient_id'])): ?>
                            <div style="font-size:11px;color:rgba(31,41,55,.40);">#<?= (int)$it['patient_id'] ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;"><code><?= htmlspecialchars((string)($it['template_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                    <td>
                        <span style="display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.3px;text-transform:uppercase;background:<?= $si['color'] ?>18;color:<?= $si['color'] ?>;border:1px solid <?= $si['color'] ?>30"><?= htmlspecialchars($si['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td style="font-size:12px;color:rgba(31,41,55,.55);white-space:nowrap;"><?= htmlspecialchars((string)($it['scheduled_for'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size:12px;color:rgba(31,41,55,.55);white-space:nowrap;"><?= htmlspecialchars((string)($it['sent_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align:right;white-space:nowrap;">
                        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/whatsapp-logs/show?id=<?= (int)$it['id'] ?>">Ver</a>
                        <?php if ($can('settings.update') && in_array($stRow, ['failed','pending'], true)): ?>
                            <form method="post" action="/whatsapp-logs/retry-send" style="display:inline;margin:0;">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
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

<div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;flex-wrap:wrap;gap:10px;">
    <span style="font-size:12px;color:rgba(31,41,55,.45);">Página <?= (int)$page ?></span>
    <div style="display:flex;gap:8px;">
        <?php if ($page > 1): ?><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/whatsapp-logs?<?= $q($page - 1) ?>">← Anterior</a><?php endif; ?>
        <?php if ($hasNext): ?><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/whatsapp-logs?<?= $q($page + 1) ?>">Próxima →</a><?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
