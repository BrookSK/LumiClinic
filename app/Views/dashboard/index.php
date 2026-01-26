<?php
$title = 'Dashboard';
ob_start();
?>
<div class="lc-grid">
    <div class="lc-card">
        <div class="lc-card__title">Bem-vindo</div>
        <div class="lc-card__body">Base administrativa inicial pronta para evoluir por módulos.</div>
    </div>

    <div class="lc-card">
        <div class="lc-card__title">Status</div>
        <div class="lc-card__body">
            <div class="lc-badge lc-badge--gold">FASE 11 (finalizado)</div>
        </div>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
