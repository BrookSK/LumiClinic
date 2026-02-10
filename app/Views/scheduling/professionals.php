<?php
/** @var list<array<string,mixed>> $items */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Profissionais';

ob_start();
?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Novo profissional</div>
    <div class="lc-card__body">
        <form method="post" action="/professionals/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 2fr 1fr 1fr; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Nome</label>
                <input class="lc-input" type="text" name="name" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Especialidade</label>
                <input class="lc-input" type="text" name="specialty" />
            </div>

            <div class="lc-field">
                <label class="lc-label">User ID (opcional)</label>
                <input class="lc-input" type="number" name="user_id" min="1" step="1" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Agendamento online?</label>
                <select class="lc-select" name="allow_online_booking">
                    <option value="0">Não</option>
                    <option value="1">Sim</option>
                </select>
            </div>

            <div style="grid-column: 1 / -1;">
                <button class="lc-btn" type="submit">Salvar</button>
            </div>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Profissionais</div>
    <div class="lc-card__body">
        <?php if ($items === []): ?>
            <div class="lc-muted">Nenhum profissional cadastrado.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Especialidade</th>
                    <th>User</th>
                    <th>Online</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($it['specialty'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= ($it['user_id'] ?? null) === null ? '-' : (int)$it['user_id'] ?></td>
                        <td><?= ((int)$it['allow_online_booking'] === 1) ? 'Sim' : 'Não' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
