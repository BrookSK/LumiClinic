<?php
$title = 'Perfil';
$patient = $patient ?? null;
$clinic = $clinic ?? null;

ob_start();
?>
<div class="lc-card" style="margin-top:16px; padding:16px;">
    <div class="lc-card__title">Seus dados</div>
    <div class="lc-card__body">
        <div class="lc-grid lc-grid--2 lc-gap-grid">
            <div>
                <div class="lc-label">Nome</div>
                <div><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-label">E-mail</div>
                <div><?= htmlspecialchars((string)($patient['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-label">Telefone</div>
                <div><?= htmlspecialchars((string)($patient['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-label">Data de nascimento</div>
                <div><?= htmlspecialchars((string)($patient['birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <div class="lc-alert lc-alert--info" style="margin-top:12px;">
            Para alterar seus dados, entre em contato com a clínica.
        </div>
    </div>
</div>

<div class="lc-card" style="margin-top:16px; padding:16px;">
    <div class="lc-card__title">Clínica</div>
    <div class="lc-card__body">
        <div class="lc-label">Nome</div>
        <div><?= htmlspecialchars((string)($clinic['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
</div>
<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'perfil';
require __DIR__ . '/_shell.php';
