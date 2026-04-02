<?php
$title     = 'Documentos';
$csrf      = $_SESSION['_csrf'] ?? '';
$patient   = $patient ?? null;
$documents = $documents ?? [];
$error     = $error ?? '';
$success   = $success ?? '';

$patientId = (int)($patient['id'] ?? 0);

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

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px;">Documentos · <?= count($documents) ?> arquivo<?= count($documents) !== 1 ? 's' : '' ?></div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= $patientId ?>">Paciente</a>
        <?php if ($can('patients.update')): ?>
            <button type="button" class="lc-btn lc-btn--primary" onclick="document.getElementById('upload-form').style.display = document.getElementById('upload-form').style.display === 'none' ? 'block' : 'none'">+ Enviar documento</button>
        <?php endif; ?>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Upload (oculto) -->
<?php if ($can('patients.update')): ?>
<div id="upload-form" style="display:none; margin-bottom:14px;">
    <div class="lc-card">
        <div class="lc-card__header" style="font-weight:700;">Enviar documento</div>
        <div class="lc-card__body">
            <form method="post" action="/patients/documents/upload" enctype="multipart/form-data" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="patient_id" value="<?= $patientId ?>" />

                <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr; align-items:end;">
                    <div class="lc-field">
                        <label class="lc-label">Arquivo</label>
                        <input class="lc-input" type="file" name="document" required />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Título</label>
                        <input class="lc-input" type="text" name="title" placeholder="Ex: Exame de sangue, RG, Laudo..." />
                    </div>
                </div>

                <div class="lc-flex lc-gap-sm" style="margin-top:12px;">
                    <button class="lc-btn lc-btn--primary" type="submit">Enviar</button>
                    <button type="button" class="lc-btn lc-btn--secondary" onclick="document.getElementById('upload-form').style.display='none'">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Lista de documentos -->
<?php if (empty($documents)): ?>
    <div class="lc-card">
        <div class="lc-card__body" style="text-align:center; padding:40px 20px;">
            <div style="font-size:32px; margin-bottom:10px;">📄</div>
            <div class="lc-muted" style="margin-bottom:12px;">Nenhum documento enviado.</div>
            <?php if ($can('patients.update')): ?>
                <button type="button" class="lc-btn lc-btn--primary" onclick="document.getElementById('upload-form').style.display='block'">Enviar primeiro documento</button>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="lc-card">
        <div class="lc-card__body" style="padding:0;">
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Título</th>
                    <th>Arquivo</th>
                    <th>Tamanho</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($documents as $d): ?>
                    <?php
                    $createdAt = (string)($d['created_at'] ?? '');
                    $dateFmt = '';
                    try { $dateFmt = (new \DateTimeImmutable($createdAt))->format('d/m/Y'); } catch (\Throwable $e) { $dateFmt = $createdAt; }
                    $sizeBytes = (int)($d['size_bytes'] ?? 0);
                    $sizeDisplay = $sizeBytes > 1048576
                        ? number_format($sizeBytes / 1048576, 1) . ' MB'
                        : ($sizeBytes > 1024 ? number_format($sizeBytes / 1024, 0) . ' KB' : $sizeBytes . ' B');
                    ?>
                    <tr>
                        <td style="white-space:nowrap; font-size:13px;"><?= htmlspecialchars($dateFmt, ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars((string)($d['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="lc-muted" style="font-size:12px;"><?= htmlspecialchars((string)($d['original_filename'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="lc-muted" style="font-size:12px;"><?= $sizeDisplay ?></td>
                        <td>
                            <div class="lc-flex lc-gap-sm">
                                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/patients/documents/file?id=<?= (int)$d['id'] ?>" target="_blank">Abrir</a>
                                <?php if ($can('patients.update')): ?>
                                    <form method="post" action="/patients/documents/delete" onsubmit="return confirm('Excluir documento?');">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                        <input type="hidden" name="id" value="<?= (int)$d['id'] ?>" />
                                        <input type="hidden" name="patient_id" value="<?= $patientId ?>" />
                                        <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="color:#b91c1c;">✕</button>
                                    </form>
                                <?php endif; ?>
                            </div>
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
