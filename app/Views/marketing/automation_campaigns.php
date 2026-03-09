<?php
/** @var list<array<string,mixed>> $rows */
/** @var list<array<string,mixed>> $segments */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Automação de Marketing - Campanhas';

$rows = $rows ?? [];
$segments = $segments ?? [];

$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
        return true;
    }

    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) {
        return false;
    }

    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) {
            return false;
        }
        return in_array($permissionCode, $permissions['allow'], true);
    }

    return in_array($permissionCode, $permissions, true);
};

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div>
        <div class="lc-badge lc-badge--primary">Campanhas</div>
        <div class="lc-muted" style="margin-top:6px;">Automação de marketing</div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <?php if ($can('marketing.automation.read')): ?>
            <a class="lc-btn lc-btn--secondary" href="/marketing/automation/segments">Segmentos</a>
            <a class="lc-btn lc-btn--secondary" href="/marketing/automation/logs">Logs</a>
        <?php endif; ?>
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

<?php if ($can('marketing.automation.manage')): ?>
    <div class="lc-card" style="margin-bottom: 16px;">
        <div class="lc-card__header">Nova campanha</div>
        <div class="lc-card__body">
            <form method="post" action="/marketing/automation/campaign/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 1fr 160px 1fr 160px; align-items:end;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Nome</label>
                <input class="lc-input" type="text" name="name" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Canal</label>
                <select class="lc-select" name="channel">
                    <option value="whatsapp">WhatsApp</option>
                    <option value="email">E-mail</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Segmento (opcional)</label>
                <select class="lc-select" name="segment_id">
                    <option value="">(todos)</option>
                    <?php foreach ($segments as $s): ?>
                        <?php $sid = (int)($s['id'] ?? 0); if ($sid <= 0) continue; ?>
                        <option value="<?= $sid ?>"><?= htmlspecialchars((string)($s['name'] ?? ('Segmento #' . $sid)), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Status</label>
                <select class="lc-select" name="status">
                    <option value="draft">Rascunho</option>
                    <option value="scheduled">Agendada</option>
                    <option value="running">Rodando</option>
                    <option value="paused">Pausada</option>
                </select>
            </div>

            <div class="lc-field" style="grid-column:1 / -1;">
                <label class="lc-label">Agendar para (opcional)</label>
                <input class="lc-input" type="datetime-local" name="scheduled_for" />
                <div class="lc-muted" style="margin-top:6px;">Formato local do navegador. Se vazio, roda quando você clicar em "Rodar agora".</div>
            </div>

            <div class="lc-field" style="grid-column:1 / -1;">
                <label class="lc-label">Trigger por evento (opcional)</label>
                <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 200px; align-items:end;">
                    <div>
                        <select class="lc-select" name="trigger_event">
                            <option value="">(nenhum)</option>
                            <option value="appointment.completed">Agendamento concluído</option>
                            <option value="appointment.no_show">Faltou (no-show)</option>
                            <option value="appointment.cancelled">Cancelado</option>
                            <option value="appointment.confirmed">Confirmado</option>
                        </select>
                    </div>
                    <div>
                        <label class="lc-label">Delay (min)</label>
                        <input class="lc-input" type="number" name="trigger_delay_minutes" value="0" min="0" />
                    </div>
                </div>
            </div>

            <div class="lc-field" style="grid-column:1 / -1;">
                <label class="lc-label">URL de clique (opcional)</label>
                <input class="lc-input" type="url" name="click_url" placeholder="https://..." />
            </div>

                <button class="lc-btn lc-btn--secondary" type="submit">Criar</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__header">Lista</div>
    <div class="lc-card__body">
        <?php if ($rows === []): ?>
            <div class="lc-muted">Nenhuma campanha ainda.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Canal</th>
                    <th>Status</th>
                    <th>Agendada</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php $id = (int)($r['id'] ?? 0); if ($id <= 0) continue; ?>
                    <tr>
                        <td><?= $id ?></td>
                        <td><?= htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['channel'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['scheduled_for'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align:right;">
                            <?php if ($can('marketing.automation.manage')): ?>
                                <a class="lc-btn lc-btn--secondary" href="/marketing/automation/campaign/edit?id=<?= $id ?>">Editar</a>
                            <?php endif; ?>
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
