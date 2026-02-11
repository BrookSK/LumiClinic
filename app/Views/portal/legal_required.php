<?php
$title = 'Termos obrigatórios';
$csrf = $_SESSION['_csrf'] ?? '';
$pending = $pending ?? [];
$error = $error ?? ($_GET['error'] ?? null);

ob_start();
?>
<div class="lc-card" style="margin-top:16px; padding:16px;">
    <div class="lc-card__title">Antes de continuar</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger" style="margin-top:10px;">
            <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="lc-alert lc-alert--info" style="margin-top:10px;">
        Para usar o portal, você precisa aceitar os termos obrigatórios.
    </div>

    <?php if (!$pending): ?>
        <div class="lc-alert lc-alert--success" style="margin-top:12px;">
            Tudo certo! Você já aceitou os termos obrigatórios.
        </div>
        <div style="margin-top:12px;">
            <a class="lc-btn lc-btn--primary" href="/portal">Continuar</a>
        </div>
    <?php else: ?>
        <div class="lc-tablewrap" style="margin-top:12px;">
            <table class="lc-table">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th style="width:260px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pending as $d): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string)($d['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                        </td>
                        <td>
                            <a class="lc-btn lc-btn--secondary" href="/portal/legal/read?id=<?= (int)($d['id'] ?? 0) ?>" target="_blank">Ler</a>

                            <form method="post" action="/portal/legal/accept" style="display:inline-block; margin-left:6px;">
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

        <div class="lc-alert lc-alert--danger" style="margin-top:12px;">
            Você não pode navegar no portal enquanto houver termos obrigatórios pendentes.
        </div>
    <?php endif; ?>
</div>
<?php
$portal_content = (string)ob_get_clean();
$portal_active = null;
require __DIR__ . '/_shell.php';
