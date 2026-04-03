<?php
$title = 'Meu perfil';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$user = $user ?? null;

$g = fn(string $k) => is_array($user) ? trim((string)($user[$k] ?? '')) : '';
$e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$docType = $g('doc_type') ?: 'cpf';

ob_start();
?>

<style>
.me-section{padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;max-width:680px}
.me-section__title{font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:4px}
.me-section__desc{font-size:12px;color:rgba(31,41,55,.45);margin-bottom:14px;line-height:1.5}
.doc-toggle{display:inline-flex;border-radius:8px;overflow:hidden;border:1px solid rgba(17,24,39,.10);margin-bottom:8px}
.doc-toggle label{padding:6px 16px;font-size:12px;font-weight:700;cursor:pointer;transition:all 150ms;color:rgba(31,41,55,.55);background:rgba(0,0,0,.02)}
.doc-toggle input{display:none}
.doc-toggle input:checked+label{background:rgba(99,102,241,.10);color:rgba(99,102,241,.9)}
</style>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:18px;">Meu perfil</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;max-width:680px;"><?= $e((string)$error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;max-width:680px;"><?= $e((string)$success) ?></div>
<?php endif; ?>

<form method="post" class="lc-form" action="/me">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />

    <!-- Dados pessoais -->
    <div class="me-section">
        <div class="me-section__title">Dados pessoais</div>
        <div class="me-section__desc">Informações de quem está contratando o sistema. Usadas na assinatura e cobrança.</div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="lc-field">
                <label class="lc-label">Nome completo</label>
                <input class="lc-input" type="text" name="name" value="<?= $e($g('name')) ?>" required />
            </div>
            <div class="lc-field">
                <label class="lc-label">E-mail</label>
                <input class="lc-input" type="email" name="email" value="<?= $e($g('email')) ?>" required />
            </div>
        </div>

        <div class="lc-field" style="margin-top:12px;">
            <label class="lc-label">Telefone</label>
            <input class="lc-input" type="text" name="phone" id="mePhone" value="<?= $e($g('phone')) ?>" placeholder="(00) 00000-0000" />
        </div>

        <div style="margin-top:12px;">
            <label class="lc-label" style="margin-bottom:6px;">Tipo de documento</label>
            <div class="doc-toggle">
                <span>
                    <input type="radio" name="doc_type" value="cpf" id="me_doc_cpf" <?= $docType !== 'cnpj' ? 'checked' : '' ?> onchange="meToggleDoc()">
                    <label for="me_doc_cpf">CPF</label>
                </span>
                <span>
                    <input type="radio" name="doc_type" value="cnpj" id="me_doc_cnpj" <?= $docType === 'cnpj' ? 'checked' : '' ?> onchange="meToggleDoc()">
                    <label for="me_doc_cnpj">CNPJ</label>
                </span>
            </div>
        </div>

        <div class="lc-field" style="margin-top:8px;">
            <label class="lc-label" id="meDocLabel"><?= $docType === 'cnpj' ? 'CNPJ' : 'CPF' ?></label>
            <input class="lc-input" type="text" name="doc_number" id="meDocInput" value="<?= $e($g('doc_number')) ?>"
                   placeholder="<?= $docType === 'cnpj' ? '00.000.000/0000-00' : '000.000.000-00' ?>" />
        </div>
    </div>

    <!-- Endereço (opcional) -->
    <div class="me-section">
        <div class="me-section__title">Endereço <span style="font-weight:500;font-size:12px;color:rgba(31,41,55,.40);">(opcional)</span></div>
        <div class="me-section__desc">Usado para emissão de cobranças e notas fiscais.</div>

        <div style="display:grid;grid-template-columns:140px 1fr;gap:12px;">
            <div class="lc-field">
                <label class="lc-label">CEP</label>
                <input class="lc-input" type="text" name="postal_code" id="meCep" value="<?= $e($g('postal_code')) ?>" placeholder="00000-000" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Rua</label>
                <input class="lc-input" type="text" name="address_street" id="meStreet" value="<?= $e($g('address_street')) ?>" />
            </div>
        </div>

        <div style="display:grid;grid-template-columns:100px 1fr 1fr;gap:12px;margin-top:12px;">
            <div class="lc-field">
                <label class="lc-label">Número</label>
                <input class="lc-input" type="text" name="address_number" value="<?= $e($g('address_number')) ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Complemento</label>
                <input class="lc-input" type="text" name="address_complement" value="<?= $e($g('address_complement')) ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Bairro</label>
                <input class="lc-input" type="text" name="address_neighborhood" id="meNeighborhood" value="<?= $e($g('address_neighborhood')) ?>" />
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 80px;gap:12px;margin-top:12px;">
            <div class="lc-field">
                <label class="lc-label">Cidade</label>
                <input class="lc-input" type="text" name="address_city" id="meCity" value="<?= $e($g('address_city')) ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">UF</label>
                <input class="lc-input" type="text" name="address_state" id="meState" value="<?= $e($g('address_state')) ?>" maxlength="2" style="text-transform:uppercase;" />
            </div>
        </div>
    </div>

    <div style="display:flex;gap:10px;max-width:680px;">
        <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
        <a class="lc-btn lc-btn--secondary" href="/">Voltar</a>
    </div>
</form>

<script>
function meMask(input, mask) {
    let v = input.value.replace(/\D/g, '');
    let r = '', vi = 0;
    for (let i = 0; i < mask.length && vi < v.length; i++) {
        if (mask[i] === '0') { r += v[vi++]; } else { r += mask[i]; }
    }
    input.value = r;
}
function meToggleDoc() {
    const isCnpj = document.getElementById('me_doc_cnpj').checked;
    document.getElementById('meDocLabel').textContent = isCnpj ? 'CNPJ' : 'CPF';
    document.getElementById('meDocInput').placeholder = isCnpj ? '00.000.000/0000-00' : '000.000.000-00';
}
document.getElementById('meDocInput').addEventListener('input', function() {
    const isCnpj = document.getElementById('me_doc_cnpj').checked;
    meMask(this, isCnpj ? '00.000.000/0000-00' : '000.000.000-00');
});
document.getElementById('mePhone').addEventListener('input', function() {
    const v = this.value.replace(/\D/g, '');
    meMask(this, v.length <= 10 ? '(00) 0000-0000' : '(00) 00000-0000');
});
document.getElementById('meCep').addEventListener('input', function() { meMask(this, '00000-000'); });
document.getElementById('meCep').addEventListener('blur', function() {
    const cep = this.value.replace(/\D/g, '');
    if (cep.length !== 8) return;
    fetch('https://viacep.com.br/ws/' + cep + '/json/')
        .then(r => r.json())
        .then(d => {
            if (d.erro) return;
            if (d.logradouro) document.getElementById('meStreet').value = d.logradouro;
            if (d.bairro) document.getElementById('meNeighborhood').value = d.bairro;
            if (d.localidade) document.getElementById('meCity').value = d.localidade;
            if (d.uf) document.getElementById('meState').value = d.uf;
        }).catch(() => {});
});
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
