<?php
/** @var list<array<string,mixed>> $rows */
/** @var list<array<string,mixed>> $campaigns */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Automação de Marketing - Logs';

$rows = $rows ?? [];
$campaigns = $campaigns ?? [];

$status = $status ?? '';
$campaign_id = isset($campaign_id) ? (int)$campaign_id : 0;
$q = $q ?? '';

$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div>
        <div class="lc-badge lc-badge--primary">Logs</div>
        <div class="lc-muted" style="margin-top:6px;">Automação de marketing</div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/marketing/automation/segments">Segmentos</a>
        <a class="lc-btn lc-btn--secondary" href="/marketing/automation/campaigns">Campanhas</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Filtros</div>
    <div class="lc-card__body">
        <form method="get" action="/marketing/automation/logs" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 200px 1fr 240px 160px; align-items:end;">
            <div class="lc-field">
                <label class="lc-label">Status</label>
                <select class="lc-select" name="status">
                    <option value="" <?= $status === '' ? 'selected' : '' ?>>(todos)</option>
                    <?php foreach (['queued','processing','sent','failed','delivered','read','clicked'] as $st): ?>
                        <option value="<?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?>" <?= $status === $st ? 'selected' : '' ?>><?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Busca (paciente)</label>
                <input class="lc-input" type="text" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome, e-mail ou telefone" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Campanha</label>
                <select class="lc-select" name="campaign_id">
                    <option value="" <?= $campaign_id === 0 ? 'selected' : '' ?>>(todas)</option>
                    <?php foreach ($campaigns as $c): ?>
                        <?php $cid = (int)($c['id'] ?? 0); if ($cid <= 0) continue; ?>
                        <option value="<?= $cid ?>" <?= $campaign_id === $cid ? 'selected' : '' ?>><?= htmlspecialchars((string)($c['name'] ?? ('Campanha #' . $cid)), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="lc-btn lc-btn--secondary" type="submit">Filtrar</button>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Resultados</div>
    <div class="lc-card__body">
        <?php if ($rows === []): ?>
            <div class="lc-muted">Sem logs.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Campanha</th>
                    <th>Paciente</th>
                    <th>Canal</th>
                    <th>Status</th>
                    <th>Erro</th>
                    <th>Criado</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php $id = (int)($r['id'] ?? 0); if ($id <= 0) continue; ?>
                    <tr>
                        <td><?= $id ?></td>
                        <td><?= htmlspecialchars((string)($r['campaign_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?= htmlspecialchars((string)($r['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            <div class="lc-muted" style="font-size:12px;">
                                <?= htmlspecialchars((string)($r['patient_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <?= htmlspecialchars((string)($r['patient_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars((string)($r['channel'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['error_message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align:right;">
                            <form method="post" action="/marketing/automation/log/retry" onsubmit="return confirm('Reenfileirar envio?');">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="id" value="<?= $id ?>" />
                                <button class="lc-btn lc-btn--secondary" type="submit">Tentar novamente</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
