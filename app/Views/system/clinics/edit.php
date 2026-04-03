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
.doc-toggle{display:inline-flex;border-radius:8px;overflow:hidden;border:1px solid rgba(17,24,39,.10);margin-bottom:8px}
.doc-toggle label{padding:6px 16px;font-size:12px;font-weight:700;cursor:pointer;transition:all 150ms;color:rgba(31,41,55,.55);background:rgba(0,0,0,.02)}
.doc-toggle input{display:none}
.doc-toggle input:checked+label{background:rgba(99,102,241,.10);color:rgba(99,102,241,.9)}
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

<!-- Dados da clínica -->
<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;max-width:680px;">
    <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:12px;">Dados da clínica</div>
    <div class="lc-field">
        <label class="lc-label">Nome da clínica</label>
        <input class="lc-input" type="text" name="name" value="<?= $e($name) ?>" required />
    </div>
</div>

<!-- Contato da clínica (opcional) -->
<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;max-width:680px;">
    <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:4px;">Contato e endereço da clínica <span style="font-weight:500;font-size:12px;color:rgba(31,41,55,.40);">(opcional)</span></div>
    <div style="font-size:12px;color:rgba(31,41,55,.45);margin-bottom:14px;">Esses dados preenchem automaticamente as configurações da clínica. O dono também pode editar em Configurações → Clínica.</div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="lc-field">
            <label class="lc-label">E-mail da clínica</label>
            <input class="lc-input" type="email" name="clinic_email" value="<?= $e($g('contact_email')) ?>" />
        </div>
        <div class="lc-field">
            <label class="lc-label">Telefone da clínica</label>
            <input class="lc-input" type="text" name="clinic_phone" value="<?= $e($g('contact_phone')) ?>" placeholder="(00) 0000-0000" />
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
        <div class="lc-field">
            <label class="lc-label">WhatsApp</label>
            <input class="lc-input" type="text" name="clinic_whatsapp" value="<?= $e(is_array($clinic) ? (string)($clinic['contact_whatsapp'] ?? '') : '') ?>" placeholder="(00) 00000-0000" />
        </div>
        <div class="lc-field">
            <label class="lc-label">Website</label>
            <input class="lc-input" type="text" name="clinic_website" value="<?= $e(is_array($clinic) ? (string)($clinic['contact_website'] ?? '') : '') ?>" placeholder="https://" />
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
        <div class="lc-field">
            <label class="lc-label">Instagram</label>
            <input class="lc-input" type="text" name="clinic_instagram" value="<?= $e(is_array($clinic) ? (string)($clinic['contact_instagram'] ?? '') : '') ?>" placeholder="@clinica" />
        </div>
        <div class="lc-field">
            <label class="lc-label">Facebook</label>
            <input class="lc-input" type="text" name="clinic_facebook" value="<?= $e(is_array($clinic) ? (string)($clinic['contact_facebook'] ?? '') : '') ?>" />
        </div>
    </div>

    <div class="lc-field" style="margin-top:12px;">
        <label class="lc-label">Endereço da clínica</label>
        <input class="lc-input" type="text" name="clinic_address" value="<?= $e(is_array($clinic) ? (string)($clinic['contact_address'] ?? '') : '') ?>" placeholder="Rua, número - Bairro, Cidade/UF" />
    </div>
</div>

<!-- Dados do contratante -->
<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;max-width:680px;">
    <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:4px;">Dados do contratante</div>
    <div style="font-size:12px;color:rgba(31,41,55,.45);margin-bottom:14px;">Informações de quem está contratando o sistema. Usadas na assinatura e cobrança. Esses dados também podem ser editados pelo próprio usuário em "Meu perfil".</div>

    <div class="lc-field">
        <label class="lc-label">Nome do contratante</label>
        <input class="lc-input" type="text" name="owner_name" value="<?= $e($ownerName) ?>" />
    </div>

    <div class="lc-field" style="margin-top:12px;">
        <label class="lc-label">Telefone</label>
        <input class="lc-input" type="text" name="owner_phone" value="<?= $e($ownerPhone) ?>" placeholder="(00) 00000-0000" id="ownerPhone" />
    </div>

    <div style="margin-top:12px;">
        <label class="lc-label" style="margin-bottom:6px;">Tipo de documento</label>
        <div class="doc-toggle">
            <span>
                <input type="radio" name="owner_doc_type" value="cpf" id="doc_cpf" <?= $ownerDocType !== 'cnpj' ? 'checked' : '' ?> onchange="toggleDocMask()">
                <label for="doc_cpf">CPF</label>
            </span>
            <span>
                <input type="radio" name="owner_doc_type" value="cnpj" id="doc_cnpj" <?= $ownerDocType === 'cnpj' ? 'checked' : '' ?> onchange="toggleDocMask()">
                <label for="doc_cnpj">CNPJ</label>
            </span>
        </div>
    </div>

    <div class="lc-field" style="margin-top:8px;">
        <label class="lc-label" id="docLabel"><?= $ownerDocType === 'cnpj' ? 'CNPJ' : 'CPF' ?></label>
        <input class="lc-input" type="text" name="cnpj" id="docInput" value="<?= $e($cnpj) ?>"
               placeholder="<?= $ownerDocType === 'cnpj' ? '00.000.000/0000-00' : '000.000.000-00' ?>" />
    </div>
