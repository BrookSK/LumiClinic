<?php
/** @var list<array<string,mixed>> $rows */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Automação de Marketing - Segmentos';

$rows = $rows ?? [];
$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div>
        <div class="lc-badge lc-badge--primary">Segmentos</div>
        <div class="lc-muted" style="margin-top:6px;">Automação de marketing</div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/marketing/automation/campaigns">Campanhas</a>
        <a class="lc-btn lc-btn--secondary" href="/marketing/automation/logs">Logs</a>
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
    <div class="lc-card__header">Novo segmento</div>
    <div class="lc-card__body">
        <form method="post" action="/marketing/automation/segment/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 1fr 140px 1fr; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Nome</label>
                <input class="lc-input" type="text" name="name" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Status</label>
                <select class="lc-select" name="status">
                    <option value="active">Ativo</option>
                    <option value="disabled">Desativado</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Regras (MVP)</label>
                <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:6px;">
                    <label class="lc-checkbox"><input type="checkbox" name="rule_whatsapp_opt_in" value="1" checked /> WhatsApp opt-in</label>
                    <label class="lc-checkbox"><input type="checkbox" name="rule_has_phone" value="1" checked /> Tem telefone</label>
                    <label class="lc-checkbox"><input type="checkbox" name="rule_has_email" value="1" /> Tem e-mail</label>
                </div>
                <input type="hidden" name="rule_status" value="active" />
            </div>

            <button class="lc-btn lc-btn--secondary" type="submit">Criar</button>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Lista</div>
    <div class="lc-card__body">
        <?php if ($rows === []): ?>
            <div class="lc-muted">Nenhum segmento ainda.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Status</th>
                    <th>Criado em</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php $id = (int)($r['id'] ?? 0); if ($id <= 0) continue; ?>
                    <tr>
                        <td><?= $id ?></td>
                        <td><?= htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align:right;">
                            <a class="lc-btn lc-btn--secondary" href="/marketing/automation/segment/edit?id=<?= $id ?>">Editar</a>
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
