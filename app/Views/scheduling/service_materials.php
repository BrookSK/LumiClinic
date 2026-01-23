<?php

/** @var array<string,mixed> $service */
/** @var list<array<string,mixed>> $materials */
/** @var list<array<string,mixed>> $defaults */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Materiais do serviço';

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-card" style="margin-bottom: 16px; border-left: 4px solid #b91c1c;">
        <div class="lc-card__body"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Serviço</div>
    <div class="lc-card__body" style="display:flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
        <div>
            <div><strong><?= htmlspecialchars((string)$service['name'], ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div class="lc-muted">Padrões de consumo por sessão</div>
        </div>
        <div>
            <a class="lc-btn lc-btn--secondary" href="/services">Voltar</a>
        </div>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Adicionar material padrão</div>
    <div class="lc-card__body">
        <form method="post" action="/services/materials/create" class="lc-form" style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap: 12px; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="service_id" value="<?= (int)$service['id'] ?>" />

            <div class="lc-field">
                <label class="lc-label">Material</label>
                <select class="lc-select" name="material_id">
                    <?php foreach ($materials as $m): ?>
                        <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars((string)$m['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)$m['unit'], ENT_QUOTES, 'UTF-8') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Qtd por sessão</label>
                <input class="lc-input" type="text" name="quantity_per_session" required />
            </div>

            <div style="grid-column: 1 / -1;">
                <button class="lc-btn" type="submit">Salvar</button>
            </div>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Materiais padrão</div>
    <div class="lc-card__body">
        <?php if ($defaults === []): ?>
            <div class="lc-muted">Nenhum material padrão definido.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Material</th>
                    <th>Qtd</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($defaults as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$d['material_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$d['quantity_per_session'], 3, ',', '.') ?> <?= htmlspecialchars((string)$d['material_unit'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align:right;">
                            <form method="post" action="/services/materials/delete" style="display:inline;" onsubmit="return confirm('Remover este material padrão?');">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="service_id" value="<?= (int)$service['id'] ?>" />
                                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>" />
                                <button class="lc-btn lc-btn--secondary" type="submit">Remover</button>
                            </form>
                        </td>
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
