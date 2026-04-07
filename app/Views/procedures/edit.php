<?php
/** @var array<string,mixed> $procedure */
/** @var list<array<string,mixed>> $protocols */
/** @var array<string,list<array<string,mixed>>> $steps_by_protocol */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Protocolo Clínico';
$error = is_string($error ?? null) ? (string)$error : '';
$success = is_string($success ?? null) ? (string)$success : '';
$e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$can = function (string $pc): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $p = $_SESSION['permissions'] ?? [];
    if (!is_array($p)) return false;
    if (isset($p['allow'],$p['deny'])&&is_array($p['allow'])&&is_array($p['deny'])) {
        if (in_array($pc,$p['deny'],true)) return false;
        return in_array($pc,$p['allow'],true);
    }
    return in_array($pc,$p,true);
};

$procedureId = (int)($procedure['id'] ?? 0);
$ro = $can('procedures.manage');
ob_start();
?>

<a href="/procedures" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:12px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Protocolos Clínicos
</a>

<?php if ($error !== ''): ?><div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= $e($error) ?></div><?php endif; ?>
<?php if ($success !== ''): ?><div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= $e($success) ?></div><?php endif; ?>

<div style="font-weight:850;font-size:20px;color:#1f2937;margin-bottom:18px;"><?= $e((string)($procedure['name'] ?? '')) ?></div>

<!-- Informações gerais -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px;">
    <div style="padding:20px;border-radius:14px;border:1px solid #e5e7eb;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,.06);">
        <div style="font-weight:750;font-size:14px;color:#1f2937;margin-bottom:12px;">Informações do protocolo</div>
        <?php if ($ro): ?>
        <form method="post" action="/procedures/edit" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
            <input type="hidden" name="id" value="<?= $procedureId ?>" />
            <div class="lc-field"><label class="lc-label">Nome</label><input class="lc-input" type="text" name="name" value="<?= $e((string)($procedure['name'] ?? '')) ?>" required /></div>
            <div class="lc-field" style="margin-top:10px;"><label class="lc-label">Status</label>
                <select class="lc-select" name="status">
                    <option value="active" <?= ($procedure['status'] ?? '') === 'active' ? 'selected' : '' ?>>Ativo</option>
                    <option value="disabled" <?= ($procedure['status'] ?? '') === 'disabled' ? 'selected' : '' ?>>Desativado</option>
                </select>
            </div>
            <div style="margin-top:12px;"><button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Salvar</button></div>
        </form>
        <?php else: ?>
            <div style="font-size:13px;color:#6b7280;">Status: <?= $e((string)($procedure['status'] ?? '')) ?></div>
        <?php endif; ?>
    </div>

    <div style="display:flex;flex-direction:column;gap:12px;">
        <!-- Contraindicações -->
        <div style="padding:16px;border-radius:14px;border:1px solid rgba(239,68,68,.15);background:rgba(239,68,68,.03);">
            <div style="font-weight:700;font-size:13px;color:#b91c1c;margin-bottom:6px;">⚠️ Contraindicações</div>
            <?php if ($ro): ?>
            <form method="post" action="/procedures/edit"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" /><input type="hidden" name="id" value="<?= $procedureId ?>" /><input type="hidden" name="name" value="<?= $e((string)($procedure['name'] ?? '')) ?>" /><input type="hidden" name="status" value="<?= $e((string)($procedure['status'] ?? 'active')) ?>" />
                <textarea class="lc-textarea" name="contraindications" rows="3" style="font-size:13px;"><?= $e((string)($procedure['contraindications'] ?? '')) ?></textarea>
                <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="margin-top:6px;">Salvar</button>
            </form>
            <?php else: ?>
                <div style="font-size:13px;color:#6b7280;white-space:pre-wrap;"><?= nl2br($e((string)($procedure['contraindications'] ?? 'Nenhuma'))) ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Orientações -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px;">
    <div style="padding:16px;border-radius:14px;border:1px solid rgba(99,102,241,.15);background:rgba(99,102,241,.03);">
        <div style="font-weight:700;font-size:13px;color:rgba(99,102,241,.8);margin-bottom:6px;">📋 Orientações pré-procedimento</div>
        <?php if ($ro): ?>
        <form method="post" action="/procedures/edit"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" /><input type="hidden" name="id" value="<?= $procedureId ?>" /><input type="hidden" name="name" value="<?= $e((string)($procedure['name'] ?? '')) ?>" /><input type="hidden" name="status" value="<?= $e((string)($procedure['status'] ?? 'active')) ?>" />
            <textarea class="lc-textarea" name="pre_guidelines" rows="3" style="font-size:13px;"><?= $e((string)($procedure['pre_guidelines'] ?? '')) ?></textarea>
            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="margin-top:6px;">Salvar</button>
        </form>
        <?php else: ?>
            <div style="font-size:13px;color:#6b7280;white-space:pre-wrap;"><?= nl2br($e((string)($procedure['pre_guidelines'] ?? 'Nenhuma'))) ?></div>
        <?php endif; ?>
    </div>
    <div style="padding:16px;border-radius:14px;border:1px solid rgba(34,197,94,.15);background:rgba(34,197,94,.03);">
        <div style="font-weight:700;font-size:13px;color:#16a34a;margin-bottom:6px;">✅ Orientações pós-procedimento</div>
        <?php if ($ro): ?>
        <form method="post" action="/procedures/edit"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" /><input type="hidden" name="id" value="<?= $procedureId ?>" /><input type="hidden" name="name" value="<?= $e((string)($procedure['name'] ?? '')) ?>" /><input type="hidden" name="status" value="<?= $e((string)($procedure['status'] ?? 'active')) ?>" />
            <textarea class="lc-textarea" name="post_guidelines" rows="3" style="font-size:13px;"><?= $e((string)($procedure['post_guidelines'] ?? '')) ?></textarea>
            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="margin-top:6px;">Salvar</button>
        </form>
        <?php else: ?>
            <div style="font-size:13px;color:#6b7280;white-space:pre-wrap;"><?= nl2br($e((string)($procedure['post_guidelines'] ?? 'Nenhuma'))) ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- Protocolos e Etapas -->
