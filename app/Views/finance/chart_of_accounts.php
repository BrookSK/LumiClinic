<?php
$csrf    = $_SESSION['_csrf'] ?? '';
$title   = 'Plano de Contas';
$rows    = $rows ?? [];
$error   = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

$natureLabels  = \App\Services\Finance\ChartOfAccountsService::NATURES;
$typeLabels    = \App\Services\Finance\ChartOfAccountsService::TYPES;
$methodLabels  = \App\Services\Finance\ChartOfAccountsService::PAYMENT_METHODS;
$defaultLabels = \App\Services\Finance\ChartOfAccountsService::DEFAULT_FOR;

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
$canManage = $can('finance.cost_centers.manage');

$syntheticRows = array_values(array_filter($rows, static fn ($r) => (string)($r['account_type'] ?? '') === 'synthetic'));

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
        <div style="font-weight:800; font-size:18px;">Plano de Contas</div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px; max-width:620px; line-height:1.5;">
            Estruture as contas contábeis da clínica. Vincule os centros de custo às contas analíticas para exportar relatórios organizados para a contabilidade.
        </div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/finance/cost-centers">Centros de Custo</a>
    </div>
</div>

<?php if (empty($rows)): ?>
    <div class="lc-card" style="margin-bottom:14px;">
        <div class="lc-card__header">Comece pelo modelo padrão</div>
        <div class="lc-card__body">
            <div class="lc-muted" style="font-size:13px; line-height:1.6; max-width:720px;">
                A clínica ainda não tem contas cadastradas. O modelo padrão cria um plano de contas pronto para clínicas
                (Ativo, Passivo, Patrimônio Líquido, Receitas e Custos/Despesas), já com as contas de Caixa, Bancos e Cartões
                vinculadas às formas de pagamento (Dinheiro, Pix, Cartão, Boleto...). Centros de custo com o mesmo nome de uma conta
                (ex: "13º Salário", "Aluguel e Condomínio") são vinculados automaticamente. Depois você pode editar, inativar ou criar contas.
            </div>
            <?php if ($canManage): ?>
                <form method="post" action="/finance/chart-of-accounts/apply-template" style="margin-top:12px;" onsubmit="return confirm('Criar o plano de contas padrão para esta clínica?');">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <button class="lc-btn lc-btn--primary" type="submit">Usar modelo padrão para clínicas</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($canManage): ?>
