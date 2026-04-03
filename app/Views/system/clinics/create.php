<?php
$title = 'Admin - Nova Clínica';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

ob_start();
?>

<style>
.sc-card{padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px}
.sc-title{font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:4px}
.sc-desc{font-size:12px;color:rgba(31,41,55,.45);margin-bottom:14px;line-height:1.5}
.sc-grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.sc-grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.sc-grid-addr{display:grid;grid-template-columns:140px 1fr;gap:12px}
.sc-grid-addr2{display:grid;grid-template-columns:100px 1fr 1fr;gap:12px}
.sc-grid-city{display:grid;grid-template-columns:1fr 80px;gap:12px}
.sc-opt{font-weight:500;font-size:12px;color:rgba(31,41,55,.40)}
.doc-toggle{display:inline-flex;border-radius:8px;overflow:hidden;border:1px solid rgba(17,24,39,.10)}
.doc-toggle label{padding:6px 16px;font-size:12px;font-weight:700;cursor:pointer;transition:all 150ms;color:rgba(31,41,55,.55);background:rgba(0,0,0,.02)}
.doc-toggle input{display:none}
.doc-toggle input:checked+label{background:rgba(99,102,241,.10);color:rgba(99,102,241,.9)}
@media(max-width:640px){.sc-grid2,.sc-grid3,.sc-grid-addr,.sc-grid-addr2,.sc-grid-city{grid-template-columns:1fr}}
@media(max-width:900px){.sc-layout-2col{grid-template-columns:1fr !important}}
</style>

<a href="/sys/clinics" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para clínicas
</a>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:18px;">Nova clínica</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= $e((string)$error) ?></div>
<?php endif; ?>

<form method="post" action="/sys/clinics/create" class="lc-form">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
    <input type="hidden" name="tenant_key" value="" />
    <input type="hidden" name="primary_domain" value="" />

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start;" class="sc-layout-2col">
<div>
    <!-- Dados da clínica -->
    <div class="sc-card">
        <div class="sc-title">Dados da clínica</div>
        <div class="sc-desc">Informações básicas da clínica.</div>
        <div class="lc-field">
            <label class="lc-label">Nome da clínica</label>
            <input class="lc-input" type="text" name="clinic_name" required placeholder="Ex: Clínica Estética Bella" />
        </div>
    </div>

    <!-- Contato da clínica -->
    <div class="sc-card">
        <div class="sc-title">Contato e endereço da clínica <span class="sc-opt">(opcional)</span></div>
        <div class="sc-desc">Preenche automaticamente as configurações da clínica. O dono também pode editar depois.</div>
        <div class="sc-grid2">
            <div class="lc-field">
                <label class="lc-label">E-mail da clínica</label>
                <input class="lc-input" type="email" name="clinic_email" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Telefone</label>
                <input class="lc-input" type="text" name="clinic_phone" placeholder="(00) 0000-0000" />
            </div>
        </div>
        <div class="sc-grid2" style="margin-top:12px;">
            <div class="lc-field">
                <label class="lc-label">WhatsApp</label>
                <input class="lc-input" type="text" name="clinic_whatsapp" placeholder="(00) 00000-0000" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Website</label>
                <input class="lc-input" type="text" name="clinic_website" placeholder="https://" />
            </div>
        </div>
        <div class="sc-grid2" style="margin-top:12px;">
            <div class="lc-field">
                <label class="lc-label">Instagram</label>
                <input class="lc-input" type="text" name="clinic_instagram" placeholder="@clinica" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Facebook</label>
                <input class="lc-input" type="text" name="clinic_facebook" />
            </div>
        </div>
        <div class="lc-field" style="margin-top:12px;">
            <label class="lc-label">Endereço da clínica</label>
        </div>
        <div class="sc-grid-addr" style="margin-top:4px;">
            <div class="lc-field">
                <label class="lc-label">CEP</label>
                <input class="lc-input" type="text" name="clinic_zip" id="clinicCepC" placeholder="00000-000" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Rua</label>
                <input class="lc-input" type="text" name="clinic_street" id="clinicStreetC" />
            </div>
        </div>
        <div class="sc-grid-addr2" style="margin-top:8px;">
            <div class="lc-field">
                <label class="lc-label">Número</label>
                <input class="lc-input" type="text" name="clinic_number" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Complemento</label>
                <input class="lc-input" type="text" name="clinic_complement" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Bairro</label>
                <input class="lc-input" type="text" name="clinic_neighborhood" id="clinicNeighborhoodC" />
            </div>
        </div>
        <div class="sc-grid-city" style="margin-top:8px;">
            <div class="lc-field">
                <label class="lc-label">Cidade</label>
                <input class="lc-input" type="text" name="clinic_city" id="clinicCityC" />
            </div>
            <div class="lc-field">
                <label class="lc-label">UF</label>
                <input class="lc-input" type="text" name="clinic_state" id="clinicStateC" maxlength="2" style="text-transform:uppercase;" />
            </div>
        </div>
    </div>

