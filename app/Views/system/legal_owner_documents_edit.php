<?php
$title = 'Admin - Termo do Owner';
$csrf = $_SESSION['_csrf'] ?? '';
$doc = $doc ?? null;
$clinics = $clinics ?? [];
$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

$isNew = (int)($doc['id'] ?? 0) <= 0;

ob_start();
?>

<a href="/sys/legal-owner-documents" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para termos
</a>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:18px;"><?= $isNew ? 'Novo termo' : 'Editar termo' ?></div>

<?php if ($error): ?><div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($success): ?><div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);max-width:700px;">
    <form method="post" action="/sys/legal-owner-documents/save">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="id" value="<?= (int)($doc['id'] ?? 0) ?>" />

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="lc-field">
                <label class="lc-label">Aplicação</label>
                <?php $dcid = $doc['clinic_id'] ?? null; ?>
                <select class="lc-select" name="clinic_id">
                    <option value="0" <?= $dcid === null ? 'selected' : '' ?>>Global (todas as clínicas)</option>
                    <?php foreach ($clinics as $c): ?>
                        <?php $cid = (int)($c['id'] ?? 0); ?>
                        <option value="<?= $cid ?>" <?= ($dcid !== null && (int)$dcid === $cid) ? 'selected' : '' ?>><?= htmlspecialchars((string)($c['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Global = vale para todos os owners.</div>
            </div>
            <div class="lc-field">
                <label class="lc-label">Status</label>
                <?php $st = (string)($doc['status'] ?? 'active'); ?>
                <select class="lc-select" name="status">
                    <option value="active" <?= $st === 'active' ? 'selected' : '' ?>>Ativo</option>
                    <option value="disabled" <?= $st === 'disabled' ? 'selected' : '' ?>>Inativo</option>
                </select>
            </div>
        </div>

        <div class="lc-field">
            <label class="lc-label">Título</label>
            <input class="lc-input" type="text" name="title" value="<?= htmlspecialchars((string)($doc['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required placeholder="Ex: Termos de Uso, Política de Privacidade..." />
        </div>

        <div class="lc-field">
            <label class="lc-label">Conteúdo</label>
            <textarea class="lc-input" name="body" rows="10" required placeholder="Texto completo do termo..."><?= htmlspecialchars((string)($doc['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="lc-field">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 12px;border-radius:10px;border:1px solid rgba(17,24,39,.08);background:rgba(0,0,0,.01);">
                <input type="checkbox" name="is_required" value="1" <?= (int)($doc['is_required'] ?? 0) === 1 ? 'checked' : '' ?> style="width:18px;height:18px;" />
                <div>
                    <div style="font-weight:700;font-size:13px;">Aceite obrigatório</div>
                    <div style="font-size:11px;color:rgba(31,41,55,.45);">O owner não consegue usar o sistema sem aceitar.</div>
                </div>
            </label>
        </div>

        <div style="display:flex;gap:10px;margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/sys/legal-owner-documents">Cancelar</a>
        </div>
    </form>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
