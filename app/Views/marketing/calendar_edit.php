<?php
/** @var array<string,mixed> $row */
/** @var list<array<string,mixed>> $users */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Agenda de Marketing - Editar';

$row = $row ?? null;
$users = $users ?? [];

$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

$id = (int)($row['id'] ?? 0);
$entryDate = (string)($row['entry_date'] ?? '');
$contentType = (string)($row['content_type'] ?? 'post');
$status = (string)($row['status'] ?? 'planned');
$color = (string)($row['color'] ?? '#64748b');
$titleValue = (string)($row['title'] ?? '');
$notes = (string)($row['notes'] ?? '');
$linkUrl = (string)($row['link_url'] ?? '');
$assignedUserId = (int)($row['assigned_user_id'] ?? 0);

$month = '';
if ($entryDate !== '' && strlen($entryDate) >= 7) {
    $month = substr($entryDate, 0, 7) . '-01';
}

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
        return true;
    }
    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) return false;
    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) return false;
        return in_array($permissionCode, $permissions['allow'], true);
    }
    return in_array($permissionCode, $permissions, true);
};

$statusLabel = ['planned'=>'Planejado','produced'=>'Produzido','posted'=>'Postado','cancelled'=>'Cancelado'];
$statusColor = ['planned'=>'#6b7280','produced'=>'#eeb810','posted'=>'#16a34a','cancelled'=>'#b91c1c'];
$typeLabel = ['post'=>'Post','story'=>'Story','reel'=>'Reel','video'=>'Vídeo','email'=>'Email','blog'=>'Blog','ad'=>'Anúncio','other'=>'Outro'];

$stColor = $statusColor[$status] ?? '#6b7280';
$stLabel = $statusLabel[$status] ?? $status;
$tpLabel = $typeLabel[$contentType] ?? $contentType;

$backUrl = '/marketing/calendar' . ($month !== '' ? ('?month=' . urlencode($month)) : '');

ob_start();
?>

