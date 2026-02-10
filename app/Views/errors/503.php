<?php
$title = 'Sistema indisponível';
$csrf = $_SESSION['_csrf'] ?? '';
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Sistema indisponível</div>
    <div class="lc-muted" style="line-height:1.55;">
        Estamos com instabilidade no momento.
        <br />
        Aguarde um pouco e tente novamente.
    </div>

    <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
        <a class="lc-btn lc-btn--primary" href="/">Tentar novamente</a>
        <form method="post" action="/logout" style="margin:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <button class="lc-btn lc-btn--secondary" type="submit">Sair</button>
        </form>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
