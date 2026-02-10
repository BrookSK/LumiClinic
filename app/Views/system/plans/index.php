<?php
/** @var list<array<string,mixed>> $items */

$title = 'Admin do Sistema';
$items = $items ?? [];
$error = isset($error) ? (string)$error : '';
$ok = isset($ok) ? (string)$ok : '';
$csrf = $_SESSION['_csrf'] ?? '';

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Planos</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/sys/billing">Assinaturas</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Clínicas</a>
    </div>
</div>

<?php if ($ok !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;">
        <?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<details class="lc-card" style="margin-bottom:14px;">
    <summary class="lc-card__title" style="cursor:pointer; user-select:none;">Criar novo plano</summary>
    <div class="lc-card__body">
        <form method="post" action="/sys/plans/create" class="lc-form lc-grid lc-grid--2 lc-gap-grid">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Nome</label>
                <input class="lc-input" type="text" name="name" placeholder="Basic" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Código</label>
                <input class="lc-input" type="text" value="Gerado automaticamente" disabled />
            </div>

            <div class="lc-field">
                <label class="lc-label">Preço (centavos)</label>
                <input class="lc-input" type="number" name="price_cents" value="0" min="0" step="1" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Moeda</label>
                <input type="hidden" name="currency" value="BRL" />
                <input class="lc-input" type="text" value="BRL (Real)" disabled />
            </div>

            <div class="lc-field">
                <label class="lc-label">Intervalo</label>
                <select class="lc-select" name="interval_unit">
                    <option value="month">Mensal</option>
                    <option value="year">Anual</option>
                    <option value="week">Semanal</option>
                    <option value="day">Diário</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Cobrar a cada (quantidade)</label>
                <input class="lc-input" type="number" name="interval_count" value="1" min="1" step="1" />
                <div class="lc-muted" style="margin-top:6px;">Ex.: Intervalo Mensal + 1 = cobrar todo mês. Intervalo Mensal + 3 = cobrar a cada 3 meses.</div>
            </div>

            <div class="lc-field">
                <label class="lc-label">Dias de teste</label>
                <input class="lc-input" type="number" name="trial_days" value="0" min="0" step="1" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Status</label>
                <select class="lc-select" name="status">
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                </select>
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Acesso ao Portal do Paciente</label>
                <select class="lc-select" name="portal_enabled">
                    <option value="1" selected>Ativo</option>
                    <option value="0">Desativado</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Limite de usuários (equipe)</label>
                <input class="lc-input" type="number" name="limit_users" value="0" min="0" step="1" />
                <div class="lc-muted" style="margin-top:6px;">0 = ilimitado</div>
            </div>

            <div class="lc-field">
                <label class="lc-label">Limite de pacientes</label>
                <input class="lc-input" type="number" name="limit_patients" value="0" min="0" step="1" />
                <div class="lc-muted" style="margin-top:6px;">0 = ilimitado</div>
            </div>

            <div class="lc-field">
                <label class="lc-label">Limite de armazenamento (MB)</label>
                <input class="lc-input" type="number" name="limit_storage_mb" value="0" min="0" step="1" />
                <div class="lc-muted" style="margin-top:6px;">0 = ilimitado</div>
            </div>

            <div class="lc-flex lc-flex--end lc-gap-sm" style="grid-column: 1 / -1;">
                <button class="lc-btn lc-btn--primary" type="submit">Criar plano</button>
            </div>
        </form>
    </div>
</details>

<div class="lc-card">
    <div class="lc-card__title">Planos cadastrados</div>
    <div class="lc-card__body" style="padding-bottom:0;">
        <div class="lc-muted" style="line-height:1.55; margin-bottom:12px;"></div>
    </div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Código</th>
                <th>Nome</th>
                <th>Preço</th>
                <th>Intervalo</th>
                <th>Teste</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <?php
                    $id = (int)($it['id'] ?? 0);
                    $status = (string)($it['status'] ?? '');
                    $price = (int)($it['price_cents'] ?? 0);
                    $intervalUnit = (string)($it['interval_unit'] ?? 'month');
                    $intervalCount = (int)($it['interval_count'] ?? 1);
                    $trialDays = (int)($it['trial_days'] ?? 0);
                    $limits = (string)($it['limits_json'] ?? '{}');
                    $limitsDecoded = [];
                    $tmp = json_decode($limits, true);
                    if (is_array($tmp)) {
                        $limitsDecoded = $tmp;
                    }
                    $portalEnabled = array_key_exists('portal', $limitsDecoded) ? (bool)$limitsDecoded['portal'] : true;
                    $limitUsers = (int)($limitsDecoded['users'] ?? 0);
                    $limitPatients = (int)($limitsDecoded['patients'] ?? 0);
                    $limitStorageMb = (int)($limitsDecoded['storage_mb'] ?? 0);
                ?>
                <tr>
                    <td><?= $id ?></td>
                    <td><?= htmlspecialchars((string)($it['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($it['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>R$ <?= number_format(max(0, $price) / 100, 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars((string)$intervalCount . ' ' . $intervalUnit, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)$trialDays ?> dias</td>
                    <td><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <details class="lc-card" style="margin:0; padding:10px; box-shadow:none;">
                            <summary class="lc-link" style="cursor:pointer;">Editar</summary>
                            <div style="margin-top:10px;">
                                <form method="post" action="/sys/plans/update" class="lc-form lc-grid lc-grid--2 lc-gap-grid">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= $id ?>" />

                                    <div class="lc-field" style="grid-column: 1 / -1;">
                                        <label class="lc-label">Nome</label>
                                        <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($it['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />
                                    </div>

                                    <div class="lc-field">
                                        <label class="lc-label">Preço (centavos)</label>
                                        <input class="lc-input" type="number" name="price_cents" value="<?= (int)$price ?>" min="0" step="1" />
                                    </div>

                                    <div class="lc-field">
                                        <label class="lc-label">Moeda</label>
                                        <input type="hidden" name="currency" value="BRL" />
                                        <input class="lc-input" type="text" value="BRL (Real)" disabled />
                                    </div>

                                    <div class="lc-field">
                                        <label class="lc-label">Intervalo</label>
                                        <select class="lc-select" name="interval_unit">
                                            <?php foreach (['day'=>'Diário','week'=>'Semanal','month'=>'Mensal','year'=>'Anual'] as $k=>$lbl): ?>
                                                <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= ($intervalUnit === $k) ? 'selected' : '' ?>><?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="lc-field">
                                        <label class="lc-label">Cobrar a cada (quantidade)</label>
                                        <input class="lc-input" type="number" name="interval_count" value="<?= (int)$intervalCount ?>" min="1" step="1" />
                                        <div class="lc-muted" style="margin-top:6px;">Ex.: Mensal + 1 = todo mês. Mensal + 3 = a cada 3 meses.</div>
                                    </div>

                                    <div class="lc-field">
                                        <label class="lc-label">Dias de teste</label>
                                        <input class="lc-input" type="number" name="trial_days" value="<?= (int)$trialDays ?>" min="0" step="1" />
                                    </div>

                                    <div class="lc-field" style="grid-column: 1 / -1;">
                                        <label class="lc-label">Acesso ao Portal do Paciente</label>
                                        <select class="lc-select" name="portal_enabled">
                                            <option value="1" <?= $portalEnabled ? 'selected' : '' ?>>Ativo</option>
                                            <option value="0" <?= !$portalEnabled ? 'selected' : '' ?>>Desativado</option>
                                        </select>
                                    </div>

                                    <div class="lc-field">
                                        <label class="lc-label">Limite de usuários (equipe)</label>
                                        <input class="lc-input" type="number" name="limit_users" value="<?= (int)$limitUsers ?>" min="0" step="1" />
                                        <div class="lc-muted" style="margin-top:6px;">0 = ilimitado</div>
                                    </div>

                                    <div class="lc-field">
                                        <label class="lc-label">Limite de pacientes</label>
                                        <input class="lc-input" type="number" name="limit_patients" value="<?= (int)$limitPatients ?>" min="0" step="1" />
                                        <div class="lc-muted" style="margin-top:6px;">0 = ilimitado</div>
                                    </div>

                                    <div class="lc-field">
                                        <label class="lc-label">Limite de armazenamento (MB)</label>
                                        <input class="lc-input" type="number" name="limit_storage_mb" value="<?= (int)$limitStorageMb ?>" min="0" step="1" />
                                        <div class="lc-muted" style="margin-top:6px;">0 = ilimitado</div>
                                    </div>

                                    <div class="lc-flex lc-flex--end lc-gap-sm" style="grid-column: 1 / -1;">
                                        <button class="lc-btn lc-btn--secondary" type="submit">Salvar</button>
                                    </div>
                                </form>

                                <form method="post" action="/sys/plans/set-status" class="lc-form lc-flex lc-gap-sm" style="align-items:end; margin-top:10px;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= $id ?>" />
                                    <div class="lc-field">
                                        <label class="lc-label">Status</label>
                                        <select class="lc-select" name="status">
                                            <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Ativo</option>
                                            <option value="inactive" <?= ($status === 'inactive') ? 'selected' : '' ?>>Inativo</option>
                                        </select>
                                    </div>
                                    <button class="lc-btn lc-btn--secondary" type="submit">Atualizar status</button>
                                </form>
                            </div>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
