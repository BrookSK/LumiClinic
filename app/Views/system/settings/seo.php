<?php
$title = 'Admin do Sistema';
$csrf = $_SESSION['_csrf'] ?? '';

$site_name = isset($site_name) ? (string)$site_name : '';
$default_title = isset($default_title) ? (string)$default_title : '';
$meta_description = isset($meta_description) ? (string)$meta_description : '';
$og_image_url = isset($og_image_url) ? (string)$og_image_url : '';
$favicon_url = isset($favicon_url) ? (string)$favicon_url : '';

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Configurações (SEO)</div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/sys/settings/billing">Cobrança</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/settings/mail">E-mail</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Clínicas</a>
    </div>
</div>

<div class="lc-card lc-card--soft">
    <div class="lc-card__header">
        <div class="lc-card__title">Dados para buscadores e compartilhamento</div>
    </div>
    <div class="lc-card__body">
        <form method="post" action="/sys/settings/seo" class="lc-form lc-grid lc-grid--2 lc-gap-grid">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Nome do site</label>
                <input class="lc-input" type="text" name="site_name" value="<?= htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: LumiClinic" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Título padrão</label>
                <input class="lc-input" type="text" name="default_title" value="<?= htmlspecialchars($default_title, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: Sistema de gestão clínica" />
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Descrição do site</label>
                <textarea class="lc-textarea" name="meta_description" rows="3" placeholder="Uma descrição curta que aparece no Google e ao compartilhar links."><?= htmlspecialchars($meta_description, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Imagem para compartilhar (link)</label>
                <input class="lc-input" type="text" name="og_image_url" value="<?= htmlspecialchars($og_image_url, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://.../imagem.jpg" />
                <div class="lc-muted" style="font-size:12px; margin-top:6px;">Use uma imagem pública (ex.: 1200x630) para aparecer no WhatsApp/Instagram/Facebook.</div>
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Ícone do navegador (favicon) (link)</label>
                <input class="lc-input" type="text" name="favicon_url" value="<?= htmlspecialchars($favicon_url, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://.../favicon.ico" />
                <div class="lc-muted" style="font-size:12px; margin-top:6px;">Pode ser .ico ou .png. Precisa ser um link público.</div>
            </div>

            <div class="lc-flex lc-flex--end lc-gap-sm" style="grid-column: 1 / -1;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
