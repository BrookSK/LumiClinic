<?php
$title = 'Anamnese';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$token = $token ?? '';
$template = $template ?? null;
$fields = $fields ?? [];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" href="/icone_1.png" />
    <link rel="stylesheet" href="/assets/css/design-system.css" />
</head>
<body class="lc-body lc-body--auth">
<div class="lc-auth lc-auth--compact">
    <div class="lc-auth__panel lc-auth__panel--compact">
        <div class="lc-auth__brand">
            <div class="lc-brand__logo" style="padding:0; background:#000;">
                <img src="/icone_1.png" alt="LumiClinic" style="width:100%; height:100%; object-fit:contain; border-radius:12px; display:block;" />
            </div>
            <div>
                <div class="lc-auth__title">Anamnese</div>
                <div class="lc-auth__subtitle" style="line-height:0; margin-top:6px;">
                    <span style="display:block; font-weight:900; font-size:16px; letter-spacing:0.2px; line-height:1;">LumiClinic</span>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="lc-alert lc-alert--success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (!$error && !$success && is_array($template)): ?>
            <div class="lc-card" style="margin: 12px 0; box-shadow:none;">
                <div class="lc-card__body">
                    <div style="font-weight:800; margin-bottom:6px;"><?= htmlspecialchars((string)($template['name'] ?? 'Anamnese'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="lc-muted">Preencha os campos abaixo e clique em enviar.</div>
                </div>
            </div>

            <form method="post" class="lc-form" action="/a/anamnese">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="token" value="<?= htmlspecialchars((string)$token, ENT_QUOTES, 'UTF-8') ?>" />

                <?php foreach ($fields as $f): ?>
                    <?php
                        $key = (string)($f['field_key'] ?? '');
                        $label = (string)($f['label'] ?? $key);
                        $type = (string)($f['field_type'] ?? 'text');
                        $opts = [];
                        if (isset($f['options_json']) && $f['options_json']) {
                            $decoded = json_decode((string)$f['options_json'], true);
                            if (is_array($decoded)) {
                                $opts = $decoded;
                            }
                        }
                    ?>

                    <label class="lc-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>

                    <?php if ($type === 'textarea'): ?>
                        <textarea class="lc-input" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" rows="4"></textarea>
                    <?php elseif ($type === 'checkbox'): ?>
                        <select class="lc-select" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                            <option value="0">Não</option>
                            <option value="1">Sim</option>
                        </select>
                    <?php elseif ($type === 'select'): ?>
                        <select class="lc-select" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                            <option value="">Selecione</option>
                            <?php foreach ($opts as $o): ?>
                                <option value="<?= htmlspecialchars((string)$o, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$o, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input class="lc-input" type="text" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" />
                    <?php endif; ?>
                <?php endforeach; ?>

                <button class="lc-btn lc-btn--primary" type="submit" style="width:100%; justify-content:center;">Enviar</button>
            </form>
        <?php endif; ?>

        <div style="margin-top: 12px;" class="lc-muted">Você pode fechar esta página após concluir.</div>
    </div>
</div>
</body>
</html>