</div>
<div>
    <!-- Contratante -->
    <div class="sc-card">
        <div class="sc-title">Quem está contratando</div>
        <div class="sc-desc">Dados de quem contrata o sistema. Será o primeiro usuário com acesso total. Usado na assinatura e cobrança.</div>

        <div class="sc-grid2">
            <div class="lc-field">
                <label class="lc-label">Nome completo</label>
                <input class="lc-input" type="text" name="owner_name" required placeholder="Ex: Dr. João Silva" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Telefone</label>
                <input class="lc-input" type="text" name="owner_phone" id="cPhone" placeholder="(00) 00000-0000" />
            </div>
        </div>

        <div class="sc-grid2" style="margin-top:12px;">
            <div class="lc-field">
                <label class="lc-label">E-mail de login</label>
                <input class="lc-input" type="email" name="owner_email" required placeholder="joao@clinica.com" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Senha</label>
                <input class="lc-input" type="password" name="owner_password" required />
            </div>
        </div>

        <div style="margin-top:12px;">
            <label class="lc-label" style="margin-bottom:6px;">Tipo de documento</label>
            <div class="doc-toggle">
                <span><input type="radio" name="owner_doc_type" value="cpf" id="c_doc_cpf" checked onchange="cToggleDoc()"><label for="c_doc_cpf">CPF</label></span>
                <span><input type="radio" name="owner_doc_type" value="cnpj" id="c_doc_cnpj" onchange="cToggleDoc()"><label for="c_doc_cnpj">CNPJ</label></span>
            </div>
        </div>
        <div class="lc-field" style="margin-top:8px;max-width:340px;">
            <label class="lc-label" id="cDocLabel">CPF</label>
            <input class="lc-input" type="text" name="owner_doc_number" id="cDocInput" placeholder="000.000.000-00" />
        </div>
    </div>

    <!-- Endereço do contratante -->
    <div class="sc-card">
        <div class="sc-title">Endereço do contratante <span class="sc-opt">(opcional)</span></div>
        <div class="sc-desc">Usado para emissão de cobranças.</div>
        <div class="sc-grid-addr">
            <div class="lc-field">
                <label class="lc-label">CEP</label>
                <input class="lc-input" type="text" name="owner_postal_code" id="cCep" placeholder="00000-000" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Rua</label>
                <input class="lc-input" type="text" name="owner_street" id="cStreet" />
            </div>
        </div>
        <div class="sc-grid-addr2" style="margin-top:12px;">
            <div class="lc-field">
                <label class="lc-label">Número</label>
                <input class="lc-input" type="text" name="owner_number" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Complemento</label>
                <input class="lc-input" type="text" name="owner_complement" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Bairro</label>
                <input class="lc-input" type="text" name="owner_neighborhood" id="cNeighborhood" />
            </div>
        </div>
        <div class="sc-grid-city" style="margin-top:12px;">
            <div class="lc-field">
                <label class="lc-label">Cidade</label>
                <input class="lc-input" type="text" name="owner_city" id="cCity" />
            </div>
            <div class="lc-field">
                <label class="lc-label">UF</label>
                <input class="lc-input" type="text" name="owner_state" id="cState" maxlength="2" style="text-transform:uppercase;" />
            </div>
        </div>
    </div>

</div>
</div><!-- end grid -->

    <div style="display:flex;gap:10px;">
        <button class="lc-btn lc-btn--primary" type="submit">Criar clínica</button>
        <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Cancelar</a>
    </div>
</form>

<script>
function cMask(input, mask) {
    let v = input.value.replace(/\D/g, ''), r = '', vi = 0;
    for (let i = 0; i < mask.length && vi < v.length; i++) {
        r += mask[i] === '0' ? v[vi++] : mask[i];
    }
    input.value = r;
}
function cToggleDoc() {
    const cn = document.getElementById('c_doc_cnpj').checked;
    document.getElementById('cDocLabel').textContent = cn ? 'CNPJ' : 'CPF';
    document.getElementById('cDocInput').placeholder = cn ? '00.000.000/0000-00' : '000.000.000-00';
}

