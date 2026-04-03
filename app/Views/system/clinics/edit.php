<?php
$title = 'Admin - Editar Clínica';
$csrf = $_SESSION['_csrf'] ?? '';
$clinic = $clinic ?? null;
$error = $error ?? null;

$g = fn(string $k) => is_array($clinic) ? (string)($clinic[$k] ?? '') : '';
$e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$clinicId = is_array($clinic) ? (int)($clinic['id'] ?? 0) : 0;
$name = $g('name');
$tenantKey = $g('tenant_key');
$primaryDomain = $g('primary_domain');
$cnpj = $g('cnpj');
$ownerName = $g('owner_name');
$ownerPhone = $g('owner_phone');
$ownerDocType = $g('owner_doc_type') ?: 'cpf';
$ownerPostalCode = $g('owner_postal_code');
$ownerStreet = $g('owner_street');
$ownerNumber = $g('owner_number');
$ownerComplement = $g('owner_complement');
$ownerNeighborhood = $g('owner_neighborhood');
$ownerCity = $g('owner_city');
$ownerState = $g('owner_state');
$status = $g('status');
$createdAt = $g('created_at');

$stLbl = $status === 'active' ? 'Ativo' : ($status === 'inactive' ? 'Inativo' : $status);
$stClr = $status === 'active' ? '#16a34a' : '#b91c1c';
$createdFmt = $createdAt !== '' ? date('d/m/Y H:i', strtotime($createdAt)) : '—';

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

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= $e((string)$error) ?></div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);"><?= $e($name) ?></div>
        <span style="display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $stClr ?>18;color:<?= $stClr ?>;border:1px solid <?= $stClr ?>30"><?= $e($stLbl) ?></span>
        <span style="font-size:12px;color:rgba(31,41,55,.35);">ID #<?= $clinicId ?> · Criada em <?= $e($createdFmt) ?></span>
    </div>
    <form method="post" action="/sys/clinics/set-status" style="margin:0;">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
        <input type="hidden" name="id" value="<?= $clinicId ?>" />
        <input type="hidden" name="status" value="<?= $status === 'active' ? 'inactive' : 'active' ?>" />
        <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit"><?= $status === 'active' ? 'Desativar' : 'Ativar' ?></button>
    </form>
</div>

<form method="post" action="/sys/clinics/update" class="lc-form">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
    <input type="hidden" name="id" value="<?= $clinicId ?>" />
    <input type="hidden" name="tenant_key" value="<?= $e($tenantKey) ?>" />
    <input type="hidden" name="primary_domain" value="<?= $e($primaryDomain) ?>" />

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start;" class="sc-layout-2col">
<div>
    <!-- Dados da clínica -->
    <div class="sc-card">
        <div class="sc-title">Dados da clínica</div>
        <div class="lc-field">
            <label class="lc-label">Nome da clínica</label>
            <input class="lc-input" type="text" name="name" value="<?= $e($name) ?>" required />
        </div>
    </div>

    <!-- Contato da clínica -->
    <div class="sc-card">
        <div class="sc-title">Contato e endereço da clínica <span class="sc-opt">(opcional)</span></div>
        <div class="sc-desc">Preenche automaticamente as configurações da clínica. O dono também pode editar em Configurações → Clínica.</div>
        <div class="sc-grid2">
            <div class="lc-field">
                <label class="lc-label">E-mail da clínica</label>
                <input class="lc-input" type="email" name="clinic_email" value="<?= $e($g('contact_email')) ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Telefone</label>
                <input class="lc-input" type="text" name="clinic_phone" value="<?= $e($g('contact_phone')) ?>" placeholder="(00) 0000-0000" />
            </div>
        </div>
        <div class="sc-grid2" style="margin-top:12px;">
            <div class="lc-field">
                <label class="lc-label">WhatsApp</label>
                <input class="lc-input" type="text" name="clinic_whatsapp" value="<?= $e($g('contact_whatsapp')) ?>" placeholder="(00) 00000-0000" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Website</label>
                <input class="lc-input" type="text" name="clinic_website" value="<?= $e($g('contact_website')) ?>" placeholder="https://" />
            </div>
        </div>
        <div class="sc-grid2" style="margin-top:12px;">
            <div class="lc-field">
                <label class="lc-label">Instagram</label>
                <input class="lc-input" type="text" name="clinic_instagram" value="<?= $e($g('contact_instagram')) ?>" placeholder="@clinica" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Facebook</label>
                <input class="lc-input" type="text" name="clinic_facebook" value="<?= $e($g('contact_facebook')) ?>" />
            </div>
        </div>
        <div class="lc-field" style="margin-top:12px;">
            <label class="lc-label">Endereço da clínica</label>
            <input class="lc-input" type="text" name="clinic_address" value="<?= $e($g('contact_address')) ?>" placeholder="Rua, número - Bairro, Cidade/UF" />
        </div>
    </div>

