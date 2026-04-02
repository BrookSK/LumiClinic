<?php
$title = 'Novo paciente';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$professionals = $professionals ?? [];
$patientOrigins = $patient_origins ?? [];

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
        return true;
    }

    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) {
        return false;
    }

    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) {
            return false;
        }
        return in_array($permissionCode, $permissions['allow'], true);
    }

    return in_array($permissionCode, $permissions, true);
};
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Cadastro</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($can('patients.create')): ?>
        <form method="post" class="lc-form" action="/patients/create">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Nome</label>
        <input class="lc-input" type="text" name="name" required />

        <div class="lc-grid">
            <div>
                <label class="lc-label">E-mail</label>
                <input class="lc-input" type="email" name="email" id="patient_email" />
            </div>
            <div>
                <label class="lc-label">Telefone</label>
                <input class="lc-input" type="text" name="phone" />
            </div>
        </div>

        <div class="lc-card lc-card--soft" style="margin-top:12px; padding:14px;">
            <div style="font-weight:600; margin-bottom:4px;">Acesso ao Portal do Paciente</div>
            <div class="lc-muted" style="font-size:12px; margin-bottom:10px;">Preencha a senha para criar o acesso. O e-mail de login será o campo "E-mail" acima. O paciente pode trocar a senha após o primeiro acesso.</div>
            <div class="lc-flex lc-gap-sm" style="align-items:center;">
                <input class="lc-input" type="text" name="portal_password" id="portal_password" placeholder="Deixe em branco para não criar acesso" autocomplete="off" style="flex:1;" />
                <button type="button" class="lc-btn lc-btn--secondary" onclick="(function(){var c='abcdefghjkmnpqrstuvwxyz23456789';var p='';for(var i=0;i<8;i++)p+=c[Math.floor(Math.random()*c.length)];document.getElementById('portal_password').value=p;})()">Gerar senha</button>
            </div>
        </div>

        <label class="lc-label" style="margin-top:10px;">WhatsApp</label>
        <label class="lc-checkbox" style="display:flex; gap:8px; align-items:center;">
            <input type="checkbox" name="whatsapp_opt_in" value="1" checked />
            <span>Receber lembretes por WhatsApp</span>
        </label>

        <div class="lc-grid">
            <div>
                <label class="lc-label">Data de nascimento</label>
                <input class="lc-input" type="date" name="birth_date" />
            </div>
            <div>
                <label class="lc-label">Sexo</label>
                <select class="lc-select" name="sex">
                    <option value="">Selecione</option>
                    <option value="female">Feminino</option>
                    <option value="male">Masculino</option>
                    <option value="other">Outro</option>
                </select>
            </div>
        </div>

        <label class="lc-label">CPF</label>
        <input class="lc-input" type="text" name="cpf" />

        <label class="lc-label">Endereço</label>
        <div class="lc-grid lc-gap-grid">
            <div>
                <label class="lc-label">Rua</label>
                <input class="lc-input" type="text" name="address_street" />
            </div>
            <div>
                <label class="lc-label">Número</label>
                <input class="lc-input" type="text" name="address_number" />
            </div>
        </div>
        <div class="lc-grid lc-gap-grid">
            <div>
                <label class="lc-label">Complemento</label>
                <input class="lc-input" type="text" name="address_complement" />
            </div>
            <div>
                <label class="lc-label">Bairro</label>
                <input class="lc-input" type="text" name="address_district" />
            </div>
        </div>
        <div class="lc-grid lc-gap-grid">
            <div>
                <label class="lc-label">Cidade</label>
                <input class="lc-input" type="text" name="address_city" />
            </div>
            <div>
                <label class="lc-label">UF</label>
                <input class="lc-input" type="text" name="address_state" maxlength="2" placeholder="SP" />
            </div>
        </div>
        <div>
            <label class="lc-label">CEP</label>
            <input class="lc-input" type="text" name="address_zip" placeholder="00000-000" />
        </div>

        <label class="lc-label">Observações</label>
        <textarea class="lc-input" name="notes" rows="4"></textarea>

            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
                <button class="lc-btn lc-btn--primary" type="submit">Criar</button>
                <a class="lc-btn lc-btn--secondary" href="/patients">Voltar</a>
            </div>
        </form>
    <?php else: ?>
        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <a class="lc-btn lc-btn--secondary" href="/patients">Voltar</a>
        </div>
    <?php endif; ?>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
