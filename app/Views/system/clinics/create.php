<?php
$title = 'Nova clínica';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Cadastro de clínica</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/sys/clinics/create">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Nome da clínica</label>
        <input class="lc-input" type="text" name="clinic_name" required />

        <label class="lc-label">Tenant (subdomínio opcional)</label>
        <input class="lc-input" type="text" name="tenant_key" placeholder="ex: clinica01" />

        <label class="lc-label">Domínio principal (opcional)</label>
        <input class="lc-input" type="text" name="primary_domain" placeholder="ex: clinica01.com.br" />

        <div class="lc-card" style="margin-top:14px;">
            <div class="lc-card__title">Owner inicial</div>

            <label class="lc-label">Nome</label>
            <input class="lc-input" type="text" name="owner_name" required />

            <label class="lc-label">E-mail</label>
            <input class="lc-input" type="email" name="owner_email" required />

            <label class="lc-label">Senha</label>
            <input class="lc-input" type="password" name="owner_password" required />
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Criar</button>
            <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Voltar</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
