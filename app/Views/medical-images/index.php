<?php
$title = 'Imagens clínicas';
$csrf = $_SESSION['_csrf'] ?? '';
$patient = $patient ?? null;
$images = $images ?? [];
$professionals = $professionals ?? [];
$pairs = $pairs ?? [];
$records = $records ?? [];

$kindLabel = [
    'before' => 'Antes',
    'after' => 'Depois',
    'other' => 'Outro',
];
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div>
        <div class="lc-badge lc-badge--primary">Imagens clínicas</div>
        <div class="lc-muted" style="margin-top:6px;">Envie imagens do paciente e, se necessário, crie comparações (Antes x Depois).</div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/medical-images/timeline?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Timeline</a>
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)($patient['id'] ?? 0) ?>">Voltar ao paciente</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md">
        <div>
            <div style="font-weight:700;">
                <?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="lc-muted">
                CPF: <?= isset($patient['cpf_last4']) && $patient['cpf_last4'] ? '***.' . htmlspecialchars((string)$patient['cpf_last4'], ENT_QUOTES, 'UTF-8') : '' ?>
            </div>
        </div>
    </div>
</div>

<div class="lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr; align-items:start; margin-bottom:14px;">
    <div>
        <div class="lc-card" style="margin-bottom:14px;">
            <div class="lc-card__title">Enviar imagem</div>
            <div class="lc-card__body">
                <div class="lc-muted" style="margin-bottom:10px;">Use para anexar uma imagem ao paciente (ex.: acompanhamento, exames, fotos).</div>

                <form method="post" action="/medical-images/upload" enctype="multipart/form-data" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); align-items:end;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />

                    <div class="lc-field" style="grid-column: 1 / -1;">
                        <label class="lc-label">Arquivo</label>
                        <input class="lc-input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required />
                    </div>

                    <div class="lc-field">
                        <label class="lc-label">Tipo</label>
                        <select class="lc-select" name="kind">
                            <option value="other">Outro</option>
                            <option value="before">Antes</option>
                            <option value="after">Depois</option>
                        </select>
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Data (opcional)</label>
                        <input class="lc-input" type="datetime-local" name="taken_at" />
                    </div>

                    <div class="lc-field">
                        <label class="lc-label">Procedimento (opcional)</label>
                        <input class="lc-input" type="text" name="procedure_type" />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Sessão (opcional)</label>
                        <input class="lc-input" type="number" name="session_number" min="1" step="1" />
                    </div>

                    <div class="lc-field">
                        <label class="lc-label">Ângulo/Posição (opcional)</label>
                        <input class="lc-input" type="text" name="pose" />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Profissional (opcional)</label>
                        <select class="lc-select" name="professional_id">
                            <option value="">(opcional)</option>
                            <?php foreach ($professionals as $pr): ?>
                                <option value="<?= (int)$pr['id'] ?>"><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="lc-field" style="grid-column: 1 / -1;">
                        <label class="lc-label">Vincular ao prontuário (opcional)</label>
                        <select class="lc-select" name="medical_record_id">
                            <option value="">(opcional)</option>
                            <?php foreach ($records as $r): ?>
                                <?php
                                $rid = (int)($r['id'] ?? 0);
                                $att = trim((string)($r['attended_at'] ?? ''));
                                $proc = trim((string)($r['procedure_type'] ?? ''));
                                $label = $att !== '' ? $att : ('Registro #' . $rid);
                                if ($proc !== '') {
                                    $label .= ' - ' . $proc;
                                }
                                ?>
                                <option value="<?= $rid ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="lc-form__actions" style="grid-column: 1 / -1;">
                        <button class="lc-btn lc-btn--primary" type="submit">Enviar imagem</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="lc-card">
            <details class="lc-collapse">
                <summary class="lc-card__title">Comparação (Antes e Depois)</summary>
                <div class="lc-card__body">
                    <div class="lc-muted" style="margin-bottom:10px;">Use quando você tiver duas imagens do mesmo procedimento (antes e depois) e quiser comparar.</div>

                    <form method="post" action="/medical-images/upload-pair" enctype="multipart/form-data" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); align-items:end;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />

                        <div class="lc-field">
                            <label class="lc-label">Antes</label>
                            <input class="lc-input" type="file" name="before_image" accept="image/jpeg,image/png,image/webp" required />
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Depois</label>
                            <input class="lc-input" type="file" name="after_image" accept="image/jpeg,image/png,image/webp" required />
                        </div>

                        <div class="lc-field">
                            <label class="lc-label">Data (opcional)</label>
                            <input class="lc-input" type="datetime-local" name="taken_at" />
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Procedimento (opcional)</label>
                            <input class="lc-input" type="text" name="procedure_type" />
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Sessão (opcional)</label>
                            <input class="lc-input" type="number" name="session_number" min="1" step="1" />
                        </div>

                        <div class="lc-field">
                            <label class="lc-label">Ângulo/Posição (opcional)</label>
                            <input class="lc-input" type="text" name="pose" />
                        </div>

                        <div class="lc-field">
                            <label class="lc-label">Profissional (opcional)</label>
                            <select class="lc-select" name="professional_id">
                                <option value="">(opcional)</option>
                                <?php foreach ($professionals as $pr): ?>
                                    <option value="<?= (int)$pr['id'] ?>"><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Vincular ao prontuário (opcional)</label>
                            <select class="lc-select" name="medical_record_id">
                                <option value="">(opcional)</option>
                                <?php foreach ($records as $r): ?>
                                    <?php
                                    $rid = (int)($r['id'] ?? 0);
                                    $att = trim((string)($r['attended_at'] ?? ''));
                                    $proc = trim((string)($r['procedure_type'] ?? ''));
                                    $label = $att !== '' ? $att : ('Registro #' . $rid);
                                    if ($proc !== '') {
                                        $label .= ' - ' . $proc;
                                    }
                                    ?>
                                    <option value="<?= $rid ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="lc-form__actions" style="grid-column: 1 / -1;">
                            <button class="lc-btn lc-btn--primary" type="submit">Criar comparação</button>
                        </div>
                    </form>
                </div>
            </details>
        </div>
    </div>

    <div>
        <div class="lc-card" style="margin-bottom:14px;">
            <div class="lc-card__title">Comparações</div>
            <div class="lc-card__body">
                <div class="lc-muted" style="margin-bottom:10px;">Aqui ficam as comparações já criadas para este paciente.</div>
                <?php if (!is_array($pairs) || $pairs === []): ?>
                    <div class="lc-muted">Nenhuma comparação cadastrada.</div>
                <?php else: ?>
                    <div class="lc-table-wrap">
                        <table class="lc-table">
                            <thead>
                            <tr>
                                <th>Data</th>
                                <th>Procedimento</th>
                                <th style="width:1%; white-space:nowrap;">Ações</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($pairs as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($p['taken_at'] ?? $p['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($p['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <a class="lc-btn lc-btn--secondary" href="/medical-images/compare?patient_id=<?= (int)($patient['id'] ?? 0) ?>&key=<?= urlencode((string)$p['comparison_key']) ?>">Abrir</a>
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
            <div class="lc-card__title">Arquivos enviados</div>
            <div class="lc-card__body">
                <div class="lc-muted" style="margin-bottom:10px;">Lista de imagens anexadas ao paciente.</div>
                <div class="lc-table-wrap">
                    <table class="lc-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Data</th>
                            <th>Procedimento</th>
                            <th>Sessão</th>
                            <th>Ângulo/Posição</th>
                            <th>Arquivo</th>
                            <th>Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($images as $img): ?>
                            <tr>
                                <td><?= (int)$img['id'] ?></td>
                                <?php $k = (string)($img['kind'] ?? ''); ?>
                                <td><?= htmlspecialchars((string)($kindLabel[$k] ?? $k), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($img['taken_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($img['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($img['session_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($img['pose'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($img['original_filename'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="lc-flex lc-flex--wrap" style="gap:8px;">
                                    <a class="lc-btn lc-btn--secondary" href="/medical-images/file?id=<?= (int)$img['id'] ?>" target="_blank">Abrir</a>
                                    <a class="lc-btn lc-btn--secondary" href="/medical-images/annotate?id=<?= (int)$img['id'] ?>">Marcar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
