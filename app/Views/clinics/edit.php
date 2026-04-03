<?php
$title = 'Clínica';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$clinic = $clinic ?? null;

$g = fn(string $k) => is_array($clinic) ? trim((string)($clinic[$k] ?? '')) : '';

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
            <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars($g('name'), ENT_QUOTES, 'UTF-8') ?>" required />
        </div>
    </div>

    <!-- Contato -->
    <div class="cl-section">
        <div class="cl-section__title">Contato</div>
        <div class="cl-row3">
            <div class="lc-field">
                <label class="lc-label">E-mail</label>
                <input class="lc-input" type="email" name="contact_email" value="<?= htmlspecialchars($g('contact_email'), ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Telefone</label>
                <input class="lc-input" type="text" name="contact_phone" value="<?= htmlspecialchars($g('contact_phone'), ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">WhatsApp</label>
                <input class="lc-input" type="text" name="contact_whatsapp" value="<?= htmlspecialchars($g('contact_whatsapp'), ENT_QUOTES, 'UTF-8') ?>" />
            </div>
        </div>
    </div>

    <!-- Endereço -->
    <div class="cl-section">
        <div class="cl-section__title">Endereço</div>
        <div style="display:grid;grid-template-columns:140px 1fr;gap:12px;">
            <div class="lc-field"><label class="lc-label">CEP</label><input class="lc-input" type="text" name="contact_address_zip" id="clinicZip" placeholder="00000-000" value="<?= htmlspecialchars($g('address_zip'), ENT_QUOTES, 'UTF-8') ?>" /></div>
            <div class="lc-field"><label class="lc-label">Rua</label><input class="lc-input" type="text" name="contact_address_street" id="clinicSt" value="<?= htmlspecialchars($g('address_street'), ENT_QUOTES, 'UTF-8') ?>" /></div>
        </div>
        <div class="cl-row4" style="margin-top:8px;">
            <div class="lc-field"><label class="lc-label">Número</label><input class="lc-input" type="text" name="contact_address_number" value="<?= htmlspecialchars($g('address_number'), ENT_QUOTES, 'UTF-8') ?>" /></div>
            <div class="lc-field"><label class="lc-label">Complemento</label><input class="lc-input" type="text" name="contact_address_complement" value="<?= htmlspecialchars($g('address_complement'), ENT_QUOTES, 'UTF-8') ?>" /></div>
            <div class="lc-field"><label class="lc-label">Bairro</label><input class="lc-input" type="text" name="contact_address_district" id="clinicNb" value="<?= htmlspecialchars($g('address_neighborhood'), ENT_QUOTES, 'UTF-8') ?>" /></div>
            <div></div>
        </div>
        <div class="cl-row3" style="margin-top:8px;">
            <div class="lc-field"><label class="lc-label">Cidade</label><input class="lc-input" type="text" name="contact_address_city" id="clinicCt" value="<?= htmlspecialchars($g('address_city'), ENT_QUOTES, 'UTF-8') ?>" /></div>
            <div class="lc-field"><label class="lc-label">UF</label><input class="lc-input" type="text" name="contact_address_state" id="clinicSt2" maxlength="2" placeholder="SP" value="<?= htmlspecialchars($g('address_state'), ENT_QUOTES, 'UTF-8') ?>" style="text-transform:uppercase;" /></div>
            <div></div>
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
                <div class="lc-field"><label class="lc-label">Site</label><input class="lc-input" type="url" name="contact_website" value="<?= htmlspecialchars($g('contact_website'), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://..." /></div>
                <div class="lc-field"><label class="lc-label">Instagram</label><input class="lc-input" type="url" name="contact_instagram" value="<?= htmlspecialchars($g('contact_instagram'), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://instagram.com/..." /></div>
                <div class="lc-field"><label class="lc-label">Facebook</label><input class="lc-input" type="url" name="contact_facebook" value="<?= htmlspecialchars($g('contact_facebook'), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://facebook.com/..." /></div>
            </div>
        </div>
    </details>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
        <a class="lc-btn lc-btn--secondary" href="/">Voltar</a>
    </div>
</form>

<script>
function clMask(input, mask) {
    let v = input.value.replace(/\D/g, ''), r = '', vi = 0;
    for (let i = 0; i < mask.length && vi < v.length; i++) {
        r += mask[i] === '0' ? v[vi++] : mask[i];
    }
    input.value = r;
}
var cz = document.getElementById('clinicZip');
if (cz) {
    cz.addEventListener('input', function() { clMask(this, '00000-000'); });
    cz.addEventListener('blur', function() {
        var c = this.value.replace(/\D/g, '');
        if (c.length !== 8) return;
        fetch('https://viacep.com.br/ws/' + c + '/json/').then(function(r){return r.json();}).then(function(d) {
            if (d.erro) return;
            if (d.logradouro) document.getElementById('clinicSt').value = d.logradouro;
            if (d.bairro) document.getElementById('clinicNb').value = d.bairro;
            if (d.localidade) document.getElementById('clinicCt').value = d.localidade;
            if (d.uf) document.getElementById('clinicSt2').value = d.uf;
        }).catch(function(){});
    });
}
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
