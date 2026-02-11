<?php
$title = 'Termos obrigatórios';
$csrf = $_SESSION['_csrf'] ?? '';
$pending = $pending ?? [];
$error = $error ?? ($_GET['error'] ?? null);

ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Antes de continuar</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger" style="margin-top:10px;">
            <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="lc-alert lc-alert--info" style="margin-top:10px;">
        Para usar o sistema, você precisa aceitar os termos obrigatórios.
    </div>

    <?php if (!$pending): ?>
        <div class="lc-alert lc-alert--success" style="margin-top:12px;">Tudo certo.</div>
        <div style="margin-top:12px;"><a class="lc-btn lc-btn--primary" href="/">Continuar</a></div>
    <?php else: ?>
        <div class="lc-tablewrap" style="margin-top:12px;">
            <table class="lc-table">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th style="width:220px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pending as $d): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string)($d['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                            <div style="opacity:.8; margin-top:6px; white-space:pre-wrap;"><?= nl2br(htmlspecialchars((string)($d['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
                        </td>
                        <td>
                            <form method="post" action="/legal/accept">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="id" value="<?= (int)($d['id'] ?? 0) ?>" />
                                <button class="lc-btn lc-btn--primary" type="submit">Aceitar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
