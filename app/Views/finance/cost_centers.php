<?php
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Financeiro - Centros de custo';
$rows = $rows ?? [];
$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Centros de custo</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/finance/cashflow">Fluxo de caixa</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Novo centro de custo</div>
    <div class="lc-card__body">
        <form method="post" action="/finance/cost-centers/create" class="lc-form lc-flex lc-gap-sm lc-flex--wrap" style="align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <div class="lc-field" style="min-width:260px;">
                <label class="lc-label">Nome</label>
                <input class="lc-input" type="text" name="name" required />
            </div>
            <button class="lc-btn" type="submit">Criar</button>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Lista</div>
    <div class="lc-card__body">
        <div class="lc-table-wrap">
            <table class="lc-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Status</th>
                    <th style="width:280px;">Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="4">Nenhum centro de custo cadastrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <?php
                            $id = (int)($r['id'] ?? 0);
                            $st = (string)($r['status'] ?? 'active');
                            $toggleTo = $st === 'active' ? 'disabled' : 'active';
                        ?>
                        <tr>
                            <td><?= $id ?></td>
                            <td><?= htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <div class="lc-flex lc-gap-sm lc-flex--wrap">
                                    <a class="lc-btn lc-btn--secondary" href="/finance/cost-centers/edit?id=<?= $id ?>">Editar</a>

                                    <form method="post" action="/finance/cost-centers/status" style="margin:0;" onsubmit="return confirm('Alterar status?');">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                        <input type="hidden" name="id" value="<?= $id ?>" />
                                        <input type="hidden" name="status" value="<?= htmlspecialchars($toggleTo, ENT_QUOTES, 'UTF-8') ?>" />
                                        <button class="lc-btn lc-btn--secondary" type="submit"><?= $st === 'active' ? 'Inativar' : 'Ativar' ?></button>
                                    </form>

                                    <form method="post" action="/finance/cost-centers/delete" style="margin:0;" onsubmit="return confirm('Excluir centro de custo?');">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                        <input type="hidden" name="id" value="<?= $id ?>" />
                                        <button class="lc-btn lc-btn--danger" type="submit">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
