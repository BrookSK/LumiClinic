<?php
$title = 'Meu Perfil';
$patient = $patient ?? null;
$clinic = $clinic ?? null;
$csrf = $_SESSION['_csrf'] ?? '';
$pending = $pending_request ?? null;
$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);
$me = $me ?? null;

$pendingPayload = [];
if (!empty($pending) && isset($pending['requested_fields_json'])) {
    $decoded = json_decode((string)$pending['requested_fields_json'], true);
    if (is_array($decoded)) $pendingPayload = $decoded;
}
$pendingAddr = isset($pendingPayload['address_parts']) && is_array($pendingPayload['address_parts']) ? $pendingPayload['address_parts'] : [];

ob_start();
?>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:18px;">Meu perfil</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Dados atuais -->
<div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:12px;">Seus dados</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div><span style="font-size:12px;color:rgba(31,41,55,.45);">Nome</span><div style="font-weight:700;margin-top:2px;"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div>
        <div><span style="font-size:12px;color:rgba(31,41,55,.45);">E-mail</span><div style="font-weight:700;margin-top:2px;"><?= htmlspecialchars((string)($patient['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div>
        <div><span style="font-size:12px;color:rgba(31,41,55,.45);">Telefone</span><div style="font-weight:700;margin-top:2px;"><?= htmlspecialchars((string)($patient['phone'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div></div>
        <div><span style="font-size:12px;color:rgba(31,41,55,.45);">Nascimento</span><div style="font-weight:700;margin-top:2px;"><?php $bd = trim((string)($patient['birth_date'] ?? '')); echo $bd !== '' ? htmlspecialchars(date('d/m/Y', strtotime($bd)), ENT_QUOTES, 'UTF-8') : '—'; ?></div></div>
    </div>

    <?php if (!empty($pending)): ?>
        <div style="margin-top:12px;padding:10px 12px;border-radius:10px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.08);font-size:13px;color:rgba(31,41,55,.70);">
            ⏳ Você tem uma solicitação de alteração pendente (enviada em <?= htmlspecialchars((string)($pending['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>).
        </div>
    <?php endif; ?>
</div>

<!-- Solicitar alteração -->
<details style="margin-bottom:16px;">
    <summary style="padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);cursor:pointer;list-style:none;font-weight:750;font-size:14px;color:rgba(31,41,55,.90);display:flex;align-items:center;justify-content:space-between;">
        Solicitar alteração de dados
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" style="color:rgba(31,41,55,.35);"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <div style="margin-top:8px;padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-bottom:12px;">As alterações serão enviadas para a clínica revisar e aprovar.</div>
        <form method="post" action="/portal/perfil/request-change">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="lc-field"><label class="lc-label">Nome</label><input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($pendingPayload['name'] ?? ($patient['name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" required /></div>
                <div class="lc-field"><label class="lc-label">E-mail</label><input class="lc-input" type="email" name="email" value="<?= htmlspecialchars((string)($pendingPayload['email'] ?? ($patient['email'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" /></div>
                <div class="lc-field"><label class="lc-label">Telefone</label><input class="lc-input" type="text" name="phone" value="<?= htmlspecialchars((string)($pendingPayload['phone'] ?? ($patient['phone'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" /></div>
                <div class="lc-field"><label class="lc-label">Nascimento</label><input class="lc-input" type="date" name="birth_date" value="<?= htmlspecialchars((string)($pendingPayload['birth_date'] ?? ($patient['birth_date'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" /></div>
            </div>
            <div style="margin-top:12px;"><button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Enviar solicitação</button></div>
        </form>
    </div>
</details>

<!-- Trocar senha -->
<details style="margin-bottom:16px;">
    <summary style="padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);cursor:pointer;list-style:none;font-weight:750;font-size:14px;color:rgba(31,41,55,.90);display:flex;align-items:center;justify-content:space-between;">
        Alterar senha
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" style="color:rgba(31,41,55,.35);"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <div style="margin-top:8px;padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
        <form method="post" action="/portal/perfil/change-password">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;max-width:500px;">
                <div class="lc-field"><label class="lc-label">Senha atual</label><input class="lc-input" type="password" name="current_password" required /></div>
                <div class="lc-field"><label class="lc-label">Nova senha</label><input class="lc-input" type="password" name="new_password" required /></div>
            </div>
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit" style="margin-top:12px;">Alterar senha</button>
        </form>
    </div>
</details>

<!-- Dados da clínica -->
<?php if ($clinic): ?>
<div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
    <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:12px;">Sua clínica</div>
    <div style="font-weight:700;font-size:15px;margin-bottom:8px;"><?= htmlspecialchars((string)($clinic['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;color:rgba(31,41,55,.60);">
        <?php if (!empty($clinic['contact_phone'])): ?><div>📞 <?= htmlspecialchars((string)$clinic['contact_phone'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if (!empty($clinic['contact_whatsapp'])): ?><div>💬 <?= htmlspecialchars((string)$clinic['contact_whatsapp'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if (!empty($clinic['contact_email'])): ?><div>✉️ <?= htmlspecialchars((string)$clinic['contact_email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if (!empty($clinic['contact_website'])): ?><div>🌐 <?= htmlspecialchars((string)$clinic['contact_website'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'perfil';
require __DIR__ . '/_shell.php';
