<?php
/** @var list<array<string,mixed>> $items */
/** @var list<array<string,mixed>> $procedures */
/** @var list<array<string,mixed>> $categories */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Serviços';

ob_start();
?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Novo serviço</div>
    <div class="lc-card__body">
        <form method="post" action="/services/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 2fr 2fr; align-items:end;">
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
                <label class="lc-label">Preço (R$)</label>
                <input class="lc-input" type="text" name="price" placeholder="0,00" inputmode="decimal" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Profissional específico?</label>
                <select class="lc-select" name="allow_specific_professional">
                    <option value="0">Não</option>
                    <option value="1">Sim</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Procedimento</label>
                <select class="lc-select" name="procedure_id">
                    <option value="">(nenhum)</option>
                    <?php foreach (($procedures ?? []) as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Categoria</label>
                <select class="lc-select" name="category_id">
                    <option value="">(nenhuma)</option>
                    <?php foreach (($categories ?? []) as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="lc-muted" style="margin-top:6px;"><a href="/services/categories">Gerenciar categorias</a></div>
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
                    <th>Categoria</th>
                    <th>Procedimento</th>
                    <th>Duração</th>
                    <th>Buffer</th>
                    <th>Preço</th>
                    <th>Específico</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($it['category_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($it['procedure_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$it['duration_minutes'] ?> min</td>
                        <td><?= (int)($it['buffer_before_minutes'] ?? 0) ?> / <?= (int)($it['buffer_after_minutes'] ?? 0) ?> min</td>
                        <td>
                            <?php
                                $pc = $it['price_cents'] ?? null;
                                $pc = $pc === null ? null : (int)$pc;
                                $display = $pc === null ? '-' : ('R$ ' . number_format(((float)$pc) / 100.0, 2, ',', '.'));
                            ?>
                            <?= htmlspecialchars($display, ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td><?= ((int)$it['allow_specific_professional'] === 1) ? 'Sim' : 'Não' ?></td>
                        <td style="text-align:right;">
                            <a class="lc-btn lc-btn--secondary" href="/services/materials?service_id=<?= (int)$it['id'] ?>">Materiais</a>
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
