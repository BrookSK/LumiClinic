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

<form method="post" class="lc-form" action="/clinic" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

    <!-- Logo + Nome -->
    <?php $logoPath = $g('logo_path'); $logoError = trim((string)($_GET['logo_error'] ?? '')); ?>
    <style>
    .cl-top{display:grid;grid-template-columns:7fr minmax(300px,5fr);gap:16px;margin-bottom:16px;align-items:stretch}
    .cl-top .cl-section{margin-bottom:0}
    @media(max-width:860px){.cl-top{grid-template-columns:1fr}}
    .cl-logo{display:flex;gap:14px;align-items:center}
    .cl-logo__box{width:88px;height:88px;flex-shrink:0;border-radius:14px;border:1px dashed rgba(17,24,39,.22);display:flex;align-items:center;justify-content:center;overflow:hidden;background:#fff}
    .cl-logo__box img{max-width:100%;max-height:100%;object-fit:contain}
    .cl-logo__empty{font-size:11px;color:rgba(31,41,55,.55);text-align:center;padding:6px}
    .cl-logo__body{flex:1;min-width:0}
    .cl-logo__row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .cl-logo__name{font-size:12px;color:rgba(31,41,55,.60);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:160px}
    .cl-logo__hint{font-size:11px;color:rgba(31,41,55,.60);margin-top:6px;line-height:1.4}
    .cl-logo__remove{background:none;border:0;padding:0;margin-top:6px;cursor:pointer;font-size:12px;font-weight:500;color:rgba(31,41,55,.50)}
    </style>
    <div class="cl-top">
        <div class="cl-section">
            <div class="cl-section__title">Identifica&ccedil;&atilde;o</div>
            <div class="lc-field">
                <label class="lc-label">Nome da cl&iacute;nica</label>
                <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars($g('name'), ENT_QUOTES, 'UTF-8') ?>" required />
            </div>
        </div>
        <div class="cl-section">
            <div class="cl-section__title">Logo</div>
            <?php if ($logoError !== ''): ?>
                <div class="lc-alert lc-alert--danger" style="margin-bottom:10px;"><?= htmlspecialchars($logoError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <div class="cl-logo">
                <div class="cl-logo__box">
                    <?php if ($logoPath !== ''): ?>
                        <img id="clLogoPreview" src="/clinic/logo?v=<?= dechex(crc32($logoPath)) ?>" alt="Logo" />
                    <?php else: ?>
                        <img id="clLogoPreview" src="" alt="" style="display:none;" />
                        <span id="clLogoEmpty" class="cl-logo__empty">Sem logo</span>
                    <?php endif; ?>
                </div>
                <div class="cl-logo__body">
                    <div class="cl-logo__row">
                        <label class="lc-btn lc-btn--secondary lc-btn--sm" for="clLogoFile" style="cursor:pointer;margin:0;">Escolher imagem</label>
                        <span id="clLogoName" class="cl-logo__name">Nenhum arquivo</span>
                        <input type="file" name="logo" id="clLogoFile" accept="image/png,image/jpeg,image/webp" style="display:none;" />
                    </div>
                    <div class="cl-logo__hint">PNG, JPG ou WEBP, at&eacute; 2 MB. Aparece no cabe&ccedil;alho dos or&ccedil;amentos. Clique em Salvar para aplicar.</div>
                    <?php if ($logoPath !== ''): ?>
                        <button type="submit" form="clLogoRemoveForm" class="cl-logo__remove" onclick="return confirm('Remover a logo da cl\u00ednica?');">Remover logo</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
    (function(){
      var f = document.getElementById('clLogoFile');
      if (!f) return;
      f.addEventListener('change', function(){
        var file = f.files && f.files[0];
        if (!file) return;
        document.getElementById('clLogoName').textContent = file.name;
        var img = document.getElementById('clLogoPreview');
        var empty = document.getElementById('clLogoEmpty');
        img.src = URL.createObjectURL(file);
        img.style.display = '';
        if (empty) empty.style.display = 'none';
      });
    })();
    </script>
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
<form id="clLogoRemoveForm" method="post" action="/clinic/logo/remove" style="display:none;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
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
