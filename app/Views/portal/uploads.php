<?php
$title = 'Uploads';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$uploads = $uploads ?? [];
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
<body class="lc-body">
<div class="lc-app" style="padding: 16px; max-width: 980px; margin: 0 auto;">
    <div class="lc-page__header">
        <div>
            <h1 class="lc-page__title">Enviar fotos</h1>
            <div class="lc-page__subtitle">Portal do Paciente</div>
        </div>
        <div class="lc-flex lc-gap-sm">
            <a class="lc-btn lc-btn--secondary" href="/portal">Dashboard</a>
            <a class="lc-btn lc-btn--secondary" href="/portal/documentos">Documentos</a>
            <form method="post" action="/portal/logout">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Sair</button>
            </form>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger" style="margin-top:12px;">
            <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Novo upload</div>
        <div class="lc-card__body">
            <form method="post" action="/portal/uploads" enctype="multipart/form-data" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />

                <label class="lc-label">Arquivo</label>
                <input class="lc-input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required />

                <div class="lc-grid">
                    <div>
                        <label class="lc-label">Tipo</label>
                        <select class="lc-select" name="kind">
                            <option value="other">Outro</option>
                            <option value="before">Antes</option>
                            <option value="after">Depois</option>
                        </select>
                    </div>
                    <div>
                        <label class="lc-label">Data (opcional)</label>
                        <input class="lc-input" type="datetime-local" name="taken_at" />
                    </div>
                </div>

                <label class="lc-label">Observação (opcional)</label>
                <input class="lc-input" type="text" name="note" />

                <button class="lc-btn lc-btn--primary" type="submit">Enviar</button>
            </form>

            <div style="margin-top:10px;" class="lc-alert lc-alert--info">
                As fotos passam por moderação antes de aparecerem em "Documentos".
            </div>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Seus uploads</div>
        <div class="lc-card__body">
            <?php if (!is_array($uploads) || $uploads === []): ?>
                <div>Nenhum upload enviado.</div>
            <?php else: ?>
                <div class="lc-table-wrap">
                    <table class="lc-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th>Observação</th>
                            <th>Criado em</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($uploads as $u): ?>
                            <tr>
                                <td><?= (int)($u['id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string)($u['kind'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($u['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($u['taken_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($u['note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($u['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
