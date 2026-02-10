<?php
$title = 'Métricas';
$csrf = $_SESSION['_csrf'] ?? '';
$summary = $summary ?? ['portal_logins' => 0, 'appointment_confirms' => 0];
ob_start();
?>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Resumo</div>
        <div class="lc-card__body">
            <div>Logins no portal: <?= (int)($summary['portal_logins'] ?? 0) ?></div>
            <div>Confirmações de consulta: <?= (int)($summary['appointment_confirms'] ?? 0) ?></div>
        </div>
    </div>

<?php
$portal_content = (string)ob_get_clean();
$portal_active = null;
require __DIR__ . '/_shell.php';
