<?php
$title = 'Moderação de uploads';
$csrf = $_SESSION['_csrf'] ?? '';
$pending = $pending ?? [];
ob_start();
?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:10px; flex-wrap:wrap;">
    <div class="lc-badge lc-badge--gold">Moderação</div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="lc-btn lc-btn--secondary" href="/">Voltar</a>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Uploads pendentes (Portal)</div>
    <div class="lc-card__body">
        <?php if (!is_array($pending) || $pending === []): ?>
            <div>Nenhum upload pendente.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Paciente</th>
                        <th>Tipo</th>
                        <th>Arquivo</th>
                        <th>Observação</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pending as $u): ?>
                        <tr>
                            <td><?= (int)($u['id'] ?? 0) ?></td>
                            <td><?= (int)($u['patient_id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string)($u['kind'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($u['original_filename'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($u['note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($u['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="display:flex; gap:10px; flex-wrap:wrap;">
                                <form method="post" action="/medical-images/moderation/approve" class="lc-form">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= (int)($u['id'] ?? 0) ?>" />
                                    <input class="lc-input" type="text" name="note" placeholder="Obs (opcional)" />
                                    <button class="lc-btn lc-btn--primary" type="submit">Aprovar</button>
                                </form>

                                <form method="post" action="/medical-images/moderation/reject" class="lc-form">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= (int)($u['id'] ?? 0) ?>" />
                                    <input class="lc-input" type="text" name="note" placeholder="Motivo (opcional)" />
                                    <button class="lc-btn lc-btn--danger" type="submit">Rejeitar</button>
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
