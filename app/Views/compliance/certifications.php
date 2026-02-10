<?php
$title = 'Certificações (Políticas & Controles)';
$csrf = $_SESSION['_csrf'] ?? '';
$policies = $policies ?? [];
$controls = $controls ?? [];
$error = $error ?? '';
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
                <option value="draft" selected>draft</option>
                <option value="active">active</option>
                <option value="retired">retired</option>
            </select>

            <label class="lc-label">Versão</label>
            <input class="lc-input" type="number" name="version" min="1" value="1" />

            <label class="lc-label">Owner (user_id)</label>
            <input class="lc-input" type="number" name="owner_user_id" min="0" placeholder="opcional" />

            <label class="lc-label">Revisado em (YYYY-MM-DD HH:MM:SS)</label>
            <input class="lc-input" type="text" name="reviewed_at" placeholder="opcional" />

            <label class="lc-label">Próxima revisão (YYYY-MM-DD HH:MM:SS)</label>
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

            <label class="lc-label">Policy ID (opcional)</label>
            <input class="lc-input" type="number" name="policy_id" min="0" placeholder="opcional" />

            <label class="lc-label">Código</label>
            <input class="lc-input" type="text" name="code" placeholder="ex: iso27001-a.5.1" required />

            <label class="lc-label">Título</label>
            <input class="lc-input" type="text" name="title" required />

            <label class="lc-label">Descrição</label>
            <textarea class="lc-textarea" name="description" rows="3"></textarea>

            <label class="lc-label">Status</label>
            <select class="lc-select" name="status">
                <option value="planned" selected>planned</option>
                <option value="implemented">implemented</option>
                <option value="tested">tested</option>
                <option value="failed">failed</option>
            </select>

            <label class="lc-label">Owner (user_id)</label>
            <input class="lc-input" type="number" name="owner_user_id" min="0" placeholder="opcional" />

            <label class="lc-label">Evidence URL</label>
            <input class="lc-input" type="text" name="evidence_url" placeholder="opcional" />

            <label class="lc-label">Last tested at (YYYY-MM-DD HH:MM:SS)</label>
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
                        <th>Owner</th>
                        <th>Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($policies as $p): ?>
                        <tr>
                            <td><?= (int)($p['id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string)($p['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($p['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int)($p['version'] ?? 1) ?></td>
                            <td><?= htmlspecialchars((string)($p['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int)($p['owner_user_id'] ?? 0) ?></td>
                            <td style="min-width:560px;">
                                <form method="post" action="/compliance/certifications/policies/update" class="lc-form lc-flex lc-flex--wrap" style="gap:8px; align-items:flex-end;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= (int)($p['id'] ?? 0) ?>" />

                                    <select class="lc-select" name="status">
                                        <option value="draft" <?= (($p['status'] ?? '')==='draft')?'selected':'' ?>>draft</option>
                                        <option value="active" <?= (($p['status'] ?? '')==='active')?'selected':'' ?>>active</option>
                                        <option value="retired" <?= (($p['status'] ?? '')==='retired')?'selected':'' ?>>retired</option>
                                    </select>

                                    <input class="lc-input" type="number" name="version" min="1" value="<?= (int)($p['version'] ?? 1) ?>" />

                                    <input class="lc-input" type="text" name="title" value="<?= htmlspecialchars((string)($p['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

                                    <input class="lc-input" type="number" name="owner_user_id" min="0" value="<?= (int)($p['owner_user_id'] ?? 0) ?>" />

                                    <input class="lc-input" type="text" name="reviewed_at" placeholder="reviewed_at" value="<?= htmlspecialchars((string)($p['reviewed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

                                    <input class="lc-input" type="text" name="next_review_at" placeholder="next_review_at" value="<?= htmlspecialchars((string)($p['next_review_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

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
                        <th>Policy</th>
                        <th>Código</th>
                        <th>Status</th>
                        <th>Título</th>
                        <th>Owner</th>
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
                            <td><?= htmlspecialchars((string)($c['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($c['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int)($c['owner_user_id'] ?? 0) ?></td>
                            <td>
                                <?php $ev = (string)($c['evidence_url'] ?? ''); ?>
                                <?php if ($ev !== ''): ?>
                                    <a href="<?= htmlspecialchars($ev, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">link</a>
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
                                        <option value="planned" <?= (($c['status'] ?? '')==='planned')?'selected':'' ?>>planned</option>
                                        <option value="implemented" <?= (($c['status'] ?? '')==='implemented')?'selected':'' ?>>implemented</option>
                                        <option value="tested" <?= (($c['status'] ?? '')==='tested')?'selected':'' ?>>tested</option>
                                        <option value="failed" <?= (($c['status'] ?? '')==='failed')?'selected':'' ?>>failed</option>
                                    </select>

                                    <input class="lc-input" type="text" name="title" value="<?= htmlspecialchars((string)($c['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

                                    <input class="lc-input" type="number" name="owner_user_id" min="0" value="<?= (int)($c['owner_user_id'] ?? 0) ?>" />

                                    <input class="lc-input" type="text" name="evidence_url" placeholder="evidence_url" value="<?= htmlspecialchars((string)($c['evidence_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

                                    <input class="lc-input" type="text" name="last_tested_at" placeholder="last_tested_at" value="<?= htmlspecialchars((string)($c['last_tested_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

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
