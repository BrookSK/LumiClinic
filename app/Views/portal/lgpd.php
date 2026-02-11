<?php
$title = 'LGPD (Solicitações)';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$requests = $requests ?? [];
ob_start();
?>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Sobre esta página</div>
        <div class="lc-card__body">
            <div>
                Aqui você pode criar <strong>solicitações LGPD</strong> (ex.: exportar dados, solicitar exclusão).
                <br />
                <strong>Termos e políticas obrigatórias</strong> são solicitados separadamente (pop-up de aceite) quando necessário.
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger" style="margin-top:12px;">
            <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Nova solicitação</div>
        <div class="lc-card__body">
            <form method="post" action="/portal/lgpd" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />

                <label class="lc-label">Tipo</label>
                <select class="lc-select" name="type" required>
                    <option value="export">Exportar dados</option>
                    <option value="delete">Solicitar exclusão</option>
                    <option value="revoke_consent">Revogar consentimento</option>
                </select>

                <label class="lc-label">Observação (opcional)</label>
                <input class="lc-input" type="text" name="note" />

                <button class="lc-btn lc-btn--primary" type="submit">Enviar</button>
            </form>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Histórico</div>
        <div class="lc-card__body">
            <?php if (!is_array($requests) || $requests === []): ?>
                <div>Nenhuma solicitação.</div>
            <?php else: ?>
                <div class="lc-table-wrap">
                    <table class="lc-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Criado em</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td><?= (int)($r['id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string)($r['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($r['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($r['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'lgpd';
require __DIR__ . '/_shell.php';
