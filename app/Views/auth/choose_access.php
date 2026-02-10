<?php
/** @var string $email */
/** @var list<array<string,mixed>> $options */

$title = 'Escolher acesso';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;

ob_start();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/design-system.css" />
</head>
<body class="lc-body lc-body--auth">
<div class="lc-auth lc-auth--compact">
    <div class="lc-auth__panel lc-auth__panel--compact">
        <div class="lc-auth__brand">
            <div class="lc-brand__logo">LC</div>
            <div>
                <div class="lc-auth__title">Escolha como entrar</div>
                <div class="lc-auth__subtitle"><?= htmlspecialchars($email !== '' ? $email : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <?php if (is_string($error) && trim($error) !== ''): ?>
            <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="/choose-access" class="lc-form" autocomplete="off">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-flex" style="flex-direction:column; gap:10px;">
                <?php foreach ($options as $opt): ?>
                    <?php
                        $kind = (string)($opt['kind'] ?? '');
                        $id = (int)($opt['id'] ?? 0);
                        $label = (string)($opt['label'] ?? '');
                        $meta = (string)($opt['meta'] ?? '');
                        $value = $kind . ':' . $id;
                    ?>
                    <label class="lc-card" style="margin:0; box-shadow:none; cursor:pointer;">
                        <div class="lc-flex lc-gap-sm" style="align-items:flex-start;">
                            <input type="radio" name="choice" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" style="margin-top:4px;" />
                            <div>
                                <div style="font-weight:800; color: rgba(31,41,55,0.95);">
                                    <?= htmlspecialchars($label !== '' ? $label : 'Acesso', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <?php if (trim($meta) !== ''): ?>
                                    <div class="lc-muted" style="margin-top:2px;">
                                        <?= htmlspecialchars($meta, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <input type="hidden" name="kind" value="" />
            <input type="hidden" name="id" value="" />

            <button class="lc-btn lc-btn--primary" type="submit" style="margin-top:12px; width:100%;">Entrar</button>
        </form>

        <div style="margin-top: 10px;">
            <a class="lc-link" href="/login">Voltar</a>
        </div>
    </div>
</div>

<script>
(function(){
  var form = document.querySelector('form[action="/choose-access"]');
  if (!form) return;

  form.addEventListener('submit', function(e){
    var checked = form.querySelector('input[name="choice"]:checked');
    if (!checked) {
      e.preventDefault();
      return;
    }
    var parts = (checked.value || '').split(':');
    form.querySelector('input[name="kind"]').value = parts[0] || '';
    form.querySelector('input[name="id"]').value = parts[1] || '';
  });
})();
</script>
</body>
</html>
<?php
$content = (string)ob_get_clean();
echo $content;
