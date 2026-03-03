<?php
/** @var array<string,mixed> $patient */
/** @var list<array<string,mixed>> $allergies */
/** @var list<array<string,mixed>> $conditions */
/** @var list<array<string,mixed>> $alerts */
/** @var string $error */
/** @var string $success */

$title = 'Ficha clínica';
$csrf = $_SESSION['_csrf'] ?? '';
$patientId = (int)($patient['id'] ?? 0);

$allergies = isset($allergies) && is_array($allergies) ? $allergies : [];
$conditions = isset($conditions) && is_array($conditions) ? $conditions : [];
$alerts = isset($alerts) && is_array($alerts) ? $alerts : [];
$error = $error ?? '';
$success = $success ?? '';

$typeLabel = [
    'allergy' => 'Alergia',
    'contraindication' => 'Contraindicação',
];

$severityLabel = [
    'info' => 'Info',
    'warning' => 'Atenção',
    'critical' => 'Crítico',
];

$conditionStatusLabel = [
    'active' => 'Ativa',
    'inactive' => 'Inativa',
    'resolved' => 'Resolvida',
];

ob_start();
?>

<?php if (is_string($error) && $error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (is_string($success) && $success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div>
        <div class="lc-badge lc-badge--primary">Ficha clínica</div>
        <div class="lc-muted" style="margin-top:6px;">
            <?= htmlspecialchars((string)($patient['name'] ?? ('Paciente #' . $patientId)), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= $patientId ?>">Voltar ao paciente</a>
        <a class="lc-btn lc-btn--secondary" href="/medical-records?patient_id=<?= $patientId ?>">Prontuário</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Alertas clínicos</div>
    <div class="lc-card__body">
        <form method="post" action="/patients/clinical-sheet/alerts/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr 1fr; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="patient_id" value="<?= $patientId ?>" />

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Título</label>
                <input class="lc-input" type="text" name="title" required />
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Detalhes</label>
                <textarea class="lc-input" name="details" rows="3"></textarea>
            </div>

            <div class="lc-field">
                <label class="lc-label">Severidade</label>
                <select class="lc-select" name="severity">
                    <option value="info">Info</option>
                    <option value="warning" selected>Atenção</option>
                    <option value="critical">Crítico</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Ativo</label>
                <select class="lc-select" name="active">
                    <option value="1" selected>Sim</option>
                    <option value="0">Não</option>
                </select>
            </div>

            <div class="lc-form__actions" style="grid-column: 1 / -1;">
                <button class="lc-btn lc-btn--primary" type="submit">Adicionar alerta</button>
            </div>
        </form>

        <?php if ($alerts === []): ?>
            <div class="lc-muted" style="margin-top:12px;">Nenhum alerta cadastrado.</div>
        <?php else: ?>
            <div class="lc-table-wrap" style="margin-top:12px;">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Título</th>
                        <th>Severidade</th>
                        <th>Status</th>
                        <th style="width:1%; white-space:nowrap;">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($alerts as $a): ?>
                        <?php
                            $aid = (int)($a['id'] ?? 0);
                            $sev = (string)($a['severity'] ?? 'warning');
                            $active = (int)($a['active'] ?? 0) === 1;
                        ?>
                        <tr>
                            <td>
                                <div><strong><?= htmlspecialchars((string)($a['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                <?php if (($a['details'] ?? '') !== ''): ?>
                                    <div class="lc-muted" style="margin-top:4px;">
                                        <?= nl2br(htmlspecialchars((string)$a['details'], ENT_QUOTES, 'UTF-8')) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)($severityLabel[$sev] ?? $sev), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $active ? 'Ativo' : 'Inativo' ?></td>
                            <td class="lc-td-actions" style="white-space:nowrap;">
                                <?php if ($active): ?>
                                    <form method="post" action="/patients/clinical-sheet/alerts/resolve" style="display:inline;" onsubmit="return confirm('Marcar como resolvido?');">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                        <input type="hidden" name="patient_id" value="<?= $patientId ?>" />
                                        <input type="hidden" name="id" value="<?= $aid ?>" />
                                        <button class="lc-btn lc-btn--secondary" type="submit">Resolver</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="/patients/clinical-sheet/alerts/delete" style="display:inline;" onsubmit="return confirm('Remover este alerta?');">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="patient_id" value="<?= $patientId ?>" />
                                    <input type="hidden" name="id" value="<?= $aid ?>" />
                                    <button class="lc-btn lc-btn--secondary" type="submit">Excluir</button>
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

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Alergias / Contraindicações</div>
    <div class="lc-card__body">
        <form method="post" action="/patients/clinical-sheet/allergies/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 1fr 2fr 1fr; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="patient_id" value="<?= $patientId ?>" />

            <div class="lc-field">
                <label class="lc-label">Tipo</label>
                <select class="lc-select" name="type">
                    <option value="allergy">Alergia</option>
                    <option value="contraindication">Contraindicação</option>
                </select>
            </div>
            <div class="lc-field">
                <label class="lc-label">Item</label>
                <input class="lc-input" type="text" name="trigger_name" required placeholder="Ex: Dipirona" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Severidade</label>
                <input class="lc-input" type="text" name="severity" placeholder="Ex: grave" />
            </div>
            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Reação</label>
                <input class="lc-input" type="text" name="reaction" placeholder="Ex: urticária" />
            </div>
            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Notas</label>
                <textarea class="lc-input" name="notes" rows="2"></textarea>
            </div>
            <div class="lc-form__actions" style="grid-column: 1 / -1;">
                <button class="lc-btn lc-btn--primary" type="submit">Adicionar</button>
            </div>
        </form>

        <?php if ($allergies === []): ?>
            <div class="lc-muted" style="margin-top:12px;">Nenhum item cadastrado.</div>
        <?php else: ?>
            <div class="lc-table-wrap" style="margin-top:12px;">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Item</th>
                        <th>Reação</th>
                        <th>Severidade</th>
                        <th style="width:1%; white-space:nowrap;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($allergies as $it): ?>
                        <?php
                            $aid = (int)($it['id'] ?? 0);
                            $t = (string)($it['type'] ?? '');
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($typeLabel[$t] ?? $t), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <div><strong><?= htmlspecialchars((string)($it['trigger_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                <?php if (($it['notes'] ?? '') !== ''): ?>
                                    <div class="lc-muted" style="margin-top:4px;">
                                        <?= nl2br(htmlspecialchars((string)$it['notes'], ENT_QUOTES, 'UTF-8')) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)($it['reaction'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($it['severity'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="lc-td-actions">
                                <form method="post" action="/patients/clinical-sheet/allergies/delete" style="display:inline;" onsubmit="return confirm('Remover este item?');">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="patient_id" value="<?= $patientId ?>" />
                                    <input type="hidden" name="id" value="<?= $aid ?>" />
                                    <button class="lc-btn lc-btn--secondary" type="submit">Excluir</button>
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

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Condições</div>
    <div class="lc-card__body">
        <form method="post" action="/patients/clinical-sheet/conditions/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr 1fr; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="patient_id" value="<?= $patientId ?>" />

            <div class="lc-field">
                <label class="lc-label">Condição</label>
                <input class="lc-input" type="text" name="condition_name" required placeholder="Ex: Hipertensão" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Status</label>
                <select class="lc-select" name="status">
                    <option value="active" selected>Ativa</option>
                    <option value="inactive">Inativa</option>
                    <option value="resolved">Resolvida</option>
                </select>
            </div>
            <div class="lc-field">
                <label class="lc-label">Início (opcional)</label>
                <input class="lc-input" type="date" name="onset_date" />
            </div>
            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Notas</label>
                <textarea class="lc-input" name="notes" rows="2"></textarea>
            </div>
            <div class="lc-form__actions" style="grid-column: 1 / -1;">
                <button class="lc-btn lc-btn--primary" type="submit">Adicionar</button>
            </div>
        </form>

        <?php if ($conditions === []): ?>
            <div class="lc-muted" style="margin-top:12px;">Nenhuma condição cadastrada.</div>
        <?php else: ?>
            <div class="lc-table-wrap" style="margin-top:12px;">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Condição</th>
                        <th>Status</th>
                        <th>Início</th>
                        <th style="width:1%; white-space:nowrap;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($conditions as $c): ?>
                        <?php
                            $cid = (int)($c['id'] ?? 0);
                            $st = (string)($c['status'] ?? 'active');
                        ?>
                        <tr>
                            <td>
                                <div><strong><?= htmlspecialchars((string)($c['condition_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                <?php if (($c['notes'] ?? '') !== ''): ?>
                                    <div class="lc-muted" style="margin-top:4px;">
                                        <?= nl2br(htmlspecialchars((string)$c['notes'], ENT_QUOTES, 'UTF-8')) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)($conditionStatusLabel[$st] ?? $st), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($c['onset_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="lc-td-actions">
                                <form method="post" action="/patients/clinical-sheet/conditions/delete" style="display:inline;" onsubmit="return confirm('Remover esta condição?');">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="patient_id" value="<?= $patientId ?>" />
                                    <input type="hidden" name="id" value="<?= $cid ?>" />
                                    <button class="lc-btn lc-btn--secondary" type="submit">Excluir</button>
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
