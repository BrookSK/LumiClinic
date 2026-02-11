<?php
$title = 'Clínica';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$clinic = $clinic ?? null;

$addressText = (string)($clinic['contact_address'] ?? '');
$addressLines = preg_split('/\r\n|\r|\n/', $addressText) ?: [];
$line1 = trim((string)($addressLines[0] ?? ''));
$line2 = trim((string)($addressLines[1] ?? ''));
$line3 = trim((string)($addressLines[2] ?? ''));

$address_street = '';
$address_number = '';
$address_complement = '';
$address_district = '';
$address_city = '';
$address_state = '';
$address_zip = '';

if ($line1 !== '') {
    if (preg_match('/^(.*?)(?:,\s*([^\-]+))?(?:\s*-\s*(.*))?$/', $line1, $m)) {
        $address_street = trim((string)($m[1] ?? ''));
        $address_number = trim((string)($m[2] ?? ''));
        $address_complement = trim((string)($m[3] ?? ''));
    }
}

if ($line2 !== '') {
    if (preg_match('/^(.*?)(?:\s*-\s*(.*?))?(?:\/(\w{2}))?$/', $line2, $m)) {
        $address_district = trim((string)($m[1] ?? ''));
        $address_city = trim((string)($m[2] ?? ''));
        $address_state = strtoupper(trim((string)($m[3] ?? '')));
        if ($address_city === '' && str_contains($line2, ' - ')) {
            $parts = explode(' - ', $line2, 2);
            $address_district = trim((string)($parts[0] ?? ''));
            $tail = trim((string)($parts[1] ?? ''));
            if (preg_match('/^(.*?)(?:\/(\w{2}))?$/', $tail, $mm)) {
                $address_city = trim((string)($mm[1] ?? ''));
                $address_state = strtoupper(trim((string)($mm[2] ?? '')));
            }
        }
    }
}

if ($line3 !== '') {
    if (preg_match('/CEP:\s*([0-9\-\.]+)/i', $line3, $m)) {
        $address_zip = trim((string)($m[1] ?? ''));
    }
}
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Dados da clínica</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/clinic">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Nome</label>
        <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($clinic['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />

        <div style="margin-top:16px; font-weight:600;">Contato da clínica</div>

        <label class="lc-label">E-mail</label>
        <input class="lc-input" type="email" name="contact_email" value="<?= htmlspecialchars((string)($clinic['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

        <div class="lc-grid lc-grid--2 lc-gap-grid">
            <div>
                <label class="lc-label">Telefone</label>
                <input class="lc-input" type="text" name="contact_phone" value="<?= htmlspecialchars((string)($clinic['contact_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div>
                <label class="lc-label">WhatsApp</label>
                <input class="lc-input" type="text" name="contact_whatsapp" value="<?= htmlspecialchars((string)($clinic['contact_whatsapp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            </div>
        </div>

        <label class="lc-label">Endereço</label>
        <div class="lc-grid lc-gap-grid">
            <div>
                <label class="lc-label">Rua</label>
                <input class="lc-input" type="text" name="contact_address_street" value="<?= htmlspecialchars($address_street, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div>
                <label class="lc-label">Número</label>
                <input class="lc-input" type="text" name="contact_address_number" value="<?= htmlspecialchars($address_number, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
        </div>
        <div class="lc-grid lc-gap-grid">
            <div>
                <label class="lc-label">Complemento</label>
                <input class="lc-input" type="text" name="contact_address_complement" value="<?= htmlspecialchars($address_complement, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div>
                <label class="lc-label">Bairro</label>
                <input class="lc-input" type="text" name="contact_address_district" value="<?= htmlspecialchars($address_district, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
        </div>
        <div class="lc-grid lc-gap-grid">
            <div>
                <label class="lc-label">Cidade</label>
                <input class="lc-input" type="text" name="contact_address_city" value="<?= htmlspecialchars($address_city, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div>
                <label class="lc-label">UF</label>
                <input class="lc-input" type="text" name="contact_address_state" maxlength="2" placeholder="SP" value="<?= htmlspecialchars($address_state, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
        </div>
        <div>
            <label class="lc-label">CEP</label>
            <input class="lc-input" type="text" name="contact_address_zip" placeholder="00000-000" value="<?= htmlspecialchars($address_zip, ENT_QUOTES, 'UTF-8') ?>" />
        </div>

        <label class="lc-label">Site</label>
        <input class="lc-input" type="url" name="contact_website" value="<?= htmlspecialchars((string)($clinic['contact_website'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

        <div class="lc-grid lc-grid--2 lc-gap-grid">
            <div>
                <label class="lc-label">Instagram</label>
                <input class="lc-input" type="url" name="contact_instagram" value="<?= htmlspecialchars((string)($clinic['contact_instagram'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div>
                <label class="lc-label">Facebook</label>
                <input class="lc-input" type="url" name="contact_facebook" value="<?= htmlspecialchars((string)($clinic['contact_facebook'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/">Voltar</a>
            <a class="lc-btn lc-btn--secondary" href="/clinic/working-hours">Horários</a>
            <a class="lc-btn lc-btn--secondary" href="/clinic/closed-days">Feriados e Recesso</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
