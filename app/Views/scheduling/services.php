<?php
/** @var list<array<string,mixed>> $items */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Serviços';

ob_start();
?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Novo serviço</div>
    <div class="lc-card__body">
        <form method="post" action="/services/create" class="lc-form" style="display:grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr; gap: 12px; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Nome</label>
                <input class="lc-input" type="text" name="name" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Duração (min)</label>
                <input class="lc-input" type="number" name="duration_minutes" min="5" step="5" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Buffer antes (min)</label>
                <input class="lc-input" type="number" name="buffer_before_minutes" min="0" step="5" value="0" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Buffer depois (min)</label>
                <input class="lc-input" type="number" name="buffer_after_minutes" min="0" step="5" value="0" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Preço (centavos)</label>
                <input class="lc-input" type="number" name="price_cents" min="0" step="1" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Profissional específico?</label>
                <select class="lc-select" name="allow_specific_professional">
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
    <div class="lc-card__header">Serviços</div>
    <div class="lc-card__body">
        <?php if ($items === []): ?>
            <div class="lc-muted">Nenhum serviço cadastrado.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Duração</th>
                    <th>Buffer</th>
                    <th>Preço</th>
                    <th>Específico</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$it['duration_minutes'] ?> min</td>
                        <td><?= (int)($it['buffer_before_minutes'] ?? 0) ?> / <?= (int)($it['buffer_after_minutes'] ?? 0) ?> min</td>
                        <td><?= $it['price_cents'] === null ? '-' : (int)$it['price_cents'] ?></td>
                        <td><?= ((int)$it['allow_specific_professional'] === 1) ? 'Sim' : 'Não' ?></td>
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
