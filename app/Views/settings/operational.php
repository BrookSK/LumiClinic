<?php
$title = 'Configuração Operacional';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? '';
$saved = $saved ?? '';

$stages = isset($stages) && is_array($stages) ? $stages : [];
$lostReasons = isset($lost_reasons) && is_array($lost_reasons) ? $lost_reasons : [];
$origins = isset($origins) && is_array($origins) ? $origins : [];

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) return false;
    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) return false;
        return in_array($permissionCode, $permissions['allow'], true);
    }
    return in_array($permissionCode, $permissions, true);
};

ob_start();
?>

<style>
.op-back{display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px}
.op-back:hover{color:rgba(129,89,1,1)}
.op-section{padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px}
.op-section__head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:6px}
.op-section__title{font-weight:750;font-size:14px;color:rgba(31,41,55,.90)}
.op-section__count{font-size:12px;color:rgba(31,41,55,.45);font-weight:600}
.op-section__desc{font-size:12px;color:rgba(31,41,55,.45);line-height:1.5;margin-bottom:14px}
.op-items{display:flex;flex-direction:column;gap:6px}
.op-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01)}
.op-item__name{font-weight:700;font-size:13px;color:rgba(31,41,55,.90)}
.op-item__order{font-size:11px;color:rgba(31,41,55,.40)}
.op-empty{text-align:center;padding:16px;color:rgba(31,41,55,.40);font-size:13px}
.op-add{margin-top:10px}
.op-add summary{list-style:none;cursor:pointer;display:inline-flex;align-items:center;gap:5px;font-size:13px;font-weight:650;color:rgba(129,89,1,1);padding:6px 0}
.op-add summary::-webkit-details-marker{display:none}
.op-add summary:hover{color:rgba(129,89,1,.80)}
.op-add__form{margin-top:10px;padding:14px;border-radius:12px;border:1px solid rgba(238,184,16,.18);background:rgba(253,229,159,.08)}
</style>

<a href="/settings" class="op-back">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para configurações
</a>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:6px;">Configuração operacional</div>
<div style="font-size:13px;color:rgba(31,41,55,.50);margin-bottom:18px;max-width:600px;line-height:1.5;">
    Aqui você gerencia as listas usadas no dia a dia da clínica. Essas listas aparecem em formulários e relatórios do sistema.
</div>

