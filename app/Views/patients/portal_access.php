<?php
$title = 'Acesso ao Portal';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$patient_id = (int)($patient_id ?? 0);
$patient_user = $patient_user ?? null;
$reset_token = $reset_token ?? null;
ob_start();
?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:10px; flex-wrap:wrap;">
    <div class="lc-badge lc-badge--gold">Portal do Paciente</div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)$patient_id ?>">Voltar</a>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Acesso</div>

    <div class="lc-card__body">
        <?php if ($error): ?>
            <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="lc-alert lc-alert--success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px;">
            <div>
                <div class="lc-label">Status</div>
                <div><?= $patient_user ? 'Criado' : 'Não criado' ?></div>
            </div>
            <div>
                <div class="lc-label">E-mail</div>
                <div><?= htmlspecialchars((string)($patient_user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <form method="post" class="lc-form" action="/patients/portal-access/ensure" style="margin-top:14px;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="patient_id" value="<?= (int)$patient_id ?>" />

            <label class="lc-label">E-mail para login no Portal</label>
            <input class="lc-input" type="email" name="email" value="<?= htmlspecialchars((string)($patient_user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />

            <button class="lc-btn lc-btn--primary" type="submit">Criar/Atualizar acesso e gerar redefinição</button>
        </form>

        <?php if (is_string($reset_token) && $reset_token !== ''): ?>
            <div style="margin-top: 14px;">
                <div class="lc-alert lc-alert--info">
                    Token (DEV): <?= htmlspecialchars((string)$reset_token, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div style="margin-top: 8px;">
                    <a class="lc-btn lc-btn--secondary" href="/portal/reset?token=<?= htmlspecialchars((string)$reset_token, ENT_QUOTES, 'UTF-8') ?>" target="_blank">Abrir link de redefinição</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
