<?php
$title = 'Admin - Nova Clínica';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;

ob_start();
?>

<a href="/sys/clinics" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para clínicas
</a>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:18px;">Nova clínica</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div style="max-width:560px;">
    <form method="post" action="/sys/clinics/create">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="tenant_key" value="" />
        <input type="hidden" name="primary_domain" value="" />

        <!-- Clínica -->
        <div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
            <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:12px;">Dados da clínica</div>
            <div class="lc-field">
                <label class="lc-label">Nome da clínica</label>
                <input class="lc-input" type="text" name="clinic_name" required placeholder="Ex: Clínica Estética Bella" />
            </div>
        </div>

        <!-- Owner -->
        <div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
            <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:4px;">Dono da clínica</div>
            <div style="font-size:12px;color:rgba(31,41,55,.45);margin-bottom:12px;">Esse será o primeiro usuário com acesso total à clínica.</div>

            <div class="lc-field">
                <label class="lc-label">Nome completo</label>
                <input class="lc-input" type="text" name="owner_name" required placeholder="Ex: Dr. João Silva" />
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:4px;">
                <div class="lc-field">
                    <label class="lc-label">E-mail de login</label>
                    <input class="lc-input" type="email" name="owner_email" required placeholder="joao@clinica.com" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Senha</label>
                    <input class="lc-input" type="password" name="owner_password" required />
                </div>
            </div>
        </div>

        <div style="display:flex;gap:10px;">
            <button class="lc-btn lc-btn--primary" type="submit">Criar clínica</button>
            <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Cancelar</a>
        </div>
    </form>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