<details class="lc-card" style="margin-bottom:14px;">
    <summary class="lc-card__header" style="cursor:pointer;">+ Nova conta</summary>
    <div class="lc-card__body">
        <form method="post" action="/finance/chart-of-accounts/create" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); align-items:end;">
                <div class="lc-field">
                    <label class="lc-label">Conta pai (opcional)</label>
                    <select class="lc-select" name="parent_id">
                        <option value="0">Nenhuma (conta raiz)</option>
                        <?php foreach ($syntheticRows as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['code'] . ' - ' . (string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Código</label>
                    <input class="lc-input" type="text" name="code" maxlength="32" required pattern="\d+(\.\d+)*" placeholder="Ex: 1.1.2.09" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Nome</label>
                    <input class="lc-input" type="text" name="name" maxlength="190" required placeholder="Ex: Conta Banco do Brasil" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Natureza</label>
                    <select class="lc-select" name="nature">
                        <option value="">Mesma da conta pai</option>
                        <?php foreach ($natureLabels as $k => $label): ?>
                            <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Tipo</label>
                    <select class="lc-select" name="account_type">
                        <?php foreach ($typeLabels as $k => $label): ?>
                            <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $k === 'analytic' ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Forma de pagamento</label>
                    <select class="lc-select" name="payment_method">
                        <option value="">Nenhuma</option>
                        <?php foreach ($methodLabels as $k => $label): ?>
                            <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Conta padrão para</label>
                    <select class="lc-select" name="default_for">
                        <option value="">Nenhuma</option>
                        <?php foreach ($defaultLabels as $k => $label): ?>
                            <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Código externo (contador)</label>
                    <input class="lc-input" type="text" name="external_code" maxlength="64" placeholder="Código no sistema do contador" />
                </div>
            </div>

            <div class="lc-field" style="margin-top:10px;">
                <label class="lc-label">Descrição</label>
                <textarea class="lc-input" name="description" rows="2" maxlength="255"></textarea>
            </div>

            <div class="lc-muted" style="font-size:12px; margin-top:8px; line-height:1.5;">
                Contas sintéticas agrupam outras contas; analíticas recebem lançamentos e podem ser vinculadas a centros de custo.
                O código de uma conta filha começa com o código da conta pai seguido de ponto (ex: 1.1.2 &rarr; 1.1.2.09).
                A forma de pagamento só vale para contas analíticas do Ativo (ex: Caixa Geral, Pix, Cartão) e indica a conta de entrada/saída do dinheiro.
                A conta padrão recebe os lançamentos sem centro de custo vinculado a uma conta.
            </div>

            <div class="lc-flex lc-gap-sm" style="margin-top:12px;">
                <button class="lc-btn lc-btn--primary" type="submit">Criar conta</button>
            </div>
        </form>
    </div>
</details>
<?php endif; ?>

<?php if (!empty($rows)): ?>
<div class="lc-card">
    <div class="lc-card__body" style="padding:0; overflow-x:auto;">
        <table class="lc-table">
            <thead>
            <tr>
                <th>Código</th>
                <th>Conta</th>
                <th>Natureza</th>
                <th>Tipo</th>
                <th>Forma de pagamento vinculada</th>
                <th>Código externo</th>
                <th style="text-align:center;">Centros de custo vinculados</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <?php
                $id = (int)($r['id'] ?? 0);
                $depth = (int)($r['depth'] ?? 0);
                $synthetic = (string)($r['account_type'] ?? '') === 'synthetic';
                $active = (string)($r['status'] ?? 'active') === 'active';
                $nature = (string)($r['nature'] ?? '');
                $type = (string)($r['account_type'] ?? '');
                $method = (string)($r['payment_method'] ?? '');
                $defaultFor = (string)($r['default_for'] ?? '');
                $linked = (int)($r['linked_cost_centers'] ?? 0);
                ?>
                <tr style="<?= $active ? '' : 'opacity:.5;' ?>">
                    <td style="white-space:nowrap; <?= $synthetic ? 'font-weight:700;' : '' ?>"><?= htmlspecialchars((string)($r['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="padding-left:<?= 12 + ($depth * 20) ?>px; <?= $synthetic ? 'font-weight:700;' : '' ?><?= $synthetic && $depth === 0 ? ' text-transform:uppercase;' : '' ?>">
                        <?= htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($defaultFor !== ''): ?>
                            <span class="lc-badge lc-badge--primary" style="font-size:10px; margin-left:4px;">Padrão: <?= htmlspecialchars($defaultLabels[$defaultFor] ?? $defaultFor, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php if (trim((string)($r['description'] ?? '')) !== ''): ?>
                            <div class="lc-muted" style="font-size:11px; font-weight:400; text-transform:none;"><?= htmlspecialchars((string)$r['description'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($natureLabels[$nature] ?? $nature, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $synthetic ? 'Sintética' : 'Analítica' ?></td>
                    <td><?= $method !== '' ? htmlspecialchars($methodLabels[$method] ?? $method, ENT_QUOTES, 'UTF-8') : '<span class="lc-muted">-</span>' ?></td>
                    <td><?= trim((string)($r['external_code'] ?? '')) !== '' ? htmlspecialchars((string)$r['external_code'], ENT_QUOTES, 'UTF-8') : '<span class="lc-muted">-</span>' ?></td>
                    <td style="text-align:center;"><?= $synthetic ? '<span class="lc-muted">-</span>' : $linked ?></td>
                    <td>
                        <span class="lc-badge <?= $active ? 'lc-badge--success' : 'lc-badge--secondary' ?>" style="font-size:11px;"><?= $active ? 'Ativa' : 'Inativa' ?></span>
                    </td>
                    <td style="white-space:nowrap;">
                        <?php if ($canManage): ?>
                            <div class="lc-flex lc-gap-sm">
                                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/finance/chart-of-accounts/edit?id=<?= $id ?>">Editar</a>
                                <form method="post" action="/finance/chart-of-accounts/status" style="margin:0;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= $id ?>" />
                                    <input type="hidden" name="status" value="<?= $active ? 'disabled' : 'active' ?>" />
                                    <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit"><?= $active ? 'Inativar' : 'Ativar' ?></button>
                                </form>
                                <form method="post" action="/finance/chart-of-accounts/delete" style="margin:0;" onsubmit="return confirm('Excluir esta conta do plano de contas?');">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= $id ?>" />
                                    <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Excluir</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/finance/chart-of-accounts/edit?id=<?= $id ?>">Ver</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
