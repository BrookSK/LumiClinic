<?php
$title = 'Acesso negado';
$csrf = $_SESSION['_csrf'] ?? '';
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Acesso negado</div>
    <div style="color: rgba(244,236,212,0.78); line-height:1.55;">
        Você não tem permissão para acessar esta área.
        <br />
        Se você acredita que isso é um erro, fale com o administrador da clínica.
    </div>

    <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="lc-btn lc-btn--primary" href="/">Voltar ao dashboard</a>
        <form method="post" action="/logout" style="margin:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <button class="lc-btn lc-btn--secondary" type="submit">Sair</button>
        </form>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
