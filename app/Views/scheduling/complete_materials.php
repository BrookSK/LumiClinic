<?php

/** @var array<string,mixed> $appointment */
/** @var array<string,mixed> $service */
/** @var list<array<string,mixed>> $defaults */
/** @var list<array<string,mixed>> $materials */
/** @var array<int,string> $used_qty */
/** @var string|null $date */
/** @var string|null $view */
/** @var int|null $professional_id */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Finalizar sessão - Materiais';

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-card lc-statusbar lc-statusbar--no_show" style="margin-bottom: 16px;">
        <div class="lc-card__body"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Finalizar sessão</div>
    <div class="lc-card__body">
        <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-md">
            <div>
                <div><strong><?= htmlspecialchars((string)$service['name'], ENT_QUOTES, 'UTF-8') ?></strong></div>
                <div class="lc-muted">Confirme/ajuste o consumo de materiais e registre uma observação obrigatória.</div>
            </div>
            <div>
                <a class="lc-btn lc-btn--secondary" href="/schedule?date=<?= urlencode(substr((string)$appointment['start_at'], 0, 10)) ?>">Voltar</a>
            </div>
        </div>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Materiais consumidos</div>
    <div class="lc-card__body">
        <form method="post" action="/schedule/complete-materials" class="lc-form lc-grid" style="gap:12px;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= (int)$appointment['id'] ?>" />
            <input type="hidden" name="date" value="<?= htmlspecialchars((string)($date !== null && $date !== '' ? $date : substr((string)$appointment['start_at'], 0, 10)), ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="view" value="<?= htmlspecialchars((string)($view ?? 'day'), ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="professional_id" value="<?= (int)($professional_id ?? 0) ?>" />

            <?php if ($defaults === []): ?>
                <div class="lc-muted">Nenhum material padrão configurado para este serviço.</div>
            <?php else: ?>
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Material</th>
                        <th style="width:220px;">Qtd</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($defaults as $d): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars((string)$d['material_name'], ENT_QUOTES, 'UTF-8') ?>
                                <div class="lc-muted"><?= htmlspecialchars((string)$d['material_unit'], ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td>
                                <?php
                                    $mid = (int)$d['material_id'];
                                    $val = isset($used_qty[$mid]) ? (string)$used_qty[$mid] : number_format((float)$d['quantity_per_session'], 3, '.', '');
                                ?>
                                <input class="lc-input" type="text" name="qty[<?= (int)$d['material_id'] ?>]" value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>" />
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="lc-card" style="margin:0;">
                <div class="lc-card__header">Materiais extras</div>
                <div class="lc-card__body">
                    <div class="lc-muted" style="margin-bottom:10px;">Se precisar, adicione materiais usados fora do padrão.</div>
                    <table class="lc-table">
                        <thead>
                        <tr>
                            <th>Material</th>
                            <th style="width:220px;">Qtd</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php for ($i = 0; $i < 3; $i++): ?>
                            <tr>
                                <td>
                                    <select class="lc-select" name="extra_material_id[]">
                                        <option value="">-</option>
                                        <?php foreach ($materials as $m): ?>
                                            <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars((string)$m['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)$m['unit'], ENT_QUOTES, 'UTF-8') ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input class="lc-input" type="text" name="extra_qty[]" value="" />
                                </td>
                            </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="lc-field">
                <label class="lc-label">Observação (obrigatória)</label>
                <input class="lc-input" type="text" name="note" required />
            </div>

            <div>
                <button class="lc-btn" type="submit">Finalizar sessão</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
