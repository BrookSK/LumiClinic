<?php
$title = 'Imagens clínicas';
$csrf = $_SESSION['_csrf'] ?? '';
$patient = $patient ?? null;
$images = $images ?? [];
$professionals = $professionals ?? [];
$pairs = $pairs ?? [];
ob_start();
?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:10px; flex-wrap:wrap;">
    <div class="lc-badge lc-badge--gold">Imagens clínicas</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Enviar Before/After (comparação)</div>

    <form method="post" action="/medical-images/upload-pair" enctype="multipart/form-data" class="lc-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />

        <div class="lc-grid">
            <div>
                <label class="lc-label">Antes (Before)</label>
                <input class="lc-input" type="file" name="before_image" accept="image/jpeg,image/png,image/webp" required />
            </div>
            <div>
                <label class="lc-label">Depois (After)</label>
                <input class="lc-input" type="file" name="after_image" accept="image/jpeg,image/png,image/webp" required />
            </div>
        </div>

        <div class="lc-grid">
            <div>
                <label class="lc-label">Data (opcional)</label>
                <input class="lc-input" type="datetime-local" name="taken_at" />
            </div>
            <div>
                <label class="lc-label">Procedimento (opcional)</label>
                <input class="lc-input" type="text" name="procedure_type" />
            </div>
        </div>

        <div class="lc-grid">
            <div>
                <label class="lc-label">Profissional (opcional)</label>
                <select class="lc-select" name="professional_id">
                    <option value="">(opcional)</option>
                    <?php foreach ($professionals as $pr): ?>
                        <option value="<?= (int)$pr['id'] ?>"><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="lc-label">Vincular ao registro do prontuário (ID, opcional)</label>
                <input class="lc-input" type="number" name="medical_record_id" min="1" />
            </div>
        </div>

        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <button class="lc-btn lc-btn--primary" type="submit">Enviar e comparar</button>
        </div>
    </form>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Comparações</div>
    <div class="lc-card__body">
        <?php if (!is_array($pairs) || $pairs === []): ?>
            <div class="lc-muted">Nenhuma comparação cadastrada.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Procedimento</th>
                        <th>Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pairs as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($p['taken_at'] ?? $p['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($p['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="display:flex; gap:8px; flex-wrap:wrap;">
                                <a class="lc-btn lc-btn--secondary" href="/medical-images/compare?patient_id=<?= (int)($patient['id'] ?? 0) ?>&key=<?= urlencode((string)$p['comparison_key']) ?>">Comparar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)($patient['id'] ?? 0) ?>">Voltar ao paciente</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="lc-card__body">
        CPF: <?= isset($patient['cpf_last4']) && $patient['cpf_last4'] ? '***.' . htmlspecialchars((string)$patient['cpf_last4'], ENT_QUOTES, 'UTF-8') : '' ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Enviar imagem</div>

    <form method="post" action="/medical-images/upload" enctype="multipart/form-data" class="lc-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />

        <label class="lc-label">Arquivo</label>
        <input class="lc-input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required />

        <div class="lc-grid">
            <div>
                <label class="lc-label">Tipo</label>
                <select class="lc-select" name="kind">
                    <option value="other">Outro</option>
                    <option value="before">Antes</option>
                    <option value="after">Depois</option>
                </select>
            </div>
            <div>
                <label class="lc-label">Data (opcional)</label>
                <input class="lc-input" type="datetime-local" name="taken_at" />
            </div>
        </div>

        <div class="lc-grid">
            <div>
                <label class="lc-label">Procedimento (opcional)</label>
                <input class="lc-input" type="text" name="procedure_type" />
            </div>
            <div>
                <label class="lc-label">Profissional (opcional)</label>
                <select class="lc-select" name="professional_id">
                    <option value="">(opcional)</option>
                    <?php foreach ($professionals as $pr): ?>
                        <option value="<?= (int)$pr['id'] ?>"><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <label class="lc-label">Vincular ao registro do prontuário (ID, opcional)</label>
        <input class="lc-input" type="number" name="medical_record_id" min="1" />

        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <button class="lc-btn lc-btn--primary" type="submit">Enviar</button>
        </div>
    </form>
</div>

<div class="lc-card">
    <div class="lc-card__title">Arquivos</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Tipo</th>
                <th>Data</th>
                <th>Procedimento</th>
                <th>Arquivo</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($images as $img): ?>
                <tr>
                    <td><?= (int)$img['id'] ?></td>
                    <td><?= htmlspecialchars((string)$img['kind'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($img['taken_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($img['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($img['original_filename'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="display:flex; gap:8px; flex-wrap:wrap;">
                        <a class="lc-btn lc-btn--secondary" href="/medical-images/file?id=<?= (int)$img['id'] ?>" target="_blank">Abrir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