</div>
<div>
    <!-- Dados do contratante -->
    <div class="sc-card">
        <div class="sc-title">Quem está contratando</div>
        <div class="sc-desc">Dados de quem contrata o sistema. Usados na assinatura e cobrança. Esses dados também podem ser editados pelo próprio usuário em "Meu perfil".</div>

        <div class="sc-grid2">
            <div class="lc-field">
                <label class="lc-label">Nome do contratante</label>
                <input class="lc-input" type="text" name="owner_name" value="<?= $e($ownerName) ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Telefone</label>
                <input class="lc-input" type="text" name="owner_phone" id="ePhone" value="<?= $e($ownerPhone) ?>" placeholder="(00) 00000-0000" />
            </div>
        </div>

        <div style="margin-top:12px;">
            <label class="lc-label" style="margin-bottom:6px;">Tipo de documento</label>
            <div class="doc-toggle">
                <span><input type="radio" name="owner_doc_type" value="cpf" id="e_doc_cpf" <?= $ownerDocType !== 'cnpj' ? 'checked' : '' ?> onchange="eToggleDoc()"><label for="e_doc_cpf">CPF</label></span>
                <span><input type="radio" name="owner_doc_type" value="cnpj" id="e_doc_cnpj" <?= $ownerDocType === 'cnpj' ? 'checked' : '' ?> onchange="eToggleDoc()"><label for="e_doc_cnpj">CNPJ</label></span>
            </div>
        </div>
        <div class="lc-field" style="margin-top:8px;max-width:340px;">
            <label class="lc-label" id="eDocLabel"><?= $ownerDocType === 'cnpj' ? 'CNPJ' : 'CPF' ?></label>
            <input class="lc-input" type="text" name="cnpj" id="eDocInput" value="<?= $e($cnpj) ?>"
                   placeholder="<?= $ownerDocType === 'cnpj' ? '00.000.000/0000-00' : '000.000.000-00' ?>" />
        </div>
    </div>

    <!-- Endereço do contratante -->
    <div class="sc-card">
        <div class="sc-title">Endereço do contratante <span class="sc-opt">(opcional)</span></div>
        <div class="sc-desc">Usado para emissão de cobranças.</div>
        <div class="sc-grid-addr">
            <div class="lc-field">
                <label class="lc-label">CEP</label>
                <input class="lc-input" type="text" name="owner_postal_code" id="eCep" value="<?= $e($ownerPostalCode) ?>" placeholder="00000-000" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Rua</label>
                <input class="lc-input" type="text" name="owner_street" id="eStreet" value="<?= $e($ownerStreet) ?>" />
            </div>
        </div>
        <div class="sc-grid-addr2" style="margin-top:12px;">
            <div class="lc-field">
                <label class="lc-label">Número</label>
                <input class="lc-input" type="text" name="owner_number" value="<?= $e($ownerNumber) ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Complemento</label>
                <input class="lc-input" type="text" name="owner_complement" value="<?= $e($ownerComplement) ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Bairro</label>
                <input class="lc-input" type="text" name="owner_neighborhood" id="eNeighborhood" value="<?= $e($ownerNeighborhood) ?>" />
            </div>
        </div>
        <div class="sc-grid-city" style="margin-top:12px;">
            <div class="lc-field">
                <label class="lc-label">Cidade</label>
                <input class="lc-input" type="text" name="owner_city" id="eCity" value="<?= $e($ownerCity) ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">UF</label>
                <input class="lc-input" type="text" name="owner_state" id="eState" value="<?= $e($ownerState) ?>" maxlength="2" style="text-transform:uppercase;" />
            </div>
        </div>
    </div>

