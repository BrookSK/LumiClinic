<?php
$title = 'Editar paciente';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$patient = $patient ?? null;
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

$ro = $can('patients.update') ? '' : 'disabled';

$addressText = (string)($patient['address'] ?? '');
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
    <div class="lc-card__title">Edição</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/patients/edit">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="id" value="<?= (int)($patient['id'] ?? 0) ?>" />

        <label class="lc-label">Nome</label>
        <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required <?= $ro ?> />

        <div class="lc-grid">
            <div>
                <label class="lc-label">E-mail</label>
                <input class="lc-input" type="email" name="email" value="<?= htmlspecialchars((string)($patient['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $ro ?> />
            </div>
            <div>
                <label class="lc-label">Telefone</label>
                <input class="lc-input" type="text" name="phone" value="<?= htmlspecialchars((string)($patient['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $ro ?> />
            </div>
        </div>

        <label class="lc-label" style="margin-top:10px;">WhatsApp</label>
        <?php $waOptIn = (int)($patient['whatsapp_opt_in'] ?? 1); ?>
        <label class="lc-checkbox" style="display:flex; gap:8px; align-items:center;">
            <input type="checkbox" name="whatsapp_opt_in" value="1" <?= $waOptIn ? 'checked' : '' ?> <?= $ro ?> />
            <span>Receber lembretes por WhatsApp</span>
        </label>

        <div class="lc-grid">
            <div>
                <label class="lc-label">Data de nascimento</label>
                <input class="lc-input" type="date" name="birth_date" value="<?= htmlspecialchars((string)($patient['birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $ro ?> />
            </div>
            <div>
                <label class="lc-label">Sexo</label>
                <?php $sex = (string)($patient['sex'] ?? ''); ?>
                <select class="lc-select" name="sex" <?= $ro ?>>
                    <option value="" <?= $sex === '' ? 'selected' : '' ?>>Selecione</option>
                    <option value="female" <?= $sex === 'female' ? 'selected' : '' ?>>Feminino</option>
                    <option value="male" <?= $sex === 'male' ? 'selected' : '' ?>>Masculino</option>
                    <option value="other" <?= $sex === 'other' ? 'selected' : '' ?>>Outro</option>
                </select>
            </div>
        </div>

        <label class="lc-label">CPF</label>
        <input class="lc-input" type="text" name="cpf" value="<?= htmlspecialchars((string)($patient['cpf'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $ro ?> />

        <label class="lc-label">Profissional de referência</label>
        <?php $currentRef = (int)($patient['reference_professional_id'] ?? 0); ?>
        <select class="lc-select" name="reference_professional_id" <?= $ro ?>>
            <option value="" <?= $currentRef === 0 ? 'selected' : '' ?>>Nenhum</option>
            <?php foreach ($professionals as $pr): ?>
                <option value="<?= (int)$pr['id'] ?>" <?= (int)$pr['id'] === $currentRef ? 'selected' : '' ?>><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <label class="lc-label">Origem do paciente</label>
        <?php $currentOrigin = (int)($patient['patient_origin_id'] ?? 0); ?>
        <select class="lc-select" name="patient_origin_id" <?= $ro ?>>
            <option value="" <?= $currentOrigin === 0 ? 'selected' : '' ?>>(opcional)</option>
            <?php foreach (($patientOrigins ?? []) as $o): ?>
                <?php $oid = (int)($o['id'] ?? 0); ?>
                <option value="<?= $oid ?>" <?= ($oid > 0 && $oid === $currentOrigin) ? 'selected' : '' ?>><?= htmlspecialchars((string)($o['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($can('settings.update')): ?>
            <a href="/settings/operational" style="font-size:11px;color:rgba(99,102,241,.7);margin-top:4px;display:inline-block;">⚙ Gerenciar origens</a>
        <?php else: ?>
            <div style="font-size:10px;color:#9ca3af;margin-top:4px;">Origens são cadastradas em Configurações → Operacional</div>
        <?php endif; ?>

        <label class="lc-label">Endereço</label>
        <div class="lc-grid lc-gap-grid">
            <div>
                <label class="lc-label">Rua</label>
                <input class="lc-input" type="text" name="address_street" value="<?= htmlspecialchars($address_street, ENT_QUOTES, 'UTF-8') ?>" <?= $ro ?> />
            </div>
            <div>
                <label class="lc-label">Número</label>
                <input class="lc-input" type="text" name="address_number" value="<?= htmlspecialchars($address_number, ENT_QUOTES, 'UTF-8') ?>" <?= $ro ?> />
            </div>
        </div>
        <div class="lc-grid lc-gap-grid">
            <div>
                <label class="lc-label">Complemento</label>
                <input class="lc-input" type="text" name="address_complement" value="<?= htmlspecialchars($address_complement, ENT_QUOTES, 'UTF-8') ?>" <?= $ro ?> />
            </div>
            <div>
                <label class="lc-label">Bairro</label>
                <input class="lc-input" type="text" name="address_district" value="<?= htmlspecialchars($address_district, ENT_QUOTES, 'UTF-8') ?>" <?= $ro ?> />
            </div>
        </div>
        <div class="lc-grid lc-gap-grid">
            <div>
                <label class="lc-label">Cidade</label>
                <input class="lc-input" type="text" name="address_city" value="<?= htmlspecialchars($address_city, ENT_QUOTES, 'UTF-8') ?>" <?= $ro ?> />
            </div>
            <div>
                <label class="lc-label">UF</label>
                <input class="lc-input" type="text" name="address_state" maxlength="2" placeholder="SP" value="<?= htmlspecialchars($address_state, ENT_QUOTES, 'UTF-8') ?>" <?= $ro ?> />
            </div>
        </div>
        <div>
            <label class="lc-label">CEP</label>
            <input class="lc-input" type="text" name="address_zip" placeholder="00000-000" value="<?= htmlspecialchars($address_zip, ENT_QUOTES, 'UTF-8') ?>" <?= $ro ?> />
        </div>

        <label class="lc-label">Observações</label>
        <textarea class="lc-input" name="notes" rows="4" <?= $ro ?>><?= htmlspecialchars((string)($patient['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

        <label class="lc-label">Status</label>
        <?php $status = (string)($patient['status'] ?? 'active'); ?>
        <select class="lc-select" name="status" <?= $ro ?>>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativo</option>
            <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>Desativado</option>
        </select>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <?php if ($can('patients.update')): ?>
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <?php endif; ?>
            <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)($patient['id'] ?? 0) ?>">Voltar</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
