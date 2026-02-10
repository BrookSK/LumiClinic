<?php
$title = 'Certificações (Políticas & Controles)';
$csrf = $_SESSION['_csrf'] ?? '';
$policies = $policies ?? [];
$controls = $controls ?? [];
$error = $error ?? '';

$policyStatusLabel = [
    'draft' => 'Rascunho',
    'active' => 'Ativa',
    'retired' => 'Desativada',
];

$controlStatusLabel = [
    'planned' => 'Planejado',
    'implemented' => 'Implementado',
    'tested' => 'Testado',
    'failed' => 'Falhou',
];
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Certificações</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/">Dashboard</a>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Cadastrar política</div>
    <div class="lc-card__body">
        <form method="post" action="/compliance/certifications/policies/create" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <label class="lc-label">Código</label>
            <input class="lc-input" type="text" name="code" placeholder="ex: iso27001-a.5" required />

            <label class="lc-label">Título</label>
            <input class="lc-input" type="text" name="title" required />

            <label class="lc-label">Descrição</label>
            <textarea class="lc-textarea" name="description" rows="3"></textarea>

            <label class="lc-label">Status</label>
            <select class="lc-select" name="status">
                <option value="draft" selected>Rascunho</option>
                <option value="active">Ativa</option>
                <option value="retired">Desativada</option>
            </select>

            <label class="lc-label">Versão</label>
            <input class="lc-input" type="number" name="version" min="1" value="1" />

            <label class="lc-label">Responsável (ID do usuário)</label>
            <input class="lc-input" type="number" name="owner_user_id" min="0" placeholder="opcional" />

            <label class="lc-label">Revisado em (AAAA-MM-DD HH:MM:SS)</label>
            <input class="lc-input" type="text" name="reviewed_at" placeholder="opcional" />

            <label class="lc-label">Próxima revisão (AAAA-MM-DD HH:MM:SS)</label>
            <input class="lc-input" type="text" name="next_review_at" placeholder="opcional" />

            <button class="lc-btn lc-btn--primary" type="submit">Cadastrar</button>
        </form>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Cadastrar controle</div>
    <div class="lc-card__body">
        <form method="post" action="/compliance/certifications/controls/create" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <label class="lc-label">Política (ID) (opcional)</label>
            <input class="lc-input" type="number" name="policy_id" min="0" placeholder="opcional" />

            <label class="lc-label">Código</label>
            <input class="lc-input" type="text" name="code" placeholder="ex: iso27001-a.5.1" required />

            <label class="lc-label">Título</label>
            <input class="lc-input" type="text" name="title" required />

            <label class="lc-label">Descrição</label>
            <textarea class="lc-textarea" name="description" rows="3"></textarea>

            <label class="lc-label">Status</label>
            <select class="lc-select" name="status">
                <option value="planned" selected>Planejado</option>
                <option value="implemented">Implementado</option>
                <option value="tested">Testado</option>
                <option value="failed">Falhou</option>
            </select>

            <label class="lc-label">Responsável (ID do usuário)</label>
            <input class="lc-input" type="number" name="owner_user_id" min="0" placeholder="opcional" />

            <label class="lc-label">Link de evidência</label>
            <input class="lc-input" type="text" name="evidence_url" placeholder="opcional" />

            <label class="lc-label">Último teste em (AAAA-MM-DD HH:MM:SS)</label>
            <input class="lc-input" type="text" name="last_tested_at" placeholder="opcional" />

            <button class="lc-btn lc-btn--primary" type="submit">Cadastrar</button>
        </form>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Políticas</div>
    <div class="lc-card__body">
        <?php if (!is_array($policies) || $policies === []): ?>
            <div>Nenhuma política.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Status</th>
                        <th>Versão</th>
                        <th>Título</th>
                        <th>Responsável</th>
                        <th>Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($policies as $p): ?>
                        <tr>
                            <td><?= (int)($p['id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string)($p['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <?php $pst = (string)($p['status'] ?? ''); ?>
                            <td><?= htmlspecialchars((string)($policyStatusLabel[$pst] ?? $pst), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int)($p['version'] ?? 1) ?></td>
                            <td><?= htmlspecialchars((string)($p['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int)($p['owner_user_id'] ?? 0) ?></td>
                            <td style="min-width:560px;">
                                <form method="post" action="/compliance/certifications/policies/update" class="lc-form lc-flex lc-flex--wrap" style="gap:8px; align-items:flex-end;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= (int)($p['id'] ?? 0) ?>" />

                                    <select class="lc-select" name="status">
                                        <option value="draft" <?= (($p['status'] ?? '')==='draft')?'selected':'' ?>>Rascunho</option>
                                        <option value="active" <?= (($p['status'] ?? '')==='active')?'selected':'' ?>>Ativa</option>
                                        <option value="retired" <?= (($p['status'] ?? '')==='retired')?'selected':'' ?>>Desativada</option>
                                    </select>

                                    <input class="lc-input" type="number" name="version" min="1" value="<?= (int)($p['version'] ?? 1) ?>" />

                                    <input class="lc-input" type="text" name="title" value="<?= htmlspecialchars((string)($p['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

                                    <input class="lc-input" type="number" name="owner_user_id" min="0" value="<?= (int)($p['owner_user_id'] ?? 0) ?>" />

                                    <input class="lc-input" type="text" name="reviewed_at" placeholder="Revisado em (AAAA-MM-DD HH:MM:SS)" value="<?= htmlspecialchars((string)($p['reviewed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

                                    <input class="lc-input" type="text" name="next_review_at" placeholder="Próxima revisão (AAAA-MM-DD HH:MM:SS)" value="<?= htmlspecialchars((string)($p['next_review_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

                                    <button class="lc-btn lc-btn--secondary" type="submit">Atualizar</button>
                                </form>
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
    <div class="lc-card__title">Controles</div>
    <div class="lc-card__body">
        <?php if (!is_array($controls) || $controls === []): ?>
            <div>Nenhum controle.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Política</th>
                        <th>Código</th>
                        <th>Status</th>
                        <th>Título</th>
                        <th>Responsável</th>
                        <th>Evidência</th>
                        <th>Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($controls as $c): ?>
                        <tr>
                            <td><?= (int)($c['id'] ?? 0) ?></td>
                            <td><?= (int)($c['policy_id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string)($c['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <?php $cst = (string)($c['status'] ?? ''); ?>
                            <td><?= htmlspecialchars((string)($controlStatusLabel[$cst] ?? $cst), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($c['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int)($c['owner_user_id'] ?? 0) ?></td>
                            <td>
                                <?php $ev = (string)($c['evidence_url'] ?? ''); ?>
                                <?php if ($ev !== ''): ?>
                                    <a href="<?= htmlspecialchars($ev, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Abrir</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td style="min-width:640px;">
                                <form method="post" action="/compliance/certifications/controls/update" class="lc-form lc-flex lc-flex--wrap" style="gap:8px; align-items:flex-end;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= (int)($c['id'] ?? 0) ?>" />

                                    <input class="lc-input" type="number" name="policy_id" min="0" value="<?= (int)($c['policy_id'] ?? 0) ?>" />

                                    <select class="lc-select" name="status">
                                        <option value="planned" <?= (($c['status'] ?? '')==='planned')?'selected':'' ?>>Planejado</option>
                                        <option value="implemented" <?= (($c['status'] ?? '')==='implemented')?'selected':'' ?>>Implementado</option>
                                        <option value="tested" <?= (($c['status'] ?? '')==='tested')?'selected':'' ?>>Testado</option>
                                        <option value="failed" <?= (($c['status'] ?? '')==='failed')?'selected':'' ?>>Falhou</option>
                                    </select>

                                    <input class="lc-input" type="text" name="title" value="<?= htmlspecialchars((string)($c['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

                                    <input class="lc-input" type="number" name="owner_user_id" min="0" value="<?= (int)($c['owner_user_id'] ?? 0) ?>" />

                                    <input class="lc-input" type="text" name="evidence_url" placeholder="Link de evidência" value="<?= htmlspecialchars((string)($c['evidence_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

                                    <input class="lc-input" type="text" name="last_tested_at" placeholder="Último teste em (AAAA-MM-DD HH:MM:SS)" value="<?= htmlspecialchars((string)($c['last_tested_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

                                    <button class="lc-btn lc-btn--secondary" type="submit">Atualizar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
