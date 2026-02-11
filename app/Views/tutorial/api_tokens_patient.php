<?php
$title = 'Tutorial: API Token (Paciente)';
$baseUrl = '';
if (isset($_SERVER['HTTP_HOST'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = $scheme . '://' . (string)$_SERVER['HTTP_HOST'];
}
$baseUrl = rtrim((string)$baseUrl, '/');
$exampleBase = $baseUrl !== '' ? $baseUrl : 'https://sua-clinica.com';

$seo = isset($seo) && is_array($seo) ? $seo : [];
$seoSiteName = trim((string)($seo['site_name'] ?? ''));
$seoDefaultTitle = trim((string)($seo['default_title'] ?? ''));
$seoDescription = trim((string)($seo['meta_description'] ?? ''));
$seoOgImageUrl = trim((string)($seo['og_image_url'] ?? ''));
$seoFaviconUrl = trim((string)($seo['favicon_url'] ?? ''));

$computedTitle = trim((string)($title ?? ''));
if ($computedTitle === '') {
    $computedTitle = $seoDefaultTitle !== '' ? $seoDefaultTitle : 'LumiClinic';
}
if ($seoSiteName !== '' && !str_contains($computedTitle, $seoSiteName)) {
    $computedTitle = $computedTitle . ' - ' . $seoSiteName;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($computedTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <?php if ($seoDescription !== ''): ?>
        <meta name="description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>" />
    <?php endif; ?>
    <?php if ($seoFaviconUrl !== ''): ?>
        <link rel="icon" href="<?= htmlspecialchars($seoFaviconUrl, ENT_QUOTES, 'UTF-8') ?>" />
    <?php else: ?>
        <link rel="icon" href="/icone_1.png" />
    <?php endif; ?>
    <meta property="og:title" content="<?= htmlspecialchars($computedTitle, ENT_QUOTES, 'UTF-8') ?>" />
    <?php if ($seoDescription !== ''): ?>
        <meta property="og:description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>" />
    <?php endif; ?>
    <?php if ($seoOgImageUrl !== ''): ?>
        <meta property="og:image" content="<?= htmlspecialchars($seoOgImageUrl, ENT_QUOTES, 'UTF-8') ?>" />
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?= htmlspecialchars($computedTitle, ENT_QUOTES, 'UTF-8') ?>" />
    <?php if ($seoDescription !== ''): ?>
        <meta name="twitter:description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>" />
    <?php endif; ?>
    <?php if ($seoOgImageUrl !== ''): ?>
        <meta name="twitter:image" content="<?= htmlspecialchars($seoOgImageUrl, ENT_QUOTES, 'UTF-8') ?>" />
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/design-system.css" />
</head>
<body class="lc-body">
<div class="lc-app" style="padding: 16px; max-width: 980px; margin: 0 auto;">
    <div class="lc-page__header" style="gap:10px;">
        <div class="lc-flex lc-gap-sm" style="align-items:center;">
            <div class="lc-brand__logo" style="width:36px; height:36px; padding:0; background:#000; border-radius:10px; overflow:hidden;">
                <img src="/icone_1.png" alt="LumiClinic" style="width:100%; height:100%; object-fit:contain; display:block;" />
            </div>
            <div>
                <div class="lc-page__title" style="margin:0;">Tutorial</div>
                <div class="lc-page__subtitle" style="margin-top:2px;">API Token (Paciente)</div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <a class="lc-btn lc-btn--secondary" href="/portal/api-tokens">Voltar para API Tokens</a>
            <a class="lc-btn lc-btn--secondary" href="/portal">Portal do Paciente</a>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Para que serve o API Token?</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                O <strong>API Token</strong> é uma credencial que permite que você (paciente) faça requisições na API do sistema de forma segura, sem precisar fazer login com e-mail e senha.
            </div>
            <div style="margin-top:10px;">
                Use o token para:
            </div>
            <div style="margin-top:8px;">
                - Integrar seus dados com ferramentas pessoais (ex: planilhas, automações)
                <br />- Consultar seus dados por API (ex: dados básicos e próximas consultas)
            </div>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Como o token funciona</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                A API usa o header HTTP:
                <div style="margin-top:10px;">
                    <pre style="white-space:pre-wrap; background:#0b1220; color:#e5e7eb; padding:12px; border-radius:10px; overflow:auto;">Authorization: Bearer SEU_TOKEN_AQUI</pre>
                </div>
            </div>
            <div style="margin-top:12px;">
                Observações:
            </div>
            <div style="margin-top:8px;">
                - Guarde seu token em local seguro.
                <br />- Se você suspeitar que alguém viu o token, <strong>revogue</strong> e gere outro.
                <br />- O token é mostrado <strong>uma única vez</strong> quando você cria.
            </div>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Endpoints disponíveis</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div class="lc-alert lc-alert--info" style="margin-bottom:12px;">
                Os exemplos abaixo usam <strong><?= htmlspecialchars($exampleBase, ENT_QUOTES, 'UTF-8') ?></strong> como base URL.
            </div>

            <div style="font-weight:700; margin-top:8px;">1) Quem sou eu</div>
            <div class="lc-muted" style="margin-top:4px;">Retorna dados básicos do seu contexto (paciente/clinic).</div>
            <pre style="white-space:pre-wrap; background:#0b1220; color:#e5e7eb; padding:12px; border-radius:10px; overflow:auto;">GET <?= htmlspecialchars($exampleBase, ENT_QUOTES, 'UTF-8') ?>/api/v1/me</pre>

            <div style="font-weight:700; margin-top:16px;">2) Próximas consultas</div>
            <div class="lc-muted" style="margin-top:4px;">Lista consultas futuras vinculadas a você.</div>
            <pre style="white-space:pre-wrap; background:#0b1220; color:#e5e7eb; padding:12px; border-radius:10px; overflow:auto;">GET <?= htmlspecialchars($exampleBase, ENT_QUOTES, 'UTF-8') ?>/api/v1/appointments/upcoming</pre>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Exemplos prontos de requisição</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div style="font-weight:700;">cURL</div>
            <pre style="white-space:pre-wrap; background:#0b1220; color:#e5e7eb; padding:12px; border-radius:10px; overflow:auto;">curl -X GET "<?= htmlspecialchars($exampleBase, ENT_QUOTES, 'UTF-8') ?>/api/v1/me" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -H "Accept: application/json"</pre>

            <div style="font-weight:700; margin-top:16px;">JavaScript (fetch)</div>
            <pre style="white-space:pre-wrap; background:#0b1220; color:#e5e7eb; padding:12px; border-radius:10px; overflow:auto;">const token = 'SEU_TOKEN_AQUI';

const res = await fetch('<?= htmlspecialchars($exampleBase, ENT_QUOTES, 'UTF-8') ?>/api/v1/appointments/upcoming', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

if (!res.ok) {
  const body = await res.text();
  throw new Error(`HTTP ${res.status}: ${body}`);
}

const data = await res.json();
console.log(data);</pre>

            <div style="font-weight:700; margin-top:16px;">PHP</div>
            <pre style="white-space:pre-wrap; background:#0b1220; color:#e5e7eb; padding:12px; border-radius:10px; overflow:auto;">$token = 'SEU_TOKEN_AQUI';
$url = '<?= htmlspecialchars($exampleBase, ENT_QUOTES, 'UTF-8') ?>/api/v1/me';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Authorization: Bearer ' . $token,
  'Accept: application/json',
]);

$out = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($out === false) {
  throw new RuntimeException($err ?: 'Erro desconhecido');
}

if ($code < 200 || $code >= 300) {
  throw new RuntimeException('HTTP ' . $code . ': ' . $out);
}

echo $out;</pre>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Erros comuns</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div style="font-weight:700;">401 unauthorized</div>
            <div class="lc-muted" style="margin-top:4px;">Acontece quando o token não foi enviado, está incorreto, ou foi revogado/expirou.</div>

            <div style="font-weight:700; margin-top:12px;">404 not found</div>
            <div class="lc-muted" style="margin-top:4px;">Endpoint inexistente. Confira a URL.</div>

            <div style="font-weight:700; margin-top:12px;">Dica</div>
            <div class="lc-muted" style="margin-top:4px;">Se precisar trocar o token, volte em <strong>Portal do Paciente → Conta → API Tokens</strong> e gere outro.</div>
        </div>
    </div>

    <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>
