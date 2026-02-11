<?php
$title = 'API Tokens';
$csrf = $_SESSION['_csrf'] ?? '';
$tokens = $tokens ?? [];
$created_token = $created_token ?? null;
ob_start();
?>

    <?php if (is_string($created_token) && $created_token !== ''): ?>
        <div class="lc-alert lc-alert--info" style="margin-top:12px;">
            Token (mostrado uma única vez): <?= htmlspecialchars($created_token, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Como usar seu token</div>
        <div class="lc-card__body">
            <div class="lc-muted" style="margin-bottom:10px; line-height:1.5;">
                Aprenda para que serve o token, boas práticas de segurança e exemplos prontos de requisições (cURL/JavaScript/PHP).
            </div>
            <a class="lc-btn lc-btn--secondary" href="/tutorial/api-tokens/paciente" target="_blank">Abrir tutorial</a>
        </div>
    </div>

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

<?php
$portal_content = (string)ob_get_clean();
$portal_active = null;
require __DIR__ . '/_shell.php';
