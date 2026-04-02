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

$address_street = ''; $address_number = ''; $address_complement = '';
$address_district = ''; $address_city = ''; $address_state = ''; $address_zip = '';

if ($line1 !== '' && preg_match('/^(.*?)(?:,\s*([^\-]+))?(?:\s*-\s*(.*))?$/', $line1, $m)) {
    $address_street = trim((string)($m[1] ?? ''));
    $address_number = trim((string)($m[2] ?? ''));
    $address_complement = trim((string)($m[3] ?? ''));
}
if ($line2 !== '') {
    $parts = explode(' - ', $line2, 2);
    $address_district = trim((string)($parts[0] ?? ''));
    $tail = trim((string)($parts[1] ?? ''));
    if (preg_match('/^(.*?)(?:\/(\w{2}))?$/', $tail, $mm)) {
        $address_city = trim((string)($mm[1] ?? ''));
        $address_state = strtoupper(trim((string)($mm[2] ?? '')));
    }
}
if ($line3 !== '' && preg_match('/CEP:\s*([0-9\-\.]+)/i', $line3, $m)) {
    $address_zip = trim((string)($m[1] ?? ''));
}

ob_start();
?>

<style>
.cl-section{padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px}
.cl-section__title{font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:12px}
.cl-row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.cl-row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.cl-row4{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:12px}
@media(max-width:640px){.cl-row2,.cl-row3,.cl-row4{grid-template-columns:1fr}}
</style>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:18px;">Dados da clínica</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" class="lc-form" action="/clinic">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

    <!-- Nome -->
    <div class="cl-section">
        <div class="cl-section__title">Identificação</div>
        <div class="lc-field">
            <label class="lc-label">Nome da clínica</label>
            <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($clinic['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />
        </div>
    </div>

    <!-- Contato -->
    <div class="cl-section">
        <div class="cl-section__title">Contato</div>
        <div class="cl-row3">
            <div class="lc-field">
                <label class="lc-label">E-mail</label>
                <input class="lc-input" type="email" name="contact_email" value="<?= htmlspecialchars((string)($clinic['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Telefone</label>
                <input class="lc-input" type="text" name="contact_phone" value="<?= htmlspecialchars((string)($clinic['contact_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">WhatsApp</label>
                <input class="lc-input" type="text" name="contact_whatsapp" value="<?= htmlspecialchars((string)($clinic['contact_whatsapp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            </div>
        </div>
    </div>

    <!-- Endereço -->
    <div class="cl-section">
        <div class="cl-section__title">Endereço</div>
        <div class="cl-row4">
            <div class="lc-field"><label class="lc-label">Rua</label><input class="lc-input" type="text" name="contact_address_street" value="<?= htmlspecialchars($address_street, ENT_QUOTES, 'UTF-8') ?>" /></div>
            <div class="lc-field"><label class="lc-label">Número</label><input class="lc-input" type="text" name="contact_address_number" value="<?= htmlspecialchars($address_number, ENT_QUOTES, 'UTF-8') ?>" /></div>
            <div class="lc-field"><label class="lc-label">Complemento</label><input class="lc-input" type="text" name="contact_address_complement" value="<?= htmlspecialchars($address_complement, ENT_QUOTES, 'UTF-8') ?>" /></div>
            <div class="lc-field"><label class="lc-label">Bairro</label><input class="lc-input" type="text" name="contact_address_district" value="<?= htmlspecialchars($address_district, ENT_QUOTES, 'UTF-8') ?>" /></div>
        </div>
        <div class="cl-row3" style="margin-top:4px;">
            <div class="lc-field"><label class="lc-label">Cidade</label><input class="lc-input" type="text" name="contact_address_city" value="<?= htmlspecialchars($address_city, ENT_QUOTES, 'UTF-8') ?>" /></div>
            <div class="lc-field"><label class="lc-label">UF</label><input class="lc-input" type="text" name="contact_address_state" maxlength="2" placeholder="SP" value="<?= htmlspecialchars($address_state, ENT_QUOTES, 'UTF-8') ?>" /></div>
            <div class="lc-field"><label class="lc-label">CEP</label><input class="lc-input" type="text" name="contact_address_zip" placeholder="00000-000" value="<?= htmlspecialchars($address_zip, ENT_QUOTES, 'UTF-8') ?>" /></div>
        </div>
    </div>

    <!-- Redes sociais -->
    <details class="cl-section" style="cursor:default;">
        <summary style="list-style:none;cursor:pointer;display:flex;align-items:center;gap:6px;font-weight:750;font-size:14px;color:rgba(31,41,55,.90);">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            Redes sociais e site
        </summary>
        <div style="margin-top:14px;">
            <div class="cl-row3">
                <div class="lc-field"><label class="lc-label">Site</label><input class="lc-input" type="url" name="contact_website" value="<?= htmlspecialchars((string)($clinic['contact_website'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://..." /></div>
                <div class="lc-field"><label class="lc-label">Instagram</label><input class="lc-input" type="url" name="contact_instagram" value="<?= htmlspecialchars((string)($clinic['contact_instagram'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://instagram.com/..." /></div>
                <div class="lc-field"><label class="lc-label">Facebook</label><input class="lc-input" type="url" name="contact_facebook" value="<?= htmlspecialchars((string)($clinic['contact_facebook'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://facebook.com/..." /></div>
            </div>
        </div>
    </details>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
        <a class="lc-btn lc-btn--secondary" href="/">Voltar</a>
    </div>
</form>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