<?php if (is_string($error) && trim($error) !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (is_string($saved) && trim($saved) !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;">Salvo com sucesso.</div>
<?php endif; ?>

<!-- Etapas do funil -->
<div class="op-section">
    <div class="op-section__head">
        <span class="op-section__title">Etapas do funil de atendimento</span>
        <span class="op-section__count"><?= count($stages) ?> etapa(s)</span>
    </div>
    <div class="op-section__desc">
        O funil organiza o fluxo do paciente desde o primeiro contato até a conversão. Exemplo: Novo contato → Triagem → Orçamento → Agendamento → Convertido.
    </div>

    <?php if ($stages === []): ?>
        <div class="op-empty">Nenhuma etapa cadastrada ainda.</div>
    <?php else: ?>
        <div class="op-items">
            <?php foreach ($stages as $s): ?>
                <div class="op-item">
                    <div>
                        <span class="op-item__name"><?= htmlspecialchars((string)($s['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="op-item__order">Ordem: <?= (int)($s['sort_order'] ?? 0) ?></span>
                    </div>
                    <?php if ($can('settings.update')): ?>
                    <form method="post" action="/settings/operational/funnel-stages/delete" style="margin:0;" onsubmit="return confirm('Remover esta etapa?');">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="hidden" name="id" value="<?= (int)($s['id'] ?? 0) ?>" />
                        <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Remover</button>
                    </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($can('settings.update')): ?>
    <details class="op-add">
        <summary>+ Adicionar etapa</summary>
        <div class="op-add__form">
            <form method="post" action="/settings/operational/funnel-stages/create" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <div class="lc-field" style="flex:2;min-width:180px;"><label class="lc-label">Nome</label><input class="lc-input" type="text" name="name" required placeholder="Ex: Triagem" /></div>
                <div class="lc-field" style="flex:1;min-width:80px;"><label class="lc-label">Ordem</label><input class="lc-input" type="number" name="sort_order" value="0" /></div>
                <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Adicionar</button>
            </form>
        </div>
    </details>
    <?php endif; ?>
</div>

<!-- Motivos de perda -->
<div class="op-section">
    <div class="op-section__head">
        <span class="op-section__title">Motivos de perda</span>
        <span class="op-section__count"><?= count($lostReasons) ?> motivo(s)</span>
    </div>
    <div class="op-section__desc">
        Quando um atendimento não vai adiante, registre o motivo. Isso ajuda a entender por que pacientes desistem. Exemplo: Sem orçamento, Sem agenda, Desistiu, Preço alto.
    </div>

    <?php if ($lostReasons === []): ?>
        <div class="op-empty">Nenhum motivo cadastrado ainda.</div>
    <?php else: ?>
        <div class="op-items">
            <?php foreach ($lostReasons as $r): ?>
                <div class="op-item">
                    <div>
                        <span class="op-item__name"><?= htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="op-item__order">Ordem: <?= (int)($r['sort_order'] ?? 0) ?></span>
                    </div>
                    <?php if ($can('settings.update')): ?>
                    <form method="post" action="/settings/operational/lost-reasons/delete" style="margin:0;" onsubmit="return confirm('Remover este motivo?');">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>" />
                        <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Remover</button>
                    </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($can('settings.update')): ?>
    <details class="op-add">
        <summary>+ Adicionar motivo</summary>
        <div class="op-add__form">
            <form method="post" action="/settings/operational/lost-reasons/create" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <div class="lc-field" style="flex:2;min-width:180px;"><label class="lc-label">Nome</label><input class="lc-input" type="text" name="name" required placeholder="Ex: Preço alto" /></div>
                <div class="lc-field" style="flex:1;min-width:80px;"><label class="lc-label">Ordem</label><input class="lc-input" type="number" name="sort_order" value="0" /></div>
                <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Adicionar</button>
            </form>
        </div>
    </details>
    <?php endif; ?>
</div>

<!-- Origem do paciente -->
<div class="op-section">
    <div class="op-section__head">
        <span class="op-section__title">Origem do paciente</span>
        <span class="op-section__count"><?= count($origins) ?> origem(ns)</span>
    </div>
    <div class="op-section__desc">
        De onde seus pacientes vêm? Cadastre as origens para rastrear nos relatórios. Exemplo: Instagram, Indicação, Google, Site, WhatsApp.
    </div>

    <?php if ($origins === []): ?>
        <div class="op-empty">Nenhuma origem cadastrada ainda.</div>
    <?php else: ?>
        <div class="op-items">
            <?php foreach ($origins as $o): ?>
                <div class="op-item">
                    <div>
                        <span class="op-item__name"><?= htmlspecialchars((string)($o['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="op-item__order">Ordem: <?= (int)($o['sort_order'] ?? 0) ?></span>
                    </div>
                    <?php if ($can('settings.update')): ?>
                    <form method="post" action="/settings/operational/patient-origins/delete" style="margin:0;" onsubmit="return confirm('Remover esta origem?');">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="hidden" name="id" value="<?= (int)($o['id'] ?? 0) ?>" />
                        <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Remover</button>
                    </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($can('settings.update')): ?>
    <details class="op-add">
        <summary>+ Adicionar origem</summary>
        <div class="op-add__form">
            <form method="post" action="/settings/operational/patient-origins/create" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <div class="lc-field" style="flex:2;min-width:180px;"><label class="lc-label">Nome</label><input class="lc-input" type="text" name="name" required placeholder="Ex: Instagram" /></div>
                <div class="lc-field" style="flex:1;min-width:80px;"><label class="lc-label">Ordem</label><input class="lc-input" type="number" name="sort_order" value="0" /></div>
                <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Adicionar</button>
            </form>
        </div>
    </details>
    <?php endif; ?>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
