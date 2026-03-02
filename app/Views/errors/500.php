<?php
$title = 'Algo deu errado';
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Algo deu errado</div>
    <div class="lc-muted" style="line-height:1.55;">
        Ocorreu um erro inesperado ao carregar esta página.
        <br />
        Tente novamente em alguns instantes.
    </div>

    <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
        <a class="lc-btn lc-btn--primary" href="/">Voltar ao início</a>
        <a class="lc-btn lc-btn--secondary" href="javascript:history.back()">Voltar</a>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