</div>

<!-- Endereço (opcional) -->
<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;max-width:680px;">
    <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:4px;">Endereço <span style="font-weight:500;font-size:12px;color:rgba(31,41,55,.40);">(opcional)</span></div>
    <div style="font-size:12px;color:rgba(31,41,55,.45);margin-bottom:14px;">Endereço do responsável. Usado para emissão de cobranças.</div>

    <div style="display:grid;grid-template-columns:140px 1fr;gap:12px;">
        <div class="lc-field">
            <label class="lc-label">CEP</label>
            <input class="lc-input" type="text" name="owner_postal_code" id="ownerCep" value="<?= $e($ownerPostalCode) ?>" placeholder="00000-000" />
        </div>
        <div class="lc-field">
            <label class="lc-label">Rua</label>
            <input class="lc-input" type="text" name="owner_street" id="ownerStreet" value="<?= $e($ownerStreet) ?>" />
        </div>
    </div>

    <div style="display:grid;grid-template-columns:100px 1fr 1fr;gap:12px;margin-top:12px;">
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
            <input class="lc-input" type="text" name="owner_neighborhood" id="ownerNeighborhood" value="<?= $e($ownerNeighborhood) ?>" />
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 80px;gap:12px;margin-top:12px;">
        <div class="lc-field">
            <label class="lc-label">Cidade</label>
            <input class="lc-input" type="text" name="owner_city" id="ownerCity" value="<?= $e($ownerCity) ?>" />
        </div>
        <div class="lc-field">
            <label class="lc-label">UF</label>
            <input class="lc-input" type="text" name="owner_state" id="ownerState" value="<?= $e($ownerState) ?>" maxlength="2" style="text-transform:uppercase;" />
        </div>
    </div>
</div>

<div style="margin-bottom:16px;max-width:680px;">
    <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
</div>
</form>

<!-- Info técnica -->
<?php if ($tenantKey !== '' || $primaryDomain !== ''): ?>
<details style="margin-bottom:16px;">
    <summary style="font-size:12px;color:rgba(31,41,55,.40);cursor:pointer;list-style:none;">Informações técnicas</summary>
    <div style="margin-top:8px;padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);font-size:13px;color:rgba(31,41,55,.55);">
        <?php if ($tenantKey !== ''): ?><div>Tenant: <code><?= $e($tenantKey) ?></code></div><?php endif; ?>
        <?php if ($primaryDomain !== ''): ?><div>Domínio: <code><?= $e($primaryDomain) ?></code></div><?php endif; ?>
    </div>
</details>
<?php endif; ?>

<details>
    <summary style="font-size:12px;color:rgba(185,28,28,.60);cursor:pointer;list-style:none;">Excluir clínica</summary>
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
function applyMask(input, mask) {
    let v = input.value.replace(/\D/g, '');
    let r = '';
    let vi = 0;
    for (let i = 0; i < mask.length && vi < v.length; i++) {
        if (mask[i] === '0') { r += v[vi++]; }
        else { r += mask[i]; }
    }
    input.value = r;
}

function toggleDocMask() {
    const isCnpj = document.getElementById('doc_cnpj').checked;
    const inp = document.getElementById('docInput');
    const lbl = document.getElementById('docLabel');
    lbl.textContent = isCnpj ? 'CNPJ' : 'CPF';
    inp.placeholder = isCnpj ? '00.000.000/0000-00' : '000.000.000-00';
    inp.value = '';
}

document.getElementById('docInput').addEventListener('input', function() {
    const isCnpj = document.getElementById('doc_cnpj').checked;
    applyMask(this, isCnpj ? '00.000.000/0000-00' : '000.000.000-00');
});

document.getElementById('ownerPhone').addEventListener('input', function() {
    const v = this.value.replace(/\D/g, '');
    if (v.length <= 10) {
        applyMask(this, '(00) 0000-0000');
    } else {
        applyMask(this, '(00) 00000-0000');
    }
});

document.getElementById('ownerCep').addEventListener('input', function() {
    applyMask(this, '00000-000');
});

// Auto-fill address from CEP via ViaCEP
document.getElementById('ownerCep').addEventListener('blur', function() {
    const cep = this.value.replace(/\D/g, '');
    if (cep.length !== 8) return;
    fetch('https://viacep.com.br/ws/' + cep + '/json/')
        .then(r => r.json())
        .then(d => {
            if (d.erro) return;
            if (d.logradouro) document.getElementById('ownerStreet').value = d.logradouro;
            if (d.bairro) document.getElementById('ownerNeighborhood').value = d.bairro;
            if (d.localidade) document.getElementById('ownerCity').value = d.localidade;
            if (d.uf) document.getElementById('ownerState').value = d.uf;
        })
        .catch(() => {});
});
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