// Masks - contratante
document.getElementById('cDocInput').addEventListener('input', function() {
    cMask(this, document.getElementById('c_doc_cnpj').checked ? '00.000.000/0000-00' : '000.000.000-00');
});
document.getElementById('cPhone').addEventListener('input', function() {
    cMask(this, this.value.replace(/\D/g, '').length <= 10 ? '(00) 0000-0000' : '(00) 00000-0000');
});
document.getElementById('cCep').addEventListener('input', function() { cMask(this, '00000-000'); });

// Masks - clínica
document.querySelectorAll('[name="clinic_phone"],[name="clinic_whatsapp"]').forEach(function(el) {
    el.addEventListener('input', function() {
        cMask(this, this.value.replace(/\D/g, '').length <= 10 ? '(00) 0000-0000' : '(00) 00000-0000');
    });
});
var clinicCepC = document.getElementById('clinicCepC');
if (clinicCepC) {
    clinicCepC.addEventListener('input', function() { cMask(this, '00000-000'); });
    clinicCepC.addEventListener('blur', function() {
        var c = this.value.replace(/\D/g, '');
        if (c.length !== 8) return;
        fetch('https://viacep.com.br/ws/' + c + '/json/').then(function(r){return r.json();}).then(function(d) {
            if (d.erro) return;
            if (d.logradouro) document.getElementById('clinicStreetC').value = d.logradouro;
            if (d.bairro) document.getElementById('clinicNeighborhoodC').value = d.bairro;
            if (d.localidade) document.getElementById('clinicCityC').value = d.localidade;
            if (d.uf) document.getElementById('clinicStateC').value = d.uf;
        }).catch(function(){});
    });
}

// ViaCEP
document.getElementById('cCep').addEventListener('blur', function() {
    const c = this.value.replace(/\D/g, '');
    if (c.length !== 8) return;
    fetch('https://viacep.com.br/ws/' + c + '/json/').then(r => r.json()).then(d => {
        if (d.erro) return;
        if (d.logradouro) document.getElementById('cStreet').value = d.logradouro;
        if (d.bairro) document.getElementById('cNeighborhood').value = d.bairro;
        if (d.localidade) document.getElementById('cCity').value = d.localidade;
        if (d.uf) document.getElementById('cState').value = d.uf;
    }).catch(() => {});
});

// CPF/CNPJ validation
function validaCPF(cpf) {
    cpf = cpf.replace(/\D/g, '');
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
    let s = 0;
    for (let i = 0; i < 9; i++) s += parseInt(cpf[i]) * (10 - i);
    let r = 11 - (s % 11); if (r >= 10) r = 0;
    if (parseInt(cpf[9]) !== r) return false;
    s = 0;
    for (let i = 0; i < 10; i++) s += parseInt(cpf[i]) * (11 - i);
    r = 11 - (s % 11); if (r >= 10) r = 0;
    return parseInt(cpf[10]) === r;
}
function validaCNPJ(cnpj) {
    cnpj = cnpj.replace(/\D/g, '');
    if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) return false;
    const w1 = [5,4,3,2,9,8,7,6,5,4,3,2], w2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
    let s = 0;
    for (let i = 0; i < 12; i++) s += parseInt(cnpj[i]) * w1[i];
    let r = s % 11 < 2 ? 0 : 11 - (s % 11);
    if (parseInt(cnpj[12]) !== r) return false;
    s = 0;
    for (let i = 0; i < 13; i++) s += parseInt(cnpj[i]) * w2[i];
    r = s % 11 < 2 ? 0 : 11 - (s % 11);
    return parseInt(cnpj[13]) === r;
}

document.querySelector('form[action="/sys/clinics/create"]').addEventListener('submit', function(ev) {
    const docInput = document.getElementById('cDocInput');
    const raw = docInput.value.replace(/\D/g, '');
    if (raw === '') return; // optional
    const isCnpj = document.getElementById('c_doc_cnpj').checked;
    if (isCnpj && !validaCNPJ(raw)) {
        ev.preventDefault();
        alert('CNPJ inválido. Verifique o número digitado.');
        docInput.focus();
        return;
    }
    if (!isCnpj && !validaCPF(raw)) {
        ev.preventDefault();
        alert('CPF inválido. Verifique o número digitado.');
        docInput.focus();
    }
});
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
