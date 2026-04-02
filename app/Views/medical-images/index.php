<?php
$title = 'Imagens clínicas';
$csrf  = $_SESSION['_csrf'] ?? '';
$patient      = $patient ?? null;
$images       = $images ?? [];
$professionals = $professionals ?? [];
$pairs        = $pairs ?? [];
$records      = $records ?? [];

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

$kindLabel = ['photo' => 'Foto', 'exam' => 'Exame', 'progress' => 'Acompanhamento', 'other' => 'Outro'];

ob_start();
?>

<!-- Cabeçalho -->
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px;">
            Imagens clínicas
            · <?= count($images) ?> imagem<?= count($images) !== 1 ? 'ns' : '' ?>
            <?php if (!empty($pairs)): ?>· <?= count($pairs) ?> comparação<?= count($pairs) !== 1 ? 'ões' : '' ?><?php endif; ?>
        </div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= $patientId ?>">Paciente</a>
        <?php if ($can('medical_images.upload')): ?>
            <button type="button" class="lc-btn lc-btn--secondary" onclick="toggleForm('form-upload')">+ Imagem</button>
            <button type="button" class="lc-btn lc-btn--secondary" onclick="toggleForm('form-pair')">+ Antes/Depois</button>
        <?php endif; ?>
    </div>
</div>

<!-- Formulário upload (oculto) -->
<?php if ($can('medical_images.upload')): ?>
<div id="form-upload" style="display:none; margin-bottom:14px;">
    <div class="lc-card">
        <div class="lc-card__header" style="font-weight:700;">Enviar imagem</div>
        <div class="lc-card__body">
            <form method="post" action="/medical-images/upload" enctype="multipart/form-data" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="patient_id" value="<?= $patientId ?>" />

                <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr 1fr; align-items:end;">
                    <div class="lc-field" style="grid-column:1/-1;">
                        <label class="lc-label">Arquivo (JPG, PNG, WebP)</label>
                        <input class="lc-input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Tipo</label>
                        <select class="lc-select" name="kind">
                            <option value="photo">Foto</option>
                            <option value="progress">Acompanhamento</option>
                            <option value="exam">Exame</option>
                            <option value="other">Outro</option>
                        </select>
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Procedimento (opcional)</label>
                        <input class="lc-input" type="text" name="procedure_type" placeholder="Ex: Botox" />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Data (opcional)</label>
                        <input class="lc-input" type="date" name="taken_at" />
                    </div>
                </div>

                <div class="lc-flex lc-gap-sm" style="margin-top:12px;">
                    <button class="lc-btn lc-btn--primary" type="submit">Enviar</button>
                    <button type="button" class="lc-btn lc-btn--secondary" onclick="toggleForm('form-upload')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Formulário antes/depois (oculto) -->
<div id="form-pair" style="display:none; margin-bottom:14px;">
    <div class="lc-card">
        <div class="lc-card__header" style="font-weight:700;">Comparação Antes / Depois</div>
        <div class="lc-card__body">
            <form method="post" action="/medical-images/upload-pair" enctype="multipart/form-data" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="patient_id" value="<?= $patientId ?>" />

                <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr; align-items:end;">
                    <div class="lc-field">
                        <label class="lc-label">Antes</label>
                        <input class="lc-input" type="file" name="before_image" accept="image/jpeg,image/png,image/webp" required />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Depois</label>
                        <input class="lc-input" type="file" name="after_image" accept="image/jpeg,image/png,image/webp" required />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Procedimento (opcional)</label>
                        <input class="lc-input" type="text" name="procedure_type" placeholder="Ex: Botox" />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Data (opcional)</label>
                        <input class="lc-input" type="date" name="taken_at" />
                    </div>
                </div>

                <div class="lc-flex lc-gap-sm" style="margin-top:12px;">
                    <button class="lc-btn lc-btn--primary" type="submit">Criar comparação</button>
                    <button type="button" class="lc-btn lc-btn--secondary" onclick="toggleForm('form-pair')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Comparações Antes/Depois -->