<style>
.mke-header{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px}
.mke-back{display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.72);font-weight:650;font-size:13px;text-decoration:none;padding:8px 14px;border-radius:12px;border:1px solid rgba(17,24,39,.10);background:var(--lc-surface-3);transition:all 160ms ease}
.mke-back:hover{border-color:rgba(129,89,1,.22);background:rgba(238,184,16,.08);color:rgba(129,89,1,1)}
.mke-back svg{flex-shrink:0}
.mke-summary{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.mke-summary__color{width:14px;height:14px;border-radius:999px;border:2px solid rgba(0,0,0,.10);flex-shrink:0}
.mke-summary__title{font-weight:850;font-size:18px;color:rgba(31,41,55,.96)}
.mke-summary__badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.3px;text-transform:uppercase}
.mke-summary__type{background:rgba(107,114,128,.10);color:rgba(31,41,55,.78)}
.mke-layout{display:grid;grid-template-columns:1fr 320px;gap:18px;align-items:start}
.mke-main{display:flex;flex-direction:column;gap:16px}
.mke-side{display:flex;flex-direction:column;gap:16px}
.mke-section{border:1px solid rgba(17,24,39,.08);border-radius:14px;background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);padding:18px}
.mke-section__title{font-weight:750;font-size:13px;color:rgba(31,41,55,.60);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px}
.mke-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.mke-row--3{grid-template-columns:1fr 1fr 80px}
.mke-links{display:flex;flex-direction:column;gap:8px}
.mke-link-row{display:flex;gap:8px;align-items:center}
.mke-link-row input{flex:1}
.mke-link-add{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:10px;border:1px dashed rgba(17,24,39,.16);background:transparent;color:rgba(31,41,55,.60);font-size:12px;font-weight:650;cursor:pointer;transition:all 160ms ease}
.mke-link-add:hover{border-color:rgba(129,89,1,.30);color:rgba(129,89,1,1);background:rgba(238,184,16,.06)}
.mke-info{display:flex;flex-direction:column;gap:10px}
.mke-info__row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(17,24,39,.06)}
.mke-info__row:last-child{border-bottom:none}
.mke-info__label{font-size:12px;color:rgba(31,41,55,.55);font-weight:600}
.mke-info__value{font-size:13px;font-weight:700;color:rgba(31,41,55,.90)}
.mke-actions{display:flex;gap:10px;flex-wrap:wrap}
.mke-delete{margin-top:8px}
.mke-delete summary{list-style:none;cursor:pointer}
.mke-delete summary::-webkit-details-marker{display:none}
.mke-delete__trigger{display:inline-flex;align-items:center;gap:5px;font-size:12px;color:rgba(185,28,28,.70);font-weight:600;cursor:pointer;padding:6px 0}
.mke-delete__trigger:hover{color:rgba(185,28,28,1)}
.mke-delete__content{margin-top:8px;padding:12px;border-radius:12px;border:1px solid rgba(185,28,28,.18);background:rgba(185,28,28,.04)}
.mke-delete__warn{font-size:12px;color:rgba(185,28,28,.80);margin-bottom:10px}
.mke-readonly{padding:10px 0}
.mke-readonly__row{display:flex;gap:12px;padding:8px 0;border-bottom:1px solid rgba(17,24,39,.06)}
.mke-readonly__row:last-child{border-bottom:none}
.mke-readonly__label{min-width:100px;font-size:12px;color:rgba(31,41,55,.55);font-weight:600}
.mke-readonly__value{font-size:13px;color:rgba(31,41,55,.90)}
@media(max-width:860px){.mke-layout{grid-template-columns:1fr}.mke-side{order:-1}}
@media(max-width:520px){.mke-row,.mke-row--3{grid-template-columns:1fr}}
</style>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Cabeçalho -->
<div class="mke-header">
    <div class="mke-summary">
        <div class="mke-summary__color" style="background:<?= htmlspecialchars($color !== '' ? $color : '#64748b', ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="mke-summary__title"><?= htmlspecialchars($titleValue !== '' ? $titleValue : 'Item', ENT_QUOTES, 'UTF-8') ?></div>
        <span class="mke-summary__badge mke-summary__type"><?= htmlspecialchars($tpLabel, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="mke-summary__badge" style="background:<?= $stColor ?>22;color:<?= $stColor ?>;border:1px solid <?= $stColor ?>33"><?= htmlspecialchars($stLabel, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <a class="mke-back" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        Voltar ao calendário
    </a>
</div>

<?php if ($can('marketing.calendar.manage')): ?>
<!-- Layout 2 colunas: form + sidebar info -->
<form method="post" action="/marketing/calendar/update" class="lc-form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="id" value="<?= $id ?>" />
    <?php if ($month !== ''): ?>
        <input type="hidden" name="month" value="<?= htmlspecialchars($month, ENT_QUOTES, 'UTF-8') ?>" />
    <?php endif; ?>

    <div class="mke-layout">
        <div class="mke-main">
            <!-- Dados principais -->
            <div class="mke-section">
                <div class="mke-section__title">Informações</div>
                <div class="lc-field">
                    <label class="lc-label">Título</label>
                    <input class="lc-input" type="text" name="title" value="<?= htmlspecialchars($titleValue, ENT_QUOTES, 'UTF-8') ?>" required placeholder="Ex: Post sobre Botox, Reel de resultados..." />
                </div>
                <div class="mke-row" style="margin-top:4px;">
                    <div class="lc-field">
                        <label class="lc-label">Data</label>
                        <input class="lc-input" type="date" name="entry_date" value="<?= htmlspecialchars($entryDate, ENT_QUOTES, 'UTF-8') ?>" required />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Responsável</label>
                        <select class="lc-select" name="assigned_user_id">
                            <option value="">Nenhum</option>
                            <?php foreach ($users as $u): ?>
                                <?php $uid = (int)($u['id'] ?? 0); if ($uid <= 0) continue; ?>
                                <?php
                                    $nm = trim((string)($u['name'] ?? ''));
                                    $em = trim((string)($u['email'] ?? ''));
                                    $lbl = $nm !== '' ? $nm : $em;
                                    if ($nm !== '' && $em !== '') $lbl = $nm . ' (' . $em . ')';
                                ?>
                                <option value="<?= $uid ?>" <?= $assignedUserId === $uid ? 'selected' : '' ?>><?= htmlspecialchars($lbl !== '' ? $lbl : ('Usuário #' . $uid), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mke-row--3" style="display:grid;margin-top:4px;">
                    <div class="lc-field">
                        <label class="lc-label">Tipo</label>
                        <select class="lc-select" name="content_type">
                            <?php foreach ($typeLabel as $k=>$lbl): ?>
                                <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $contentType === $k ? 'selected' : '' ?>><?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Status</label>
                        <select class="lc-select" name="status">
                            <?php foreach ($statusLabel as $k=>$lbl): ?>
                                <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $status === $k ? 'selected' : '' ?>><?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Cor</label>
                        <input class="lc-input" type="color" name="color" value="<?= htmlspecialchars($color !== '' ? $color : '#64748b', ENT_QUOTES, 'UTF-8') ?>" />
                    </div>
                </div>
            </div>

            <!-- Notas -->
            <div class="mke-section">
                <div class="mke-section__title">Notas</div>
                <textarea class="lc-input" name="notes" rows="4" placeholder="Ideias, referências, briefing..."><?= htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- Links -->
            <div class="mke-section">
                <div class="mke-section__title">Links de referência</div>
                <div class="mke-links" id="linksContainer">
                    <?php
                    $existingLinks = \App\Services\Marketing\MarketingCalendarService::decodeLinks($linkUrl !== '' ? $linkUrl : null);
                    if ($existingLinks === []) $existingLinks = [''];
                    foreach ($existingLinks as $lk):
                    ?>
                    <div class="mke-link-row">
                        <input class="lc-input" type="url" name="links[]" value="<?= htmlspecialchars($lk, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://..." />
                        <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="removeLink(this)" title="Remover">✕</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="mke-link-add" onclick="addLink()" style="margin-top:8px;">+ Adicionar link</button>
            </div>

            <!-- Ações -->
            <div class="mke-actions">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar alterações</button>
                <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>">Cancelar</a>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="mke-side">
            <div class="mke-section">
                <div class="mke-section__title">Resumo</div>
                <div class="mke-info">
                    <div class="mke-info__row">
                        <span class="mke-info__label">Data</span>
                        <span class="mke-info__value"><?= htmlspecialchars($entryDate !== '' ? date('d/m/Y', strtotime($entryDate)) : '—', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="mke-info__row">
                        <span class="mke-info__label">Tipo</span>
                        <span class="mke-info__value"><?= htmlspecialchars($tpLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="mke-info__row">
                        <span class="mke-info__label">Status</span>
                        <span class="mke-info__value" style="color:<?= $stColor ?>"><?= htmlspecialchars($stLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="mke-info__row">
                        <span class="mke-info__label">Cor</span>
                        <span class="mke-info__value"><span style="display:inline-block;width:16px;height:16px;border-radius:6px;background:<?= htmlspecialchars($color !== '' ? $color : '#64748b', ENT_QUOTES, 'UTF-8') ?>;vertical-align:middle;border:1px solid rgba(0,0,0,.10)"></span></span>
                    </div>
                    <?php
                    $assignedName = '—';
                    if ($assignedUserId > 0) {
                        foreach ($users as $u) {
                            if ((int)($u['id'] ?? 0) === $assignedUserId) {
                                $assignedName = trim((string)($u['name'] ?? $u['email'] ?? ''));
                                break;
                            }
                        }
                    }
                    ?>
                    <div class="mke-info__row">
                        <span class="mke-info__label">Responsável</span>
                        <span class="mke-info__value"><?= htmlspecialchars($assignedName, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>

            <!-- Excluir -->
            <div class="mke-section">
                <details class="mke-delete">
                    <summary>
                        <span class="mke-delete__trigger">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            Excluir este item
                        </span>
                    </summary>
                    <div class="mke-delete__content">
                        <div class="mke-delete__warn">Esta ação não pode ser desfeita. O item será removido permanentemente da agenda.</div>
                        <form method="post" action="/marketing/calendar/delete" onsubmit="return confirm('Tem certeza que deseja excluir?');">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="id" value="<?= $id ?>" />
                            <?php if ($month !== ''): ?>
                                <input type="hidden" name="month" value="<?= htmlspecialchars($month, ENT_QUOTES, 'UTF-8') ?>" />
                            <?php endif; ?>
                            <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit">Confirmar exclusão</button>
                        </form>
                    </div>
                </details>
            </div>
        </div>
    </div>
</form>

<?php else: ?>
<!-- Somente leitura -->
<div class="mke-layout">
    <div class="mke-main">
        <div class="mke-section">
            <div class="mke-section__title">Detalhes</div>
            <div class="mke-readonly">
                <div class="mke-readonly__row">
                    <span class="mke-readonly__label">Título</span>
                    <span class="mke-readonly__value"><?= htmlspecialchars($titleValue, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="mke-readonly__row">
                    <span class="mke-readonly__label">Data</span>
                    <span class="mke-readonly__value"><?= htmlspecialchars($entryDate !== '' ? date('d/m/Y', strtotime($entryDate)) : '—', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="mke-readonly__row">
                    <span class="mke-readonly__label">Tipo</span>
                    <span class="mke-readonly__value"><?= htmlspecialchars($tpLabel, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="mke-readonly__row">
                    <span class="mke-readonly__label">Status</span>
                    <span class="mke-readonly__value" style="color:<?= $stColor ?>"><?= htmlspecialchars($stLabel, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php $readLinks = \App\Services\Marketing\MarketingCalendarService::decodeLinks($linkUrl !== '' ? $linkUrl : null); ?>
                <?php if ($readLinks !== []): ?>
                <div class="mke-readonly__row" style="flex-direction:column;gap:4px;">
                    <span class="mke-readonly__label">Links</span>
                    <?php foreach ($readLinks as $lk): ?>
                        <a href="<?= htmlspecialchars($lk, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="lc-link" style="font-size:13px;word-break:break-all;"><?= htmlspecialchars($lk, ENT_QUOTES, 'UTF-8') ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if ($notes !== ''): ?>
                <div class="mke-readonly__row" style="flex-direction:column;gap:4px;">
                    <span class="mke-readonly__label">Notas</span>
                    <span class="mke-readonly__value"><?= nl2br(htmlspecialchars($notes, ENT_QUOTES, 'UTF-8')) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>">Voltar ao calendário</a>
        </div>
    </div>
    <div class="mke-side">
        <div class="mke-section">
            <div class="mke-section__title">Resumo</div>
            <div class="mke-info">
                <div class="mke-info__row">
                    <span class="mke-info__label">Data</span>
                    <span class="mke-info__value"><?= htmlspecialchars($entryDate !== '' ? date('d/m/Y', strtotime($entryDate)) : '—', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="mke-info__row">
                    <span class="mke-info__label">Tipo</span>
                    <span class="mke-info__value"><?= htmlspecialchars($tpLabel, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="mke-info__row">
                    <span class="mke-info__label">Status</span>
                    <span class="mke-info__value" style="color:<?= $stColor ?>"><?= htmlspecialchars($stLabel, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function addLink() {
    var c = document.getElementById('linksContainer');
    if (!c) return;
    var row = document.createElement('div');
    row.className = 'mke-link-row';
    row.innerHTML = '<input class="lc-input" type="url" name="links[]" placeholder="https://..." />' +
        '<button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="removeLink(this)" title="Remover">✕</button>';
    c.appendChild(row);
    row.querySelector('input').focus();
}
function removeLink(btn) {
    var row = btn.closest('.mke-link-row');
    var c = document.getElementById('linksContainer');
    if (!c || !row) return;
    if (c.children.length <= 1) {
        row.querySelector('input').value = '';
        return;
    }
    row.remove();
}
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
