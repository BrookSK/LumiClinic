<?php
/** @var array<string,mixed> $appointment */
/** @var array<string,mixed>|null $patient */
/** @var array<string,mixed>|null $consultation */
/** @var list<array<string,mixed>> $attachments */
/** @var list<array<string,mixed>> $professionals */
/** @var string $error */
/** @var string $success */

$title = 'Consulta executada';
$csrf = $_SESSION['_csrf'] ?? '';

$apptId = (int)($appointment['id'] ?? 0);
$patientId = (int)($appointment['patient_id'] ?? 0);
$curProfessionalId = (int)($appointment['professional_id'] ?? 0);

$executedAtValue = '';
if (is_array($consultation) && isset($consultation['executed_at'])) {
    $executedAtValue = (string)$consultation['executed_at'];
}
if ($executedAtValue === '') {
    $executedAtValue = (string)($appointment['start_at'] ?? '');
}
if (strlen($executedAtValue) >= 16) {
    $executedAtValue = substr($executedAtValue, 0, 16);
    $executedAtValue = str_replace(' ', 'T', $executedAtValue);
}

if (is_array($consultation) && isset($consultation['professional_id'])) {
    $curProfessionalId = (int)$consultation['professional_id'];
}

$notesValue = is_array($consultation) ? (string)($consultation['notes'] ?? '') : '';

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (isset($success) && $success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div>
        <div class="lc-badge lc-badge--primary">Execução</div>
        <div class="lc-muted" style="margin-top:6px;">
            <?= htmlspecialchars((string)($patient['name'] ?? ('Paciente #' . $patientId)), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <?php if ($patientId > 0): ?>
            <a class="lc-btn lc-btn--secondary" href="/patients/appointments?patient_id=<?= $patientId ?>">Voltar</a>
        <?php else: ?>
            <a class="lc-btn lc-btn--secondary" href="/schedule?view=week&date=<?= urlencode(date('Y-m-d')) ?>">Voltar</a>
        <?php endif; ?>
        <a class="lc-btn lc-btn--secondary" href="/schedule/reschedule?appointment_id=<?= $apptId ?>">Reagendar</a>
        <a class="lc-btn lc-btn--secondary" href="/schedule/logs?appointment_id=<?= $apptId ?>">Logs</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__header">Dados do agendamento</div>
    <div class="lc-card__body lc-grid lc-grid--4 lc-gap-grid">
        <div><strong>Início:</strong> <?= htmlspecialchars((string)($appointment['start_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Fim:</strong> <?= htmlspecialchars((string)($appointment['end_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Status:</strong> <?= htmlspecialchars((string)($appointment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Origem:</strong> <?= htmlspecialchars((string)($appointment['origin'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__header">Registrar execução</div>
    <div class="lc-card__body">
        <form method="post" action="/patients/consultation/save" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="appointment_id" value="<?= $apptId ?>" />

            <div class="lc-field">
                <label class="lc-label">Executada em</label>
                <input class="lc-input" type="datetime-local" name="executed_at" value="<?= htmlspecialchars($executedAtValue, ENT_QUOTES, 'UTF-8') ?>" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Profissional</label>
                <select class="lc-select" name="professional_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($professionals as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === $curProfessionalId) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Observações</label>
                <textarea class="lc-input" name="notes" rows="5"><?= htmlspecialchars($notesValue, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div style="grid-column: 1 / -1;" class="lc-flex lc-flex--end">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar execução</button>
            </div>
        </form>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__header">Anexos</div>
    <div class="lc-card__body">
        <form method="post" action="/patients/consultation/attachments/upload" enctype="multipart/form-data" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr 160px; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="appointment_id" value="<?= $apptId ?>" />

            <div class="lc-field">
                <label class="lc-label">Arquivo</label>
                <input class="lc-input" type="file" name="file" accept="image/jpeg,image/png,image/webp,application/pdf" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Nota (opcional)</label>
                <input class="lc-input" type="text" name="note" placeholder="Ex: termo assinado" />
            </div>

            <button class="lc-btn lc-btn--secondary" type="submit">Anexar</button>
        </form>

        <?php if (!is_array($attachments) || $attachments === []): ?>
            <div class="lc-muted" style="margin-top:10px;">Nenhum anexo.</div>
        <?php else: ?>
            <table class="lc-table" style="margin-top:10px;">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Arquivo</th>
                    <th>Nota</th>
                    <th>Tipo</th>
                    <th>Tamanho</th>
                    <th>Criado em</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($attachments as $a): ?>
                    <?php
                        $aid = (int)($a['id'] ?? 0);
                        $fname = (string)($a['original_filename'] ?? 'arquivo');
                        $anote = (string)($a['note'] ?? '');
                        $mime = (string)($a['mime_type'] ?? '');
                        $size = (int)($a['size_bytes'] ?? 0);
                        $createdAt = (string)($a['created_at'] ?? '');
                    ?>
                    <tr>
                        <td><?= $aid ?></td>
                        <td><?= htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($anote, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($mime, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $size > 0 ? number_format($size / 1024, 1, ',', '.') . ' KB' : '-' ?></td>
                        <td><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a class="lc-btn lc-btn--secondary" href="/patients/consultation/attachments/file?id=<?= $aid ?>">Abrir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