<?php if (!empty($pairs)): ?>
<div style="margin-bottom:16px;">
    <div class="lc-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px;">Comparações</div>
    <div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
        <?php foreach ($pairs as $p): ?>
            <?php
            $proc = trim((string)($p['procedure_type'] ?? ''));
            $takenAt = (string)($p['taken_at'] ?? $p['created_at'] ?? '');
            $dateFmt = '';
            try { $dateFmt = $takenAt !== '' ? (new \DateTimeImmutable($takenAt))->format('d/m/Y') : ''; } catch (\Throwable $e) {}
            ?>
            <a href="/medical-images/compare?patient_id=<?= $patientId ?>&key=<?= urlencode((string)$p['comparison_key']) ?>"
               style="display:block; text-decoration:none; color:inherit;">
                <div class="lc-card" style="margin:0; overflow:hidden; transition:box-shadow .15s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.15)'" onmouseout="this.style.boxShadow=''">
                    <div style="background:linear-gradient(135deg,#f4ecd4,#fffdf8); height:80px; display:flex; align-items:center; justify-content:center; font-size:28px;">⚖️</div>
                    <div style="padding:10px 12px;">
                        <div style="font-weight:600; font-size:13px;"><?= $proc !== '' ? htmlspecialchars($proc, ENT_QUOTES, 'UTF-8') : 'Comparação' ?></div>
                        <?php if ($dateFmt !== ''): ?>
                            <div class="lc-muted" style="font-size:12px; margin-top:2px;"><?= htmlspecialchars($dateFmt, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Galeria de imagens -->
<?php if (empty($images)): ?>
    <div class="lc-card">
        <div class="lc-card__body" style="text-align:center; padding:40px 20px;">
            <div style="font-size:36px; margin-bottom:10px;">🖼</div>
            <div class="lc-muted">Nenhuma imagem cadastrada.</div>
            <?php if ($can('medical_images.upload')): ?>
                <button type="button" class="lc-btn lc-btn--primary" style="margin-top:12px;" onclick="toggleForm('form-upload')">+ Enviar primeira imagem</button>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="lc-muted" style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px;">Imagens</div>
    <div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));">
        <?php foreach ($images as $img): ?>
            <?php
            $imgId = (int)$img['id'];
            $k = (string)($img['kind'] ?? '');
            $kLabel = $kindLabel[$k] ?? $k;
            $proc = trim((string)($img['procedure_type'] ?? ''));
            $takenAt = (string)($img['taken_at'] ?? $img['created_at'] ?? '');
            $dateFmt = '';
            try { $dateFmt = $takenAt !== '' ? (new \DateTimeImmutable($takenAt))->format('d/m/Y') : ''; } catch (\Throwable $e) {}
            ?>
            <div class="lc-card" style="margin:0; overflow:hidden;">
                <!-- Preview da imagem -->
                <a href="/medical-images/file?id=<?= $imgId ?>" target="_blank" style="display:block;">
                    <div style="height:120px; background:#f3f4f6; display:flex; align-items:center; justify-content:center; overflow:hidden; position:relative;">
                        <img
                            src="/medical-images/file?id=<?= $imgId ?>"
                            alt="<?= htmlspecialchars($kLabel, ENT_QUOTES, 'UTF-8') ?>"
                            style="width:100%; height:100%; object-fit:cover;"
                            loading="lazy"
                            onerror="this.parentElement.innerHTML='<span style=\'font-size:32px;\'>🖼</span>'"
                        />
                    </div>
                </a>
                <div style="padding:8px 10px;">
                    <div style="font-size:12px; font-weight:600;"><?= htmlspecialchars($kLabel, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if ($proc !== ''): ?>
                        <div class="lc-muted" style="font-size:11px; margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($proc, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <?php if ($dateFmt !== ''): ?>
                        <div class="lc-muted" style="font-size:11px;"><?= htmlspecialchars($dateFmt, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <?php if ($can('files.read')): ?>
                        <div class="lc-flex lc-gap-sm" style="margin-top:6px;">
                            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/medical-images/file?id=<?= $imgId ?>" target="_blank" style="flex:1; text-align:center; font-size:11px;">Ver</a>
                            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/medical-images/annotate?id=<?= $imgId ?>" style="flex:1; text-align:center; font-size:11px;">Marcar</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function toggleForm(id) {
    var el = document.getElementById(id);
    if (!el) return;
    var isOpen = el.style.display !== 'none';
    // Fechar todos os formulários primeiro
    ['form-upload','form-pair'].forEach(function(fid){
        var f = document.getElementById(fid);
        if (f) f.style.display = 'none';
    });
    // Abrir o clicado se estava fechado
    if (!isOpen) {
        el.style.display = 'block';
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
