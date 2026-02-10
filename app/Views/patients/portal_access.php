<?php
$title = 'Acesso ao Portal';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$patient_id = (int)($patient_id ?? 0);
$patient_user = $patient_user ?? null;
$reset_token = $reset_token ?? null;
$reset_url = $reset_url ?? null;
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Portal do Paciente</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
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

        <div class="lc-grid lc-grid--2 lc-gap-grid">
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
                <?php
                $fullUrl = null;
                if (is_string($reset_url) && trim($reset_url) !== '') {
                    $fullUrl = trim($reset_url);
                } else {
                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
                    if ($host !== '') {
                        $fullUrl = $scheme . '://' . $host . '/portal/reset?token=' . (string)$reset_token;
                    } else {
                        $fullUrl = '/portal/reset?token=' . (string)$reset_token;
                    }
                }
                ?>

                <div class="lc-field" style="max-width: 760px;">
                    <label class="lc-label">Link de redefinição de senha</label>
                    <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center;">
                        <input class="lc-input" type="text" id="lcPortalResetLink" value="<?= htmlspecialchars((string)$fullUrl, ENT_QUOTES, 'UTF-8') ?>" readonly style="flex:1; min-width: 260px;" />
                        <button class="lc-btn lc-btn--secondary" type="button" id="lcCopyPortalResetLink">Copiar link</button>
                        <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars((string)$fullUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank">Abrir link</a>
                    </div>
                </div>

                <div style="margin-top: 8px;">
                    <div class="lc-muted" style="font-size:12px; line-height:1.5;">
                        Dica: copie o link e envie ao paciente por WhatsApp. O link expira em 1 hora.
                    </div>
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
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
