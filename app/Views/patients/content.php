<?php
$title = 'Conteúdos do paciente';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$contents = $contents ?? [];
ob_start();
?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:10px; flex-wrap:wrap;">
    <div class="lc-badge lc-badge--gold">Conteúdos</div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="lc-btn lc-btn--secondary" href="/patients">Pacientes</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Cadastrar conteúdo (link)</div>
    <div class="lc-card__body">
        <form method="post" action="/patients/content/create" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <label class="lc-label">Tipo</label>
            <select class="lc-select" name="type">
                <option value="link">Link</option>
                <option value="pdf">PDF (estrutura)</option>
                <option value="video">Vídeo</option>
            </select>

            <label class="lc-label">Título</label>
            <input class="lc-input" type="text" name="title" required />

            <label class="lc-label">Descrição (opcional)</label>
            <input class="lc-input" type="text" name="description" />

            <label class="lc-label">URL (opcional)</label>
            <input class="lc-input" type="text" name="url" />

            <label class="lc-label">Procedure type (opcional)</label>
            <input class="lc-input" type="text" name="procedure_type" />

            <label class="lc-label">Audience (opcional)</label>
            <input class="lc-input" type="text" name="audience" />

            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Conteúdos ativos</div>
    <div class="lc-card__body">
        <?php if (!is_array($contents) || $contents === []): ?>
            <div>Nenhum conteúdo.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Título</th>
                        <th>URL</th>
                        <th>Conceder ao paciente</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($contents as $c): ?>
                        <tr>
                            <td><?= (int)($c['id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string)($c['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($c['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($c['url'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="post" action="/patients/content/grant" class="lc-form" style="display:flex; gap:8px; align-items:flex-end;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="content_id" value="<?= (int)($c['id'] ?? 0) ?>" />
                                    <input class="lc-input" type="number" name="patient_id" min="1" placeholder="patient_id" required />
                                    <button class="lc-btn lc-btn--secondary" type="submit">Conceder</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