</div>
</div><!-- end grid -->

    <div style="display:flex;gap:10px;margin-bottom:16px;">
        <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
    </div>
</form>

<!-- Info técnica -->
<?php if ($tenantKey !== '' || $primaryDomain !== ''): ?>
<details style="margin-bottom:16px;">
    <summary style="font-size:12px;color:rgba(31,41,55,.40);cursor:pointer;">Informações técnicas</summary>
    <div style="margin-top:8px;padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);font-size:13px;color:rgba(31,41,55,.55);">
        <?php if ($tenantKey !== ''): ?><div>Tenant: <code><?= $e($tenantKey) ?></code></div><?php endif; ?>
        <?php if ($primaryDomain !== ''): ?><div>Domínio: <code><?= $e($primaryDomain) ?></code></div><?php endif; ?>
    </div>
</details>
<?php endif; ?>

<details>
    <summary style="font-size:12px;color:rgba(185,28,28,.60);cursor:pointer;">Excluir clínica</summary>
    <div style="margin-top:8px;padding:12px;border-radius:12px;border:1px solid rgba(185,28,28,.18);background:rgba(185,28,28,.04);">
        <div style="font-size:12px;color:rgba(185,28,28,.70);margin-bottom:8px;">Essa ação oculta a clínica do sistema. Os dados não são apagados permanentemente.</div>
        <form method="post" action="/sys/clinics/delete" style="margin:0;" onsubmit="return confirm('Tem certeza que deseja excluir esta clínica?');">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
            <input type="hidden" name="id" value="<?= $clinicId ?>" />
            <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit">Confirmar exclusão</button>
        </form>
    </div>
</details>

<script>
function eMask(input, mask) {
    let v = input.value.replace(/\D/g, ''), r = '', vi = 0;
    for (let i = 0; i < mask.length && vi < v.length; i++) {
        r += mask[i] === '0' ? v[vi++] : mask[i];
    }
    input.value = r;
}
function eToggleDoc() {
    const cn = document.getElementById('e_doc_cnpj').checked;
    document.getElementById('eDocLabel').textContent = cn ? 'CNPJ' : 'CPF';
    document.getElementById('eDocInput').placeholder = cn ? '00.000.000/0000-00' : '000.000.000-00';
}
document.getElementById('eDocInput').addEventListener('input', function() {
    eMask(this, document.getElementById('e_doc_cnpj').checked ? '00.000.000/0000-00' : '000.000.000-00');
});
document.getElementById('ePhone').addEventListener('input', function() {
    eMask(this, this.value.replace(/\D/g, '').length <= 10 ? '(00) 0000-0000' : '(00) 00000-0000');
});
document.getElementById('eCep').addEventListener('input', function() { eMask(this, '00000-000'); });
document.getElementById('eCep').addEventListener('blur', function() {
    const c = this.value.replace(/\D/g, '');
    if (c.length !== 8) return;
    fetch('https://viacep.com.br/ws/' + c + '/json/').then(r => r.json()).then(d => {
        if (d.erro) return;
        if (d.logradouro) document.getElementById('eStreet').value = d.logradouro;
        if (d.bairro) document.getElementById('eNeighborhood').value = d.bairro;
        if (d.localidade) document.getElementById('eCity').value = d.localidade;
        if (d.uf) document.getElementById('eState').value = d.uf;
    }).catch(() => {});
});
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
