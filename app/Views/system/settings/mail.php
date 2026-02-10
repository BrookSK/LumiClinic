<?php
$title = 'Admin do Sistema';
$csrf = $_SESSION['_csrf'] ?? '';

$smtp_host = isset($smtp_host) ? (string)$smtp_host : '';
$smtp_port = isset($smtp_port) ? (string)$smtp_port : '';
$smtp_username = isset($smtp_username) ? (string)$smtp_username : '';
$smtp_encryption = isset($smtp_encryption) ? (string)$smtp_encryption : '';
$from_address = isset($from_address) ? (string)$from_address : '';
$from_name = isset($from_name) ? (string)$from_name : '';
$smtp_password_set = isset($smtp_password_set) ? (string)$smtp_password_set : '';

$test_status = isset($test_status) ? (string)$test_status : '';
$test_message = isset($test_message) ? (string)$test_message : '';

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Configurações (E-mail)</div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/sys/settings/billing">Billing</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Clínicas</a>
    </div>
</div>

<?php if ($test_status !== '' && $test_message !== ''): ?>
    <div class="lc-alert <?= $test_status === 'ok' ? 'lc-alert--success' : 'lc-alert--danger' ?>">
        <?= htmlspecialchars($test_message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card lc-card--soft" style="margin-bottom:16px;">
    <div class="lc-card__header">
        <div class="lc-card__title">SMTP</div>
    </div>
    <div class="lc-card__body">
        <form method="post" action="/sys/settings/mail" class="lc-form lc-grid lc-grid--2 lc-gap-grid">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Host</label>
                <input class="lc-input" type="text" name="smtp_host" value="<?= htmlspecialchars($smtp_host, ENT_QUOTES, 'UTF-8') ?>" placeholder="smtp.seudominio.com" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Porta</label>
                <input class="lc-input" type="text" name="smtp_port" value="<?= htmlspecialchars($smtp_port, ENT_QUOTES, 'UTF-8') ?>" placeholder="587" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Usuário</label>
                <input class="lc-input" type="text" name="smtp_username" value="<?= htmlspecialchars($smtp_username, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Criptografia</label>
                <select class="lc-select" name="smtp_encryption">
                    <?php
                    $enc = $smtp_encryption !== '' ? $smtp_encryption : 'tls';
                    $opts = ['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'Nenhuma'];
                    foreach ($opts as $k => $label) {
                        $sel = ($enc === $k) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '" ' . $sel . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Senha <?= $smtp_password_set === '1' ? '(já configurada)' : '' ?></label>
                <input class="lc-input" type="password" name="smtp_password" value="" placeholder="Deixe em branco para manter" />
            </div>

            <div class="lc-field">
                <label class="lc-label">From (e-mail)</label>
                <input class="lc-input" type="email" name="from_address" value="<?= htmlspecialchars($from_address, ENT_QUOTES, 'UTF-8') ?>" placeholder="no-reply@seudominio.com" />
            </div>

            <div class="lc-field">
                <label class="lc-label">From (nome)</label>
                <input class="lc-input" type="text" name="from_name" value="<?= htmlspecialchars($from_name, ENT_QUOTES, 'UTF-8') ?>" placeholder="LumiClinic" />
            </div>

            <div class="lc-flex lc-flex--end lc-gap-sm" style="grid-column: 1 / -1;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            </div>
        </form>
    </div>
</div>

<div class="lc-card lc-card--soft">
    <div class="lc-card__header">
        <div class="lc-card__title">Testar envio</div>
    </div>
    <div class="lc-card__body">
        <form method="post" action="/sys/settings/mail/test" class="lc-form lc-flex lc-gap-sm lc-flex--wrap lc-flex--center">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input class="lc-input" style="max-width:360px" type="email" name="to" placeholder="destino@exemplo.com" required />
            <button class="lc-btn lc-btn--secondary" type="submit">Enviar teste</button>
        </form>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
