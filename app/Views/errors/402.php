<?php
$title = 'Acesso temporariamente bloqueado';
$csrf = $_SESSION['_csrf'] ?? '';
$support_whatsapp_number = isset($support_whatsapp_number) ? trim((string)$support_whatsapp_number) : '';
$support_email = isset($support_email) ? trim((string)$support_email) : '';

$whatsappDigits = preg_replace('/\D+/', '', $support_whatsapp_number);
$whatsappDigits = $whatsappDigits === null ? '' : $whatsappDigits;
$whatsappUrl = $whatsappDigits !== '' ? ('https://wa.me/' . $whatsappDigits) : '';

$mailUrl = '';
if ($support_email !== '' && filter_var($support_email, FILTER_VALIDATE_EMAIL)) {
    $mailUrl = 'mailto:' . $support_email;
}
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Assinatura pendente</div>
    <div class="lc-muted" style="line-height:1.55;">
        O acesso ao sistema foi temporariamente bloqueado porque a assinatura da sua clínica está pendente.
        <br />
        Para voltar a usar o sistema, entre em contato com o suporte para regularizar.
    </div>

    <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
        <?php if ($whatsappUrl !== ''): ?>
            <a class="lc-btn lc-btn--primary" href="<?= htmlspecialchars($whatsappUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
        <?php endif; ?>
        <?php if ($mailUrl !== ''): ?>
            <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars($mailUrl, ENT_QUOTES, 'UTF-8') ?>">Enviar e-mail</a>
        <?php endif; ?>
        <form method="post" action="/logout" style="margin:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <button class="lc-btn lc-btn--primary" type="submit">Sair</button>
        </form>
        <a class="lc-btn lc-btn--secondary" href="/">Tentar novamente</a>
    </div>

    <?php if ($whatsappUrl === '' && $mailUrl === ''): ?>
        <div class="lc-muted" style="margin-top:10px; font-size:12px; line-height:1.55;">
            O contato do suporte ainda não foi configurado.
        </div>
    <?php endif; ?>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
