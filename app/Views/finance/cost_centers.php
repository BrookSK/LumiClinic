<?php
$csrf    = $_SESSION['_csrf'] ?? '';
$title   = 'Centros de Custo';
$rows    = $rows ?? [];
$error   = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

$can = function (string $p): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $perms = $_SESSION['permissions'] ?? [];
    if (!is_array($perms)) return false;
    if (isset($perms['allow'], $perms['deny'])) {
        if (in_array($p, $perms['deny'], true)) return false;
        return in_array($p, $perms['allow'], true);
    }
    return in_array($p, $perms, true);
};

ob_start();
?>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;">Centros de Custo</div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px; max-width:500px; line-height:1.5;">
            Centros de custo servem para categorizar suas despesas e receitas. Ex: "Aluguel", "Marketing", "Materiais", "Salários". Assim você sabe para onde o dinheiro está indo.
        </div>
    </div>
    <a class="lc-btn lc-btn--secondary" href="/finance/cashflow">Fluxo de Caixa</a>
</div>

<!-- Criar -->
<?php if ($can('finance.cost_centers.manage')): ?>
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <form method="post" action="/finance/cost-centers/create" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <div class="lc-field" style="flex:1; min-width:200px;">
                <label class="lc-label">Nome do centro de custo</label>
                <input class="lc-input" type="text" name="name" required placeholder="Ex: Aluguel, Marketing, Materiais..." />
            </div>
            <button class="lc-btn lc-btn--primary" type="submit">Criar</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Lista -->
<?php if (empty($rows)): ?>
    <div class="lc-card">
        <div class="lc-card__body" style="text-align:center; padding:30px;">
            <div class="lc-muted">Nenhum centro de custo cadastrado.</div>
            <div class="lc-muted" style="font-size:12px; margin-top:6px;">Crie centros de custo para organizar suas finanças por categoria.</div>
        </div>
    </div>
<?php else: ?>
    <div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));">
        <?php foreach ($rows as $r): ?>
            <?php
            $id = (int)($r['id'] ?? 0);
            $st = (string)($r['status'] ?? 'active');
            $active = $st === 'active';
            ?>
            <div class="lc-card" style="margin:0; opacity:<?= $active ? '1' : '.5' ?>;">
                <div style="padding:16px;">
                    <div class="lc-flex lc-flex--between lc-flex--center" style="margin-bottom:8px;">
                        <div style="font-weight:700;"><?= htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <span class="lc-badge <?= $active ? 'lc-badge--success' : 'lc-badge--secondary' ?>" style="font-size:11px;">
                            <?= $active ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </div>
                    <?php if ($can('finance.cost_centers.manage')): ?>
                        <div class="lc-flex lc-gap-sm lc-flex--wrap">
                            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/finance/cost-centers/edit?id=<?= $id ?>">Editar</a>
                            <form method="post" action="/finance/cost-centers/status" style="margin:0;">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="id" value="<?= $id ?>" />
                                <input type="hidden" name="status" value="<?= $active ? 'disabled' : 'active' ?>" />
                                <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit"><?= $active ? 'Inativar' : 'Ativar' ?></button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
