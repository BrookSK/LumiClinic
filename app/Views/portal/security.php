<?php
$title = 'Segurança';
$csrf = $_SESSION['_csrf'] ?? '';
$success = $success ?? null;
$error = $error ?? null;
$me = $me ?? null;
$reset_url = $reset_url ?? null;

ob_start();
?>
<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-top:12px;">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-top:12px;">
        <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-top:16px; padding:16px;">
    <div class="lc-card__title">Acesso</div>
    <div class="lc-card__body">
        <div class="lc-field" style="max-width: 760px;">
            <label class="lc-label">E-mail de login</label>
            <input class="lc-input" type="text" value="<?= htmlspecialchars((string)($me['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" readonly />
        </div>

        <form method="post" action="/portal/seguranca/reset" style="margin-top:12px;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <button class="lc-btn lc-btn--primary" type="submit">Enviar link para redefinir minha senha</button>
        </form>

        <?php if (is_string($reset_url) && trim($reset_url) !== ''): ?>
            <div class="lc-field" style="margin-top:14px; max-width: 760px;">
                <label class="lc-label">Link (caso você precise copiar)</label>
                <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center;">
                    <input class="lc-input" type="text" id="lcPortalResetLink" value="<?= htmlspecialchars((string)$reset_url, ENT_QUOTES, 'UTF-8') ?>" readonly style="flex:1; min-width: 260px;" />
                    <button class="lc-btn lc-btn--secondary" type="button" id="lcCopyPortalResetLink">Copiar link</button>
                    <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars((string)$reset_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank">Abrir</a>
                </div>
            </div>

            <script>
            (function(){
              try {
                var btn = document.getElementById('lcCopyPortalResetLink');
                var input = document.getElementById('lcPortalResetLink');
                if (!btn || !input) return;
                btn.addEventListener('click', async function(){
                  try {
                    var value = input.value || '';
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                      await navigator.clipboard.writeText(value);
                    } else {
                      input.focus();
                      input.select();
                      document.execCommand('copy');
                    }
                    btn.textContent = 'Copiado';
                    setTimeout(function(){ btn.textContent = 'Copiar link'; }, 1600);
                  } catch (e) {}
                });
              } catch (e) {}
            })();
            </script>
        <?php endif; ?>
    </div>
</div>
<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'seguranca';
require __DIR__ . '/_shell.php';
