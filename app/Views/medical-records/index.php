<?php
$title = 'Prontuário';
$patient = $patient ?? null;
$records = $records ?? [];
$alerts = $alerts ?? [];
$allergies = $allergies ?? [];
$conditions = $conditions ?? [];
$images = $images ?? [];
$imagePairs = $image_pairs ?? [];
$professionals = $professionals ?? [];
$filters = $filters ?? [];
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Prontuário</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)($patient['id'] ?? 0) ?>">Voltar ao paciente</a>
        <a class="lc-btn lc-btn--primary" href="/medical-records/create?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Novo registro</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="lc-card__body">
        <?php
            $cpfLast4 = '';
            if (isset($patient['cpf_last4']) && (string)$patient['cpf_last4'] !== '') {
                $cpfLast4 = (string)$patient['cpf_last4'];
            } elseif (isset($patient['cpf']) && (string)$patient['cpf'] !== '') {
                $digits = preg_replace('/\D+/', '', (string)$patient['cpf']);
                $digits = $digits === null ? '' : $digits;
                if (strlen($digits) >= 4) {
                    $cpfLast4 = substr($digits, -4);
                }
            }
        ?>
        CPF: <?= $cpfLast4 !== '' ? ('***.' . htmlspecialchars($cpfLast4, ENT_QUOTES, 'UTF-8')) : '' ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Resumo clínico</div>
    <div class="lc-card__body">
        <div class="lc-grid lc-grid--3 lc-gap-grid">
            <div class="lc-card" style="margin:0;">
                <div class="lc-card__body" style="padding:10px;">
                    <div class="lc-muted" style="font-size:12px;">Alertas</div>
                    <div style="font-weight:800; font-size:18px; line-height:1;"><?= (int)count($alerts) ?></div>
                </div>
            </div>
            <div class="lc-card" style="margin:0;">
                <div class="lc-card__body" style="padding:10px;">
                    <div class="lc-muted" style="font-size:12px;">Alergias/Contraindicações</div>
                    <div style="font-weight:800; font-size:18px; line-height:1;"><?= (int)count($allergies) ?></div>
                </div>
            </div>
            <div class="lc-card" style="margin:0;">
                <div class="lc-card__body" style="padding:10px;">
                    <div class="lc-muted" style="font-size:12px;">Condições</div>
                    <div style="font-weight:800; font-size:18px; line-height:1;"><?= (int)count($conditions) ?></div>
                </div>
            </div>
            <div class="lc-card" style="margin:0;">
                <div class="lc-card__body" style="padding:10px;">
                    <div class="lc-muted" style="font-size:12px;">Imagens</div>
                    <div style="font-weight:800; font-size:18px; line-height:1;"><?= (int)count($images) ?></div>
                </div>
            </div>
        </div>

        <?php if ($imagePairs !== [] || $images !== []): ?>
            <div style="margin-top:12px;">
                <div class="lc-label">Imagens clínicas</div>
                <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:8px;">
                    <a class="lc-btn lc-btn--secondary" href="/medical-images?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Abrir imagens</a>
                </div>

                <?php if ($imagePairs !== []): ?>
                    <div class="lc-table-wrap" style="margin-top:10px;">
                        <table class="lc-table" style="margin:0;">
                            <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Data</th>
                                <th>Procedimento</th>
                                <th style="width:1%; white-space:nowrap;">Ações</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($imagePairs as $p): ?>
                                <tr>
                                    <td>Comparação</td>
                                    <td><?= htmlspecialchars((string)($p['taken_at'] ?? $p['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($p['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <a class="lc-btn lc-btn--secondary" href="/medical-images/compare?patient_id=<?= (int)($patient['id'] ?? 0) ?>&key=<?= urlencode((string)($p['comparison_key'] ?? '')) ?>">Abrir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if ($images !== []): ?>
                    <div class="lc-table-wrap" style="margin-top:10px;">
                        <table class="lc-table" style="margin:0;">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Data</th>
                                <th>Procedimento</th>
                                <th style="width:1%; white-space:nowrap;">Ações</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach (array_slice($images, 0, 10) as $img): ?>
                                <tr>
                                    <td><?= (int)($img['id'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars((string)($img['kind'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($img['taken_at'] ?? $img['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($img['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <a class="lc-btn lc-btn--secondary" href="/medical-images/file?id=<?= (int)($img['id'] ?? 0) ?>" target="_blank">Abrir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($alerts !== []): ?>
            <div style="margin-top:12px;">
                <div class="lc-label">Alertas clínicos</div>
                <div class="lc-table-wrap" style="margin-top:8px;">
                    <table class="lc-table" style="margin:0;">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Severidade</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alerts as $a): ?>
                                <?php
                                    $active = isset($a['active']) && (int)$a['active'] === 1;
                                    $sev = (string)($a['severity'] ?? '');
                                    $sevLabel = $sev === 'critical' ? 'Crítico' : ($sev === 'warning' ? 'Atenção' : 'Info');
                                ?>
                                <tr>
                                    <td>
                                        <div><strong><?= htmlspecialchars((string)($a['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                        <?php if (isset($a['details']) && (string)$a['details'] !== ''): ?>
                                            <div class="lc-muted" style="margin-top:4px;">
                                                <?= nl2br(htmlspecialchars((string)$a['details'], ENT_QUOTES, 'UTF-8')) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($sevLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= $active ? 'Ativo' : 'Resolvido' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($allergies !== []): ?>
            <div style="margin-top:12px;">
                <div class="lc-label">Alergias / Contraindicações</div>
                <div class="lc-table-wrap" style="margin-top:8px;">
                    <table class="lc-table" style="margin:0;">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Item</th>
                                <th>Reação</th>
                                <th>Severidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allergies as $it): ?>
                                <?php $t = (string)($it['type'] ?? ''); ?>
                                <?php $typeLabel = $t === 'contraindication' ? 'Contraindicação' : 'Alergia'; ?>
                                <tr>
                                    <td><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <div><strong><?= htmlspecialchars((string)($it['trigger_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                        <?php if (isset($it['notes']) && (string)$it['notes'] !== ''): ?>
                                            <div class="lc-muted" style="margin-top:4px;">
                                                <?= nl2br(htmlspecialchars((string)$it['notes'], ENT_QUOTES, 'UTF-8')) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars((string)($it['reaction'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($it['severity'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($conditions !== []): ?>
            <div style="margin-top:12px;">
                <div class="lc-label">Condições</div>
                <div class="lc-table-wrap" style="margin-top:8px;">
                    <table class="lc-table" style="margin:0;">
                        <thead>
                            <tr>
                                <th>Condição</th>
                                <th>Status</th>
                                <th>Início</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($conditions as $c): ?>
                                <?php
                                    $st = (string)($c['status'] ?? 'active');
                                    $stLabel = $st === 'inactive' ? 'Inativa' : ($st === 'resolved' ? 'Resolvida' : 'Ativa');
                                ?>
                                <tr>
                                    <td>
                                        <div><strong><?= htmlspecialchars((string)($c['condition_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                        <?php if (isset($c['notes']) && (string)$c['notes'] !== ''): ?>
                                            <div class="lc-muted" style="margin-top:4px;">
                                                <?= nl2br(htmlspecialchars((string)$c['notes'], ENT_QUOTES, 'UTF-8')) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($stLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($c['onset_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($alerts === [] && $allergies === [] && $conditions === []): ?>
            <div class="lc-muted" style="margin-top:10px;">Nenhuma informação clínica cadastrada.</div>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Filtros</div>
    <div class="lc-card__body">
        <form method="get" action="/medical-records" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr)); align-items:end;">
            <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />

            <div class="lc-field">
                <label class="lc-label">Profissional</label>
                <?php $curProf = (int)($filters['professional_id'] ?? 0); ?>
                <select class="lc-select" name="professional_id">
                    <option value="">(todos)</option>
                    <?php foreach ($professionals as $pr): ?>
                        <option value="<?= (int)$pr['id'] ?>" <?= (int)$pr['id'] === $curProf ? 'selected' : '' ?>><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">De</label>
                <input class="lc-input" type="datetime-local" name="date_from" value="<?= htmlspecialchars((string)($filters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Até</label>
                <input class="lc-input" type="datetime-local" name="date_to" value="<?= htmlspecialchars((string)($filters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-form__actions" style="grid-column: 1 / -1;">
                <button class="lc-btn lc-btn--primary" type="submit">Aplicar</button>
                <a class="lc-btn lc-btn--secondary" href="/medical-records?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Limpar</a>
            </div>
        </form>
    </div>
</div>

<?php foreach ($records as $r): ?>
    <div id="mr-<?= (int)$r['id'] ?>" class="lc-card" style="margin-bottom:12px;">
        <div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="gap:10px;">
            <div>
                <div class="lc-card__title">Atendimento em <?= htmlspecialchars((string)$r['attended_at'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="lc-card__body">
                    <?= htmlspecialchars((string)($r['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
            <div>
                <a class="lc-btn lc-btn--secondary" href="/medical-records/edit?patient_id=<?= (int)$patient['id'] ?>&id=<?= (int)$r['id'] ?>">Editar</a>
            </div>
        </div>

        <?php if (($r['clinical_description'] ?? '') !== ''): ?>
            <div style="margin-top:12px;">
                <div class="lc-label">Descrição clínica</div>
                <div><?= nl2br(htmlspecialchars((string)$r['clinical_description'], ENT_QUOTES, 'UTF-8')) ?></div>
            </div>
        <?php endif; ?>

        <?php if (($r['clinical_evolution'] ?? '') !== ''): ?>
            <div style="margin-top:12px;">
                <div class="lc-label">Evolução</div>
                <div><?= nl2br(htmlspecialchars((string)$r['clinical_evolution'], ENT_QUOTES, 'UTF-8')) ?></div>
            </div>
        <?php endif; ?>

        <?php if (($r['notes'] ?? '') !== ''): ?>
            <div style="margin-top:12px;">
                <div class="lc-label">Notas</div>
                <div><?= nl2br(htmlspecialchars((string)$r['notes'], ENT_QUOTES, 'UTF-8')) ?></div>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
