<?php
/** @var array<string,mixed> $procedure */
/** @var list<array<string,mixed>> $protocols */
/** @var array<string,list<array<string,mixed>>> $steps_by_protocol */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Procedimento';

$error = is_string($error ?? null) ? (string)$error : '';
$success = is_string($success ?? null) ? (string)$success : '';

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
        return true;
    }

    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) {
        return false;
    }

    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) {
            return false;
        }
        return in_array($permissionCode, $permissions['allow'], true);
    }

    return in_array($permissionCode, $permissions, true);
};

$procedureId = (int)($procedure['id'] ?? 0);

ob_start();
?>

<div style="display:flex; gap:10px; align-items:center; justify-content:space-between; margin-bottom:12px;">
    <div>
        <div class="lc-muted" style="margin-bottom:4px;"><a href="/procedures">Procedimentos</a> / Editar</div>
        <div style="font-weight:800; font-size:18px;"><?= htmlspecialchars((string)($procedure['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="lc-card lc-statusbar lc-statusbar--no_show" style="margin-bottom:16px;">
        <div class="lc-card__body"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if ($success !== ''): ?>
    <div class="lc-card" style="margin-bottom:16px;">
        <div class="lc-card__body"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Dados do procedimento</div>
    <div class="lc-card__body">
        <?php if ($can('procedures.manage')): ?>
            <form method="post" action="/procedures/edit" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= (int)$procedureId ?>" />

                <div class="lc-field" style="grid-column: 1 / -1;">
                    <label class="lc-label">Nome</label>
                    <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($procedure['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />
                </div>

                <div class="lc-field" style="grid-column: 1 / -1;">
                    <label class="lc-label">Contraindicações</label>
                    <textarea class="lc-textarea" name="contraindications" rows="4"><?= htmlspecialchars((string)($procedure['contraindications'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="lc-field" style="grid-column: 1 / -1;">
                    <label class="lc-label">Orientações pré</label>
                    <textarea class="lc-textarea" name="pre_guidelines" rows="4"><?= htmlspecialchars((string)($procedure['pre_guidelines'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="lc-field" style="grid-column: 1 / -1;">
                    <label class="lc-label">Orientações pós</label>
                    <textarea class="lc-textarea" name="post_guidelines" rows="4"><?= htmlspecialchars((string)($procedure['post_guidelines'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Status</label>
                    <?php $st = (string)($procedure['status'] ?? 'active'); ?>
                    <select class="lc-select" name="status">
                        <option value="active" <?= $st === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="disabled" <?= $st === 'disabled' ? 'selected' : '' ?>>Desativado</option>
                    </select>
                </div>

                <div>
                    <label class="lc-label">Duração média real</label>
                    <div class="lc-muted" style="padding:10px 0;">
                        <?= isset($avg_real_duration_minutes) && $avg_real_duration_minutes !== null ? ((int)$avg_real_duration_minutes . ' min') : '-' ?>
                    </div>
                </div>

                <div style="grid-column: 1 / -1;">
                    <button class="lc-btn" type="submit">Salvar</button>
                </div>
            </form>
        <?php else: ?>
            <div class="lc-grid" style="grid-template-columns: 2fr 1fr; gap:12px;">
                <div style="grid-column: 1 / -1;">
                    <div class="lc-muted">Nome</div>
                    <div><?= htmlspecialchars((string)($procedure['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div style="grid-column: 1 / -1;">
                    <div class="lc-muted">Contraindicações</div>
                    <div><?= nl2br(htmlspecialchars((string)($procedure['contraindications'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
                </div>
                <div style="grid-column: 1 / -1;">
                    <div class="lc-muted">Orientações pré</div>
                    <div><?= nl2br(htmlspecialchars((string)($procedure['pre_guidelines'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
                </div>
                <div style="grid-column: 1 / -1;">
                    <div class="lc-muted">Orientações pós</div>
                    <div><?= nl2br(htmlspecialchars((string)($procedure['post_guidelines'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
                </div>
                <div>
                    <div class="lc-muted">Status</div>
                    <div><?= htmlspecialchars((string)($procedure['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div>
                    <div class="lc-muted">Duração média real</div>
                    <div><?= isset($avg_real_duration_minutes) && $avg_real_duration_minutes !== null ? ((int)$avg_real_duration_minutes . ' min') : '-' ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($can('procedures.manage')): ?>
    <div class="lc-card" style="margin-bottom: 16px;">
        <div class="lc-card__header">Novo protocolo</div>
        <div class="lc-card__body">
            <form method="post" action="/procedures/protocols/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr 1fr; align-items:end;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="procedure_id" value="<?= (int)$procedureId ?>" />

                <div class="lc-field">
                    <label class="lc-label">Nome</label>
                    <input class="lc-input" type="text" name="name" required />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Ordem</label>
                    <input class="lc-input" type="number" name="sort_order" value="0" step="1" />
                </div>

                <div>
                    <button class="lc-btn" type="submit">Criar</button>
                </div>

                <div class="lc-field" style="grid-column: 1 / -1;">
                    <label class="lc-label">Notas</label>
                    <textarea class="lc-textarea" name="notes" rows="3"></textarea>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__header">Protocolos</div>
    <div class="lc-card__body">
        <?php if ($protocols === []): ?>
            <div class="lc-muted">Nenhum protocolo cadastrado.</div>
        <?php else: ?>
            <?php foreach ($protocols as $p): ?>
                <?php
                    $pid = (int)$p['id'];
                    $steps = $steps_by_protocol[(string)$pid] ?? [];
                    $pst = (string)($p['status'] ?? 'active');
                ?>
                <div class="lc-card" style="margin-bottom:12px;">
                    <div class="lc-card__header" style="display:flex; gap:10px; align-items:center; justify-content:space-between;">
                        <div style="font-weight:800;"><?= htmlspecialchars((string)($p['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="lc-muted">#<?= (int)$pid ?></div>
                    </div>
                    <div class="lc-card__body">
                        <?php if ($can('procedures.manage')): ?>
                            <form method="post" action="/procedures/protocols/update" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr 1fr 1fr; align-items:end;">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="procedure_id" value="<?= (int)$procedureId ?>" />
                                <input type="hidden" name="id" value="<?= (int)$pid ?>" />

                                <div class="lc-field">
                                    <label class="lc-label">Nome</label>
                                    <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($p['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />
                                </div>

                                <div class="lc-field">
                                    <label class="lc-label">Ordem</label>
                                    <input class="lc-input" type="number" name="sort_order" value="<?= (int)($p['sort_order'] ?? 0) ?>" step="1" />
                                </div>

                                <div class="lc-field">
                                    <label class="lc-label">Status</label>
                                    <select class="lc-select" name="status">
                                        <option value="active" <?= $pst === 'active' ? 'selected' : '' ?>>Ativo</option>
                                        <option value="disabled" <?= $pst === 'disabled' ? 'selected' : '' ?>>Desativado</option>
                                    </select>
                                </div>

                                <div style="display:flex; gap:8px; justify-content:flex-end;">
                                    <button class="lc-btn lc-btn--secondary" type="submit">Salvar</button>
                                </div>

                                <div class="lc-field" style="grid-column: 1 / -1;">
                                    <label class="lc-label">Notas</label>
                                    <textarea class="lc-textarea" name="notes" rows="3"><?= htmlspecialchars((string)($p['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="lc-grid" style="grid-template-columns: 2fr 1fr 1fr 1fr; gap:10px;">
                                <div>
                                    <div class="lc-muted">Nome</div>
                                    <div><?= htmlspecialchars((string)($p['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <div>
                                    <div class="lc-muted">Ordem</div>
                                    <div><?= (int)($p['sort_order'] ?? 0) ?></div>
                                </div>
                                <div>
                                    <div class="lc-muted">Status</div>
                                    <div><?= htmlspecialchars((string)$pst, ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <div></div>
                                <div style="grid-column: 1 / -1;">
                                    <div class="lc-muted">Notas</div>
                                    <div><?= nl2br(htmlspecialchars((string)($p['notes'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($can('procedures.manage')): ?>
                            <form method="post" action="/procedures/protocols/delete" style="margin-top:10px;" onsubmit="return confirm('Remover protocolo?');">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="procedure_id" value="<?= (int)$procedureId ?>" />
                                <input type="hidden" name="id" value="<?= (int)$pid ?>" />
                                <button class="lc-btn lc-btn--danger" type="submit">Remover protocolo</button>
                            </form>
                        <?php endif; ?>

                        <div style="margin-top:14px;">
                            <div style="font-weight:800; margin-bottom:8px;">Nova etapa</div>
                            <?php if ($can('procedures.manage')): ?>
                                <form method="post" action="/procedures/steps/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr 1fr 1fr; align-items:end;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="procedure_id" value="<?= (int)$procedureId ?>" />
                                    <input type="hidden" name="protocol_id" value="<?= (int)$pid ?>" />

                                    <div class="lc-field">
                                        <label class="lc-label">Título</label>
                                        <input class="lc-input" type="text" name="title" required />
                                    </div>

                                    <div class="lc-field">
                                        <label class="lc-label">Duração (min)</label>
                                        <input class="lc-input" type="number" name="duration_minutes" min="0" step="1" />
                                    </div>

                                    <div class="lc-field">
                                        <label class="lc-label">Ordem</label>
                                        <input class="lc-input" type="number" name="sort_order" value="0" step="1" />
                                    </div>

                                    <div>
                                        <button class="lc-btn" type="submit">Adicionar</button>
                                    </div>

                                    <div class="lc-field" style="grid-column: 1 / -1;">
                                        <label class="lc-label">Notas</label>
                                        <textarea class="lc-textarea" name="notes" rows="2"></textarea>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>

                        <div style="margin-top:14px;">
                            <div style="font-weight:800; margin-bottom:8px;">Etapas</div>
                            <?php if ($steps === []): ?>
                                <div class="lc-muted">Nenhuma etapa.</div>
                            <?php else: ?>
                                <table class="lc-table">
                                    <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Duração</th>
                                        <th>Ordem</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($steps as $s): ?>
                                        <?php $sid = (int)$s['id']; ?>
                                        <tr>
                                            <td>
                                                <?php if ($can('procedures.manage')): ?>
                                                    <form method="post" action="/procedures/steps/update" class="lc-form" style="display:flex; gap:8px; align-items:center;">
                                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                                        <input type="hidden" name="procedure_id" value="<?= (int)$procedureId ?>" />
                                                        <input type="hidden" name="protocol_id" value="<?= (int)$pid ?>" />
                                                        <input type="hidden" name="id" value="<?= (int)$sid ?>" />
                                                        <input type="hidden" name="notes" value="<?= htmlspecialchars((string)($s['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

                                                        <input class="lc-input" type="text" name="title" value="<?= htmlspecialchars((string)($s['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="min-width: 280px;" required />
                                                <?php else: ?>
                                                    <?= htmlspecialchars((string)($s['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($can('procedures.manage')): ?>
                                                        <input class="lc-input" type="number" name="duration_minutes" value="<?= $s['duration_minutes'] === null ? '' : (int)$s['duration_minutes'] ?>" min="0" step="1" style="width:120px;" />
                                                <?php else: ?>
                                                    <?= $s['duration_minutes'] === null ? '-' : ((int)$s['duration_minutes'] . ' min') ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($can('procedures.manage')): ?>
                                                        <input class="lc-input" type="number" name="sort_order" value="<?= (int)($s['sort_order'] ?? 0) ?>" step="1" style="width:90px;" />
                                                <?php else: ?>
                                                    <?= (int)($s['sort_order'] ?? 0) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align:right; white-space:nowrap;">
                                                <?php if ($can('procedures.manage')): ?>
                                                        <button class="lc-btn lc-btn--secondary" type="submit">Salvar</button>
                                                    </form>

                                                    <form method="post" action="/procedures/steps/delete" style="display:inline-block; margin-left:8px;" onsubmit="return confirm('Remover etapa?');">
                                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                                        <input type="hidden" name="procedure_id" value="<?= (int)$procedureId ?>" />
                                                        <input type="hidden" name="protocol_id" value="<?= (int)$pid ?>" />
                                                        <input type="hidden" name="id" value="<?= (int)$sid ?>" />
                                                        <button class="lc-btn lc-btn--danger" type="submit">Remover</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="4">
                                                <?php if ($can('procedures.manage')): ?>
                                                    <form method="post" action="/procedures/steps/update" class="lc-form">
                                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                                        <input type="hidden" name="procedure_id" value="<?= (int)$procedureId ?>" />
                                                        <input type="hidden" name="protocol_id" value="<?= (int)$pid ?>" />
                                                        <input type="hidden" name="id" value="<?= (int)$sid ?>" />
                                                        <input type="hidden" name="title" value="<?= htmlspecialchars((string)($s['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                                                        <input type="hidden" name="duration_minutes" value="<?= $s['duration_minutes'] === null ? '' : (int)$s['duration_minutes'] ?>" />
                                                        <input type="hidden" name="sort_order" value="<?= (int)($s['sort_order'] ?? 0) ?>" />
                                                        <div class="lc-field" style="margin-top:6px;">
                                                            <label class="lc-label">Notas</label>
                                                            <textarea class="lc-textarea" name="notes" rows="2"><?= htmlspecialchars((string)($s['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                                        </div>
                                                        <button class="lc-btn lc-btn--secondary" type="submit">Salvar notas</button>
                                                    </form>
                                                <?php else: ?>
                                                    <div class="lc-field" style="margin-top:6px;">
                                                        <div class="lc-muted">Notas</div>
                                                        <div><?= nl2br(htmlspecialchars((string)($s['notes'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
