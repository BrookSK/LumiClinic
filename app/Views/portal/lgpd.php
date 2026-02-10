<?php
$title = 'LGPD';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$requests = $requests ?? [];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" href="/icone_1.png" />
    <link rel="stylesheet" href="/assets/css/design-system.css" />
</head>
<body class="lc-body">
<div class="lc-app" style="padding: 16px; max-width: 980px; margin: 0 auto;">
    <div class="lc-page__header">
        <div>
            <h1 class="lc-page__title">LGPD</h1>
            <div class="lc-page__subtitle">Solicitações</div>
        </div>
        <div class="lc-flex lc-gap-sm">
            <a class="lc-btn lc-btn--secondary" href="/portal">Dashboard</a>
            <form method="post" action="/portal/logout">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Sair</button>
            </form>
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
</div>
</body>
</html>
