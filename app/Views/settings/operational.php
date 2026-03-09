<?php
$title = 'Configuração operacional';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? '';
$saved = $saved ?? '';

$stages = isset($stages) && is_array($stages) ? $stages : [];
$lostReasons = isset($lost_reasons) && is_array($lost_reasons) ? $lost_reasons : [];
$origins = isset($origins) && is_array($origins) ? $origins : [];

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

ob_start();
?>

<div class="lc-card" style="margin-bottom:16px;">
    <div class="lc-card__title">Configuração operacional</div>
    <div class="lc-card__body">
        <div class="lc-muted">Ajuste aqui listas usadas no dia a dia (funil de atendimento, motivos e origem do paciente).</div>
    </div>
</div>

<?php if (is_string($error) && trim($error) !== ''): ?>
    <div class="lc-card lc-statusbar lc-statusbar--no_show" style="margin-bottom:16px;">
        <div class="lc-card__body"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if (is_string($saved) && trim($saved) !== ''): ?>
    <div class="lc-card lc-statusbar lc-statusbar--completed" style="margin-bottom:16px;">
        <div class="lc-card__body">Salvo com sucesso.</div>
    </div>
<?php endif; ?>

<div class="lc-grid lc-gap-grid" style="align-items:start;">
    <div class="lc-card">
        <div class="lc-card__title">Etapas do funil</div>
        <div class="lc-card__body">
            <div class="lc-muted" style="margin-bottom:10px;">Ex.: Novo contato, Triagem, Orçamento, Agendamento, Convertido.</div>

            <?php if ($can('settings.update')): ?>
                <form method="post" action="/settings/operational/funnel-stages/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr; align-items:end;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <div class="lc-field">
                        <label class="lc-label">Nome</label>
                        <input class="lc-input" type="text" name="name" required />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Ordem</label>
                        <input class="lc-input" type="number" name="sort_order" value="0" />
                    </div>
                    <div class="lc-form__actions" style="grid-column: 1 / -1;">
                        <button class="lc-btn lc-btn--primary" type="submit">Adicionar etapa</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if ($stages === []): ?>
                <div class="lc-muted" style="margin-top:12px;">Nenhuma etapa cadastrada.</div>
            <?php else: ?>
                <div class="lc-table-wrap" style="margin-top:12px;">
                    <table class="lc-table">
                        <thead>
                        <tr>
                            <th>Ordem</th>
                            <th>Nome</th>
                            <th style="width:1%; white-space:nowrap;">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($stages as $s): ?>
                            <tr>
                                <td><?= (int)($s['sort_order'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string)($s['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="text-align:right;">
                                    <?php if ($can('settings.update')): ?>
                                        <form method="post" action="/settings/operational/funnel-stages/delete" style="display:inline;" onsubmit="return confirm('Remover esta etapa?');">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)($s['id'] ?? 0) ?>" />
                                            <button class="lc-btn lc-btn--secondary" type="submit">Remover</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="lc-card">
        <div class="lc-card__title">Motivos de perda</div>
        <div class="lc-card__body">
            <div class="lc-muted" style="margin-bottom:10px;">Use quando um atendimento não for adiante (ex.: sem orçamento, sem agenda, desistiu).</div>

            <?php if ($can('settings.update')): ?>
                <form method="post" action="/settings/operational/lost-reasons/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr; align-items:end;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <div class="lc-field">
                        <label class="lc-label">Nome</label>
                        <input class="lc-input" type="text" name="name" required />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Ordem</label>
                        <input class="lc-input" type="number" name="sort_order" value="0" />
                    </div>
                    <div class="lc-form__actions" style="grid-column: 1 / -1;">
                        <button class="lc-btn lc-btn--primary" type="submit">Adicionar motivo</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if ($lostReasons === []): ?>
                <div class="lc-muted" style="margin-top:12px;">Nenhum motivo cadastrado.</div>
            <?php else: ?>
                <div class="lc-table-wrap" style="margin-top:12px;">
                    <table class="lc-table">
                        <thead>
                        <tr>
                            <th>Ordem</th>
                            <th>Nome</th>
                            <th style="width:1%; white-space:nowrap;">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($lostReasons as $r): ?>
                            <tr>
                                <td><?= (int)($r['sort_order'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="text-align:right;">
                                    <?php if ($can('settings.update')): ?>
                                        <form method="post" action="/settings/operational/lost-reasons/delete" style="display:inline;" onsubmit="return confirm('Remover este motivo?');">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>" />
                                            <button class="lc-btn lc-btn--secondary" type="submit">Remover</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="lc-card">
        <div class="lc-card__title">Origem do paciente</div>
        <div class="lc-card__body">
            <div class="lc-muted" style="margin-bottom:10px;">Ex.: Instagram, Indicação, Google, Site, WhatsApp.</div>

            <?php if ($can('settings.update')): ?>
                <form method="post" action="/settings/operational/patient-origins/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr; align-items:end;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <div class="lc-field">
                        <label class="lc-label">Nome</label>
                        <input class="lc-input" type="text" name="name" required />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Ordem</label>
                        <input class="lc-input" type="number" name="sort_order" value="0" />
                    </div>
                    <div class="lc-form__actions" style="grid-column: 1 / -1;">
                        <button class="lc-btn lc-btn--primary" type="submit">Adicionar origem</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if ($origins === []): ?>
                <div class="lc-muted" style="margin-top:12px;">Nenhuma origem cadastrada.</div>
            <?php else: ?>
                <div class="lc-table-wrap" style="margin-top:12px;">
                    <table class="lc-table">
                        <thead>
                        <tr>
                            <th>Ordem</th>
                            <th>Nome</th>
                            <th style="width:1%; white-space:nowrap;">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($origins as $o): ?>
                            <tr>
                                <td><?= (int)($o['sort_order'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string)($o['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="text-align:right;">
                                    <?php if ($can('settings.update')): ?>
                                        <form method="post" action="/settings/operational/patient-origins/delete" style="display:inline;" onsubmit="return confirm('Remover esta origem?');">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)($o['id'] ?? 0) ?>" />
                                            <button class="lc-btn lc-btn--secondary" type="submit">Remover</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
