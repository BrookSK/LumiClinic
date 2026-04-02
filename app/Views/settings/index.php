<?php
$title = 'Configurações';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$settings = $settings ?? null;
$anamnesisTemplates = $anamnesis_templates ?? [];
$terminology = $terminology ?? null;

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

$ro = $can('settings.update') ? '' : 'disabled';

$weekStart = isset($settings['week_start_weekday']) ? (int)$settings['week_start_weekday'] : 1;
$weekEnd = isset($settings['week_end_weekday']) ? (int)$settings['week_end_weekday'] : 0;
$weekdayNames = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$anamTpl = isset($settings['anamnesis_default_template_id']) ? (int)$settings['anamnesis_default_template_id'] : 0;

ob_start();
?>

<style>
.cfg-head{font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:18px}
.cfg-section{padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px}
.cfg-section__title{font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:4px}
.cfg-section__desc{font-size:12px;color:rgba(31,41,55,.45);margin-bottom:14px;line-height:1.5}
.cfg-row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.cfg-row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
@media(max-width:640px){.cfg-row2,.cfg-row3{grid-template-columns:1fr}}
</style>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="cfg-head">Configurações gerais</div>

<!-- Terminologia + Agenda -->
<form method="post" class="lc-form" action="/settings">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="timezone" value="<?= htmlspecialchars((string)($settings['timezone'] ?? 'America/Sao_Paulo'), ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="language" value="<?= htmlspecialchars((string)($settings['language'] ?? 'pt-BR'), ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="week_end_weekday" value="<?= $weekEnd ?>" />

    <!-- Terminologia -->
    <div class="cfg-section">
        <div class="cfg-section__title">Terminologia</div>
        <div class="cfg-section__desc">Personalize os termos usados no sistema. Por exemplo, se sua clínica chama "paciente" de "cliente", altere aqui e todo o sistema se adapta.</div>

        <div class="cfg-row3">
            <div class="lc-field">
                <label class="lc-label">Paciente / Cliente</label>
                <input class="lc-input" type="text" name="patient_label" value="<?= htmlspecialchars((string)($terminology['patient_label'] ?? 'Paciente'), ENT_QUOTES, 'UTF-8') ?>" required <?= $ro ?> />
            </div>
            <div class="lc-field">
                <label class="lc-label">Consulta / Sessão</label>
                <input class="lc-input" type="text" name="appointment_label" value="<?= htmlspecialchars((string)($terminology['appointment_label'] ?? 'Consulta'), ENT_QUOTES, 'UTF-8') ?>" required <?= $ro ?> />
            </div>
            <div class="lc-field">
                <label class="lc-label">Profissional / Especialista</label>
                <input class="lc-input" type="text" name="professional_label" value="<?= htmlspecialchars((string)($terminology['professional_label'] ?? 'Profissional'), ENT_QUOTES, 'UTF-8') ?>" required <?= $ro ?> />
            </div>
        </div>
    </div>

    <!-- Agenda e Anamnese -->
    <div class="cfg-section">
        <div class="cfg-section__title">Agenda e anamnese</div>
        <div class="cfg-section__desc">Defina o dia que a semana começa na agenda e qual template de anamnese é enviado automaticamente quando uma consulta é confirmada.</div>

        <div class="cfg-row2">
            <div class="lc-field">
                <label class="lc-label">Início da semana</label>
                <select class="lc-select" name="week_start_weekday" <?= $ro ?>>
                    <?php for ($i = 0; $i < 7; $i++): ?>
                        <option value="<?= $i ?>" <?= $weekStart === $i ? 'selected' : '' ?>><?= htmlspecialchars($weekdayNames[$i], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="lc-field">
                <label class="lc-label">Anamnese padrão</label>
                <select class="lc-select" name="anamnesis_default_template_id" <?= $ro ?>>
                    <option value="0">Nenhum</option>
                    <?php if (is_array($anamnesisTemplates)): ?>
                        <?php foreach ($anamnesisTemplates as $t): ?>
                            <option value="<?= (int)($t['id'] ?? 0) ?>" <?= ((int)($t['id'] ?? 0) === $anamTpl) ? 'selected' : '' ?>><?= htmlspecialchars((string)($t['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Enviado automaticamente ao confirmar consulta.</div>
            </div>
        </div>
    </div>

    <?php if ($can('settings.update')): ?>
        <div style="margin-bottom:18px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar configurações</button>
        </div>
    <?php endif; ?>
</form>

<!-- Atalhos para outras configurações -->
<div class="cfg-section">
    <div class="cfg-section__title">Integrações e configurações avançadas</div>
    <div class="cfg-section__desc">Acesse as configurações de integrações externas e listas operacionais.</div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;">
        <a href="/settings/whatsapp" style="padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:rgba(0,0,0,.01);text-decoration:none;color:inherit;display:flex;align-items:center;gap:10px;transition:all 160ms ease;">
            <span style="font-size:20px;">💬</span>
            <div>
                <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);">WhatsApp</div>
                <div style="font-size:11px;color:rgba(31,41,55,.45);">Conexão e configuração</div>
            </div>
        </a>
        <?php if ($can('settings.update')): ?>
        <a href="/whatsapp-templates" style="padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:rgba(0,0,0,.01);text-decoration:none;color:inherit;display:flex;align-items:center;gap:10px;transition:all 160ms ease;">
            <span style="font-size:20px;">📝</span>
            <div>
                <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);">Templates WhatsApp</div>
                <div style="font-size:11px;color:rgba(31,41,55,.45);">Modelos de mensagem</div>
            </div>
        </a>
        <?php endif; ?>
        <?php $aiGlobalKey = $ai_global_key ?? false; ?>
        <?php if (!$aiGlobalKey): ?>
        <a href="/settings/ai" style="padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:rgba(0,0,0,.01);text-decoration:none;color:inherit;display:flex;align-items:center;gap:10px;transition:all 160ms ease;">
            <span style="font-size:20px;">🤖</span>
            <div>
                <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);">Inteligência Artificial</div>
                <div style="font-size:11px;color:rgba(31,41,55,.45);">Chave OpenAI</div>
            </div>
        </a>
        <?php endif; ?>
        <a href="/settings/operational" style="padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:rgba(0,0,0,.01);text-decoration:none;color:inherit;display:flex;align-items:center;gap:10px;transition:all 160ms ease;">
            <span style="font-size:20px;">⚙️</span>
            <div>
                <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);">Operacional</div>
                <div style="font-size:11px;color:rgba(31,41,55,.45);">Funil, motivos, origens</div>
            </div>
        </a>
        <a href="/settings/google-calendar" style="padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:rgba(0,0,0,.01);text-decoration:none;color:inherit;display:flex;align-items:center;gap:10px;transition:all 160ms ease;">
            <span style="font-size:20px;">📅</span>
            <div>
                <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);">Google Calendar</div>
                <div style="font-size:11px;color:rgba(31,41,55,.45);">Sincronização de agenda</div>
            </div>
        </a>
        <?php if ($can('settings.update')): ?>
        <a href="/whatsapp-logs" style="padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:rgba(0,0,0,.01);text-decoration:none;color:inherit;display:flex;align-items:center;gap:10px;transition:all 160ms ease;">
            <span style="font-size:20px;">📋</span>
            <div>
                <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);">Logs WhatsApp</div>
                <div style="font-size:11px;color:rgba(31,41,55,.45);">Histórico de envios</div>
            </div>
        </a>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
