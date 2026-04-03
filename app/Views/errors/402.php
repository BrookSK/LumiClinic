<?php
$title = 'Assinatura pendente';
$csrf = $_SESSION['_csrf'] ?? '';
$support_whatsapp_number = isset($support_whatsapp_number) ? trim((string)$support_whatsapp_number) : '';
$support_email = isset($support_email) ? trim((string)$support_email) : '';

$whatsappDigits = preg_replace('/\D+/', '', $support_whatsapp_number) ?? '';
$whatsappUrl = $whatsappDigits !== '' ? ('https://wa.me/' . $whatsappDigits) : '';
$mailUrl = ($support_email !== '' && filter_var($support_email, FILTER_VALIDATE_EMAIL)) ? ('mailto:' . $support_email) : '';

$isOwner = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) && in_array('owner', $_SESSION['role_codes'], true);

ob_start();
?>

<div style="max-width:560px;margin:40px auto;text-align:center;">
    <div style="font-size:48px;margin-bottom:16px;">🔒</div>
    <div style="font-weight:850;font-size:22px;color:rgba(31,41,55,.96);margin-bottom:8px;">Acesso bloqueado</div>
    <div style="font-size:14px;color:rgba(31,41,55,.55);line-height:1.6;margin-bottom:24px;">
        O acesso ao sistema foi temporariamente bloqueado porque a assinatura da sua clínica está pendente ou vencida.
    </div>

    <?php if ($isOwner): ?>
    <div style="padding:18px;border-radius:14px;border:1px solid rgba(238,184,16,.30);background:rgba(253,229,159,.12);margin-bottom:20px;text-align:left;">
        <div style="font-weight:700;font-size:14px;color:rgba(129,89,1,1);margin-bottom:8px;">O que fazer?</div>
        <div style="font-size:13px;color:rgba(31,41,55,.65);line-height:1.6;">
            Acesse a página de assinatura para verificar o status do seu plano, pagar boletos pendentes ou entrar em contato com o suporte.
        </div>
        <div style="margin-top:12px;">
            <a class="lc-btn lc-btn--primary" href="/billing/subscription" style="width:100%;justify-content:center;">Ver minha assinatura</a>
        </div>
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
        <?php if ($whatsappUrl !== ''): ?>
            <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars($whatsappUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">💬 WhatsApp</a>
        <?php endif; ?>
        <?php if ($mailUrl !== ''): ?>
            <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars($mailUrl, ENT_QUOTES, 'UTF-8') ?>">✉️ E-mail</a>
        <?php endif; ?>
        <form method="post" action="/logout" style="margin:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <button class="lc-btn lc-btn--secondary" type="submit">Sair</button>
        </form>
    </div>

    <?php if (!$isOwner): ?>
    <div style="margin-top:16px;font-size:13px;color:rgba(31,41,55,.45);line-height:1.5;">
        Entre em contato com o dono da clínica para regularizar a assinatura.
    </div>
    <?php endif; ?>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
