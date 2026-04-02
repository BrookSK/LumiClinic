<?php
/** @var array<string,mixed> $row */
/** @var array<string,mixed> $rules */
/** @var int $audience_count */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Automação - Editar Segmento';

$row = $row ?? [];
$rules = $rules ?? [];
$audience_count = isset($audience_count) ? (int)$audience_count : 0;

$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

$id = (int)($row['id'] ?? 0);
$name = (string)($row['name'] ?? '');
$status = (string)($row['status'] ?? 'active');

$ruleStatus = (string)($rules['status'] ?? 'active');
$ruleWa = (int)($rules['whatsapp_opt_in'] ?? 1);
$rulePhone = (int)($rules['has_phone'] ?? 1);
$ruleEmail = (int)($rules['has_email'] ?? 0);

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
.ma-nav{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px}
.ma-nav a{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:12px;font-weight:700;font-size:13px;text-decoration:none;border:1px solid rgba(17,24,39,.10);color:rgba(31,41,55,.72);background:var(--lc-surface-3);transition:all 160ms ease}
.ma-nav a:hover{border-color:rgba(129,89,1,.22);color:rgba(129,89,1,1);background:rgba(238,184,16,.06)}
.ma-nav a.active{background:rgba(238,184,16,.14);border-color:rgba(129,89,1,.24);color:rgba(31,41,55,.96)}
.mse-layout{display:grid;grid-template-columns:1fr 300px;gap:18px;align-items:start}
.mse-section{padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06)}
.mse-section__title{font-weight:750;font-size:13px;color:rgba(31,41,55,.55);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px}
.mse-audience{text-align:center;padding:20px}
.mse-audience__number{font-size:36px;font-weight:900;color:rgba(129,89,1,1)}
.mse-audience__label{font-size:13px;color:rgba(31,41,55,.55);margin-top:4px}
.mse-rules{display:flex;flex-direction:column;gap:10px}
.mse-rule{display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01)}
.mse-rule__check{width:18px;height:18px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0}
.mse-rule__check--on{background:rgba(22,163,74,.14);color:#16a34a}
.mse-rule__check--off{background:rgba(107,114,128,.10);color:#6b7280}
.mse-rule__text{font-size:13px;color:rgba(31,41,55,.80)}
.mse-rule__desc{font-size:11px;color:rgba(31,41,55,.45);margin-top:2px}
@media(max-width:760px){.mse-layout{grid-template-columns:1fr}}
</style>

<!-- Navegação -->
<div class="ma-nav">
    <a href="/marketing/automation/segments">Segmentos</a>
    <a href="/marketing/automation/campaigns">Campanhas</a>
    <a href="/marketing/automation/logs">Logs de envio</a>
</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Header -->
<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:18px;color:rgba(31,41,55,.96);"><?= htmlspecialchars($name !== '' ? $name : 'Segmento', ENT_QUOTES, 'UTF-8') ?></div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Segmento #<?= $id ?></div>
    </div>
    <a href="/marketing/automation/segments" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:12px;border:1px solid rgba(17,24,39,.10);background:var(--lc-surface-3);color:rgba(31,41,55,.72);font-weight:650;font-size:13px;text-decoration:none;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        Voltar
    </a>
</div>

<?php if ($can('marketing.automation.manage')): ?>
<form method="post" action="/marketing/automation/segment/update" class="lc-form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="id" value="<?= $id ?>" />

    <div class="mse-layout">
        <div style="display:flex;flex-direction:column;gap:16px;">
            <!-- Dados -->
            <div class="mse-section">
                <div class="mse-section__title">Dados do segmento</div>
                <div class="lc-field">
                    <label class="lc-label">Nome</label>
                    <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required placeholder="Ex: Pacientes ativos com WhatsApp" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Status do segmento</label>
                    <select class="lc-select" name="status">
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>Desativado</option>
                    </select>
                </div>
            </div>

            <!-- Regras -->
            <div class="mse-section">
                <div class="mse-section__title">Regras de filtro</div>
                <div style="font-size:13px;color:rgba(31,41,55,.60);margin-bottom:14px;line-height:1.5;">
                    Defina quais pacientes fazem parte deste segmento. Apenas pacientes que atendem a todas as regras marcadas serão incluídos.
                </div>

                <div class="lc-field" style="margin-bottom:14px;">
                    <label class="lc-label">Status do paciente</label>
                    <select class="lc-select" name="rule_status" style="max-width:220px;">
                        <option value="active" <?= $ruleStatus === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inactive" <?= $ruleStatus === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                    <div style="font-size:11px;color:rgba(31,41,55,.45);margin-top:4px;">Filtra pacientes pelo status no cadastro.</div>
                </div>

                <div style="display:flex;flex-direction:column;gap:10px;">
                    <label style="display:flex;align-items:flex-start;gap:10px;padding:12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);cursor:pointer;">
                        <input type="checkbox" name="rule_whatsapp_opt_in" value="1" <?= $ruleWa === 1 ? 'checked' : '' ?> style="width:18px;height:18px;margin-top:1px;flex-shrink:0;" />
                        <div>
                            <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);">Aceitou receber WhatsApp</div>
                            <div style="font-size:11px;color:rgba(31,41,55,.50);margin-top:2px;">Apenas pacientes que deram opt-in para receber mensagens via WhatsApp.</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:10px;padding:12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);cursor:pointer;">
                        <input type="checkbox" name="rule_has_phone" value="1" <?= $rulePhone === 1 ? 'checked' : '' ?> style="width:18px;height:18px;margin-top:1px;flex-shrink:0;" />
                        <div>
                            <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);">Tem telefone cadastrado</div>
                            <div style="font-size:11px;color:rgba(31,41,55,.50);margin-top:2px;">Obrigatório para campanhas via WhatsApp.</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:10px;padding:12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);cursor:pointer;">
                        <input type="checkbox" name="rule_has_email" value="1" <?= $ruleEmail === 1 ? 'checked' : '' ?> style="width:18px;height:18px;margin-top:1px;flex-shrink:0;" />
                        <div>
                            <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);">Tem e-mail cadastrado</div>
                            <div style="font-size:11px;color:rgba(31,41,55,.50);margin-top:2px;">Obrigatório para campanhas via e-mail.</div>
                        </div>
                    </label>
                </div>
            </div>

            <div style="display:flex;gap:10px;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar segmento</button>
                <a class="lc-btn lc-btn--secondary" href="/marketing/automation/segments">Cancelar</a>
            </div>
        </div>

        <!-- Sidebar -->
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="mse-section">
                <div class="mse-section__title">Audiência estimada</div>
                <div class="mse-audience">
                    <div class="mse-audience__number"><?= (int)$audience_count ?></div>
                    <div class="mse-audience__label">paciente<?= $audience_count !== 1 ? 's' : '' ?> neste segmento</div>
                </div>
            </div>

            <div class="mse-section">
                <div class="mse-section__title">Regras ativas</div>
                <div class="mse-rules">
                    <div class="mse-rule">
                        <div class="mse-rule__check mse-rule__check--on">✓</div>
                        <div>
                            <div class="mse-rule__text">Status: <?= htmlspecialchars($ruleStatus === 'active' ? 'Ativo' : 'Inativo', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                    <div class="mse-rule">
                        <div class="mse-rule__check mse-rule__check--<?= $ruleWa === 1 ? 'on' : 'off' ?>"><?= $ruleWa === 1 ? '✓' : '✕' ?></div>
                        <div>
                            <div class="mse-rule__text">WhatsApp opt-in</div>
                        </div>
                    </div>
                    <div class="mse-rule">
                        <div class="mse-rule__check mse-rule__check--<?= $rulePhone === 1 ? 'on' : 'off' ?>"><?= $rulePhone === 1 ? '✓' : '✕' ?></div>
                        <div>
                            <div class="mse-rule__text">Tem telefone</div>
                        </div>
                    </div>
                    <div class="mse-rule">
                        <div class="mse-rule__check mse-rule__check--<?= $ruleEmail === 1 ? 'on' : 'off' ?>"><?= $ruleEmail === 1 ? '✓' : '✕' ?></div>
                        <div>
                            <div class="mse-rule__text">Tem e-mail</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php else: ?>
<!-- Somente leitura -->
<div class="mse-layout">
    <div class="mse-section">
        <div class="mse-section__title">Dados do segmento</div>
        <div style="display:flex;gap:20px;flex-wrap:wrap;">
            <div><span style="font-size:12px;color:rgba(31,41,55,.50);">Nome</span><div style="font-weight:700;"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></div></div>
            <div><span style="font-size:12px;color:rgba(31,41,55,.50);">Status</span><div style="font-weight:700;"><?= $status === 'active' ? 'Ativo' : 'Desativado' ?></div></div>
        </div>
    </div>
    <div class="mse-section">
        <div class="mse-section__title">Audiência</div>
        <div class="mse-audience">
            <div class="mse-audience__number"><?= (int)$audience_count ?></div>
            <div class="mse-audience__label">paciente<?= $audience_count !== 1 ? 's' : '' ?></div>
        </div>
    </div>
</div>
<div style="margin-top:14px;">
    <a class="lc-btn lc-btn--secondary" href="/marketing/automation/segments">Voltar</a>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