<div style="padding:20px;border-radius:14px;border:1px solid #e5e7eb;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,.06);margin-bottom:18px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <div style="font-weight:750;font-size:14px;color:#1f2937;">Protocolos e etapas</div>
        <?php if ($ro): ?>
        <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" onclick="document.getElementById('newProtForm').style.display=document.getElementById('newProtForm').style.display==='none'?'block':'none';">+ Novo protocolo</button>
        <?php endif; ?>
    </div>

    <?php if ($ro): ?>
    <div id="newProtForm" style="display:none;padding:14px;border-radius:10px;background:rgba(99,102,241,.03);border:1px solid rgba(99,102,241,.12);margin-bottom:14px;">
        <form method="post" action="/procedures/protocols/create" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
            <input type="hidden" name="procedure_id" value="<?= $procedureId ?>" />
            <div style="display:grid;grid-template-columns:2fr 80px;gap:10px;align-items:end;">
                <div class="lc-field"><label class="lc-label">Nome do protocolo</label><input class="lc-input" type="text" name="name" required placeholder="Ex: Protocolo padrão" /></div>
                <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Criar</button>
            </div>
            <div class="lc-field" style="margin-top:8px;"><label class="lc-label">Notas (opcional)</label><textarea class="lc-textarea" name="notes" rows="2"></textarea></div>
            <input type="hidden" name="sort_order" value="0" />
        </form>
    </div>
    <?php endif; ?>

    <?php if (empty($protocols)): ?>
        <div style="text-align:center;padding:30px;color:#9ca3af;font-size:13px;">Nenhum protocolo cadastrado. Crie um protocolo para adicionar etapas ao procedimento.</div>
    <?php else: ?>
        <?php foreach ($protocols as $p): ?>
        <?php $pid = (int)$p['id']; $steps = $steps_by_protocol[(string)$pid] ?? []; ?>
        <details style="margin-bottom:12px;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;" open>
            <summary style="padding:14px 16px;background:rgba(0,0,0,.02);cursor:pointer;display:flex;align-items:center;justify-content:space-between;font-weight:700;font-size:14px;color:#1f2937;">
                <span><?= $e((string)($p['name'] ?? '')) ?></span>
                <span style="font-size:11px;color:#9ca3af;font-weight:500;"><?= count($steps) ?> etapa(s)</span>
            </summary>
            <div style="padding:14px 16px;">
                <?php if ($ro): ?>
                <div style="display:flex;gap:8px;margin-bottom:12px;">
                    <form method="post" action="/procedures/protocols/update" style="display:flex;gap:8px;align-items:center;flex:1;">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" /><input type="hidden" name="procedure_id" value="<?= $procedureId ?>" /><input type="hidden" name="id" value="<?= $pid ?>" /><input type="hidden" name="status" value="<?= $e((string)($p['status'] ?? 'active')) ?>" /><input type="hidden" name="sort_order" value="<?= (int)($p['sort_order'] ?? 0) ?>" /><input type="hidden" name="notes" value="<?= $e((string)($p['notes'] ?? '')) ?>" />
                        <input class="lc-input" type="text" name="name" value="<?= $e((string)($p['name'] ?? '')) ?>" style="flex:1;" required />
                        <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Salvar</button>
                    </form>
                    <form method="post" action="/procedures/protocols/delete" onsubmit="return confirm('Remover protocolo e todas as etapas?');">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" /><input type="hidden" name="procedure_id" value="<?= $procedureId ?>" /><input type="hidden" name="id" value="<?= $pid ?>" />
                        <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit">Remover</button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Etapas -->
                <?php if (empty($steps)): ?>
                    <div style="color:#9ca3af;font-size:12px;padding:10px 0;">Nenhuma etapa.</div>
                <?php else: ?>
                    <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:12px;">
                    <?php foreach ($steps as $i => $s): $sid = (int)$s['id']; ?>
                        <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;background:rgba(0,0,0,.02);border:1px solid #f3f4f6;">
                            <span style="font-weight:800;font-size:14px;color:rgba(99,102,241,.6);min-width:24px;"><?= $i + 1 ?></span>
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:600;font-size:13px;color:#1f2937;"><?= $e((string)($s['title'] ?? '')) ?></div>
                                <?php if (($s['notes'] ?? '') !== ''): ?><div style="font-size:11px;color:#9ca3af;margin-top:2px;"><?= $e((string)$s['notes']) ?></div><?php endif; ?>
                            </div>
                            <?php if (($s['duration_minutes'] ?? null) !== null): ?>
                                <span style="font-size:11px;color:#6b7280;white-space:nowrap;"><?= (int)$s['duration_minutes'] ?> min</span>
                            <?php endif; ?>
                            <?php if ($ro): ?>
                            <form method="post" action="/procedures/steps/delete" onsubmit="return confirm('Remover etapa?');" style="margin:0;">
                                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" /><input type="hidden" name="procedure_id" value="<?= $procedureId ?>" /><input type="hidden" name="protocol_id" value="<?= $pid ?>" /><input type="hidden" name="id" value="<?= $sid ?>" />
                                <button type="submit" style="background:none;border:none;cursor:pointer;color:#b91c1c;font-size:14px;padding:2px;" title="Remover">✕</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($ro): ?>
                <form method="post" action="/procedures/steps/create" class="lc-form" style="padding:10px 12px;border-radius:8px;background:rgba(0,0,0,.02);border:1px dashed #d1d5db;">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" /><input type="hidden" name="procedure_id" value="<?= $procedureId ?>" /><input type="hidden" name="protocol_id" value="<?= $pid ?>" /><input type="hidden" name="sort_order" value="<?= count($steps) ?>" />
                    <div style="display:grid;grid-template-columns:1fr 80px auto;gap:8px;align-items:end;">
                        <div class="lc-field"><label class="lc-label">Nova etapa</label><input class="lc-input" type="text" name="title" required placeholder="Título da etapa" /></div>
                        <div class="lc-field"><label class="lc-label">Duração</label><input class="lc-input" type="number" name="duration_minutes" min="0" placeholder="min" /></div>
                        <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit" style="height:38px;">Adicionar</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </details>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
