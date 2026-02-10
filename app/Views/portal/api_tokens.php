<?php
$title = 'API Tokens';
$csrf = $_SESSION['_csrf'] ?? '';
$tokens = $tokens ?? [];
$created_token = $created_token ?? null;
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
            <h1 class="lc-page__title">API Tokens</h1>
            <div class="lc-page__subtitle">Estrutura para App Mobile</div>
        </div>
        <div class="lc-flex lc-gap-sm">
            <a class="lc-btn lc-btn--secondary" href="/portal">Dashboard</a>
            <form method="post" action="/portal/logout">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Sair</button>
            </form>
        </div>
    </div>

    <?php if (is_string($created_token) && $created_token !== ''): ?>
        <div class="lc-alert lc-alert--info" style="margin-top:12px;">
            Token (mostrado uma única vez): <?= htmlspecialchars($created_token, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Criar token</div>
        <div class="lc-card__body">
            <form method="post" action="/portal/api-tokens/create" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <label class="lc-label">Nome (opcional)</label>
                <input class="lc-input" type="text" name="name" />
                <button class="lc-btn lc-btn--primary" type="submit">Gerar</button>
            </form>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Tokens</div>
        <div class="lc-card__body">
            <?php if (!is_array($tokens) || $tokens === []): ?>
                <div>Nenhum token.</div>
            <?php else: ?>
                <div class="lc-table-wrap">
                    <table class="lc-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Criado em</th>
                            <th>Revogado em</th>
                            <th>Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tokens as $t): ?>
                            <tr>
                                <td><?= (int)($t['id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string)($t['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($t['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($t['revoked_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if (($t['revoked_at'] ?? null) === null): ?>
                                        <form method="post" action="/portal/api-tokens/revoke">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)($t['id'] ?? 0) ?>" />
                                            <button class="lc-btn lc-btn--danger" type="submit">Revogar</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="lc-badge lc-badge--gray">Revogado</span>
                                    <?php endif; ?>
                                </td>
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
