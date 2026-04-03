<?php
$title = 'Criar conta';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$plans = $plans ?? [];
$e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$hasTrial = false;
foreach ($plans as $p) {
    if ((int)($p['trial_days'] ?? 0) > 0) { $hasTrial = true; break; }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= $e($title) ?></title>
    <link rel="icon" href="/icone_1.png" />
    <link rel="stylesheet" href="/assets/css/design-system.css" />
    <style>
    .su-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:linear-gradient(135deg,rgba(99,102,241,.03),rgba(245,158,11,.03))}
    .su-card{width:100%;max-width:820px;background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(17,24,39,.08);overflow:hidden}
    .su-header{padding:28px 32px 0;display:flex;align-items:center;gap:12px}
    .su-body{padding:24px 32px 32px}
    .su-title{font-weight:900;font-size:22px;color:rgba(31,41,55,.96)}
    .su-sub{font-size:13px;color:rgba(31,41,55,.50);margin-top:4px}
    .su-section{font-weight:750;font-size:13px;color:rgba(31,41,55,.80);margin:18px 0 10px;padding-bottom:6px;border-bottom:1px solid rgba(17,24,39,.06)}
    .su-grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .su-grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
    .su-plan{padding:14px;border-radius:12px;border:2px solid rgba(17,24,39,.08);cursor:pointer;transition:all 150ms;text-align:center}
    .su-plan:hover{border-color:rgba(99,102,241,.3)}
    .su-plan.selected{border-color:rgba(99,102,241,.5);background:rgba(99,102,241,.04)}
    .su-plan input{display:none}
    .su-plan__name{font-weight:700;font-size:14px;color:rgba(31,41,55,.90)}
    .su-plan__price{font-weight:900;font-size:20px;color:rgba(129,89,1,1);margin-top:4px}
    .su-plan__period{font-size:11px;color:rgba(31,41,55,.45)}
    .su-plan__trial{font-size:11px;color:rgba(34,197,94,.8);font-weight:600;margin-top:4px}
    .doc-toggle{display:inline-flex;border-radius:8px;overflow:hidden;border:1px solid rgba(17,24,39,.10)}
    .doc-toggle label{padding:5px 14px;font-size:11px;font-weight:700;cursor:pointer;color:rgba(31,41,55,.55);background:rgba(0,0,0,.02);transition:all 150ms}
    .doc-toggle input{display:none}
    .doc-toggle input:checked+label{background:rgba(99,102,241,.10);color:rgba(99,102,241,.9)}
    @media(max-width:640px){.su-grid2,.su-grid3{grid-template-columns:1fr}.su-body{padding:20px}}
    .cc-section{margin-top:0}
    .cc-title{font-weight:750;font-size:13px;color:rgba(31,41,55,.80);margin-bottom:10px}
    .cc-note{font-size:12px;color:rgba(31,41,55,.50);margin-bottom:10px;line-height:1.5}
    .cc-grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .cc-grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
    @media(max-width:640px){.cc-grid2,.cc-grid3{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="su-wrap">
<div class="su-card">
    <div class="su-header">
        <div style="width:40px;height:40px;background:#000;border-radius:10px;overflow:hidden;flex-shrink:0;">
            <img src="/icone_1.png" alt="LumiClinic" style="width:100%;height:100%;object-fit:contain;" />
        </div>
        <div>
            <div class="su-title">Criar sua conta</div>
            <div class="su-sub">Preencha os dados abaixo para começar a usar o sistema.</div>
        </div>
    </div>

    <div class="su-body">
        <?php if ($error): ?>
            <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= $e((string)$error) ?></div>
        <?php endif; ?>

        <form method="post" action="/criar-conta" autocomplete="off" id="signupForm">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />

            <!-- Seus dados -->
            <div class="su-section">Seus dados</div>
            <div class="su-grid2">
                <div class="lc-field">
                    <label class="lc-label">Nome completo</label>
                    <input class="lc-input" type="text" name="owner_name" required />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Telefone</label>
                    <input class="lc-input" type="text" name="owner_phone" id="suPhone" placeholder="(00) 00000-0000" />
                </div>
            </div>
            <div class="su-grid2" style="margin-top:10px;">
                <div class="lc-field">
                    <label class="lc-label">E-mail</label>
                    <input class="lc-input" type="email" name="owner_email" required />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Senha</label>
                    <input class="lc-input" type="password" name="owner_password" required minlength="8" placeholder="Mínimo 8 caracteres" />
                </div>
            </div>

            <div style="margin-top:10px;">
                <div class="doc-toggle">
                    <span><input type="radio" name="doc_type" value="cpf" id="su_cpf" checked onchange="suToggleDoc()"><label for="su_cpf">CPF</label></span>
                    <span><input type="radio" name="doc_type" value="cnpj" id="su_cnpj" onchange="suToggleDoc()"><label for="su_cnpj">CNPJ</label></span>
                </div>
            </div>
            <div class="lc-field" style="margin-top:6px;max-width:280px;">
                <label class="lc-label" id="suDocLabel">CPF</label>
                <input class="lc-input" type="text" name="doc_number" id="suDocInput" placeholder="000.000.000-00" />
            </div>

            <!-- Clínica -->
            <div class="su-section">Sua clínica</div>
            <div class="lc-field">
                <label class="lc-label">Nome da clínica</label>
                <input class="lc-input" type="text" name="clinic_name" required placeholder="Ex: Clínica Estética Bella" />
            </div>

            <!-- Plano -->
            <?php if (!empty($plans)): ?>
            <div class="su-section">Escolha seu plano</div>
            <div class="su-grid3">
                <?php foreach ($plans as $p):
                    $price = (int)($p['price_cents'] ?? 0);
                    $trial = (int)($p['trial_days'] ?? 0);
                    $code = (string)($p['code'] ?? '');
                    $pName = (string)($p['name'] ?? '');
                    $isFree = $price === 0;
                ?>
                <label class="su-plan" onclick="selectPlan(this)">
                    <input type="radio" name="plan" value="<?= $e($code) ?>" />
                    <div class="su-plan__name"><?= $e($pName) ?></div>
                    <?php if ($isFree): ?>
                        <div class="su-plan__price">Grátis</div>
                    <?php else: ?>
                        <div class="su-plan__price">R$ <?= number_format($price / 100, 2, ',', '.') ?></div>
                        <div class="su-plan__period">/mês</div>
                    <?php endif; ?>
                    <?php if ($trial > 0): ?>
                        <div class="su-plan__trial"><?= $trial ?> dias grátis</div>
                    <?php endif; ?>
                </label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Cartão de crédito -->
            <div class="su-section">Cartão de crédito</div>
            <div style="padding:16px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:rgba(0,0,0,.01);">
                <?php
                $cardTitle = '';
                $cardNote = count($plans) > 0 && min(array_column($plans, 'price_cents')) == 0
                    ? 'Para planos gratuitos, o cartão é usado apenas para validação. Nenhuma cobrança será feita.'
                    : null;
                $cardRequired = true;
                include __DIR__ . '/../billing/_card_fields.php';
                ?>
            </div>

            <div style="margin-top:22px;display:flex;gap:10px;align-items:center;">
                <button class="lc-btn lc-btn--primary" type="submit" style="padding:10px 28px;">Criar minha conta</button>
                <a href="/login" style="font-size:13px;color:rgba(99,102,241,.7);text-decoration:none;font-weight:600;">Já tenho conta</a>
            </div>
        </form>
    </div>
</div>
</div>

<script>
function suMask(input, mask) {
    let v = input.value.replace(/\D/g, ''), r = '', vi = 0;
    for (let i = 0; i < mask.length && vi < v.length; i++) {
        r += mask[i] === '0' ? v[vi++] : mask[i];
    }
    input.value = r;
}
function suToggleDoc() {
    const cn = document.getElementById('su_cnpj').checked;
    document.getElementById('suDocLabel').textContent = cn ? 'CNPJ' : 'CPF';
    document.getElementById('suDocInput').placeholder = cn ? '00.000.000/0000-00' : '000.000.000-00';
}
function selectPlan(el) {
    document.querySelectorAll('.su-plan').forEach(function(p) { p.classList.remove('selected'); });
    el.classList.add('selected');
    el.querySelector('input').checked = true;
}
document.getElementById('suDocInput').addEventListener('input', function() {
    suMask(this, document.getElementById('su_cnpj').checked ? '00.000.000/0000-00' : '000.000.000-00');
});
document.getElementById('suPhone').addEventListener('input', function() {
    suMask(this, this.value.replace(/\D/g, '').length <= 10 ? '(00) 0000-0000' : '(00) 00000-0000');
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
document.getElementById('signupForm').addEventListener('submit', function(ev) {
    var raw = document.getElementById('suDocInput').value.replace(/\D/g, '');
    if (raw === '') return;
    var cn = document.getElementById('su_cnpj').checked;
    if (cn && !validaCNPJ(raw)) { ev.preventDefault(); alert('CNPJ inválido.'); return; }
    if (!cn && !validaCPF(raw)) { ev.preventDefault(); alert('CPF inválido.'); return; }
});
</script>
</body>
</html>
