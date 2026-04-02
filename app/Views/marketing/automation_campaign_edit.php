<?php
/** @var array<string,mixed> $row */
/** @var list<array<string,mixed>> $segments */
/** @var list<array<string,mixed>> $templates */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Automação - Editar Campanha';

$row = $row ?? [];
$segments = $segments ?? [];
$templates = $templates ?? [];

$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

$id = (int)($row['id'] ?? 0);
$name = (string)($row['name'] ?? '');
$channel = (string)($row['channel'] ?? 'whatsapp');
$status = (string)($row['status'] ?? 'draft');
$segmentId = (int)($row['segment_id'] ?? 0);
$scheduledFor = (string)($row['scheduled_for'] ?? '');
$scheduledForLocal = '';
if (trim($scheduledFor) !== '') {
    $scheduledForLocal = str_replace(' ', 'T', substr($scheduledFor, 0, 16));
}
$triggerEvent = (string)($row['trigger_event'] ?? '');
$triggerDelay = (string)($row['trigger_delay_minutes'] ?? '');
$waTpl = (string)($row['whatsapp_template_code'] ?? '');
$emailSubject = (string)($row['email_subject'] ?? '');
$emailBody = (string)($row['email_body'] ?? '');
$clickUrl = (string)($row['click_url'] ?? '');
$lastRun = trim((string)($row['last_run_at'] ?? ''));

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

$statusLabel = ['draft'=>'Rascunho','scheduled'=>'Agendada','running'=>'Rodando','paused'=>'Pausada','completed'=>'Concluída','cancelled'=>'Cancelada'];
$statusColor = ['draft'=>'#6b7280','scheduled'=>'#eeb810','running'=>'#16a34a','paused'=>'#b5841e','completed'=>'#815901','cancelled'=>'#b91c1c'];
$channelLabel = ['whatsapp'=>'WhatsApp','email'=>'E-mail'];
$triggerLabel = ['appointment.completed'=>'Após consulta concluída','appointment.no_show'=>'Após falta (no-show)','appointment.cancelled'=>'Após cancelamento','appointment.confirmed'=>'Após confirmação'];

$stLbl = $statusLabel[$status] ?? $status;
$stClr = $statusColor[$status] ?? '#6b7280';
$chLbl = $channelLabel[$channel] ?? $channel;

ob_start();
?>

<style>
.mce-back{display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px}
.mce-back:hover{color:rgba(129,89,1,1)}
.mce-head{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.mce-head__name{font-weight:850;font-size:20px;color:rgba(31,41,55,.96)}
.mce-badge{display:inline-flex;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.3px;text-transform:uppercase}
.mce-card{padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px}
.mce-card__step{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.mce-card__num{width:28px;height:28px;border-radius:999px;background:rgba(238,184,16,.16);color:rgba(129,89,1,1);font-weight:800;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.mce-card__label{font-weight:750;font-size:14px;color:rgba(31,41,55,.90)}
.mce-card__hint{font-size:12px;color:rgba(31,41,55,.45);margin-left:auto}
.mce-row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.mce-row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.mce-adv{margin-top:4px}
.mce-adv summary{list-style:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:650;color:rgba(31,41,55,.55);padding:6px 0}
.mce-adv summary::-webkit-details-marker{display:none}
.mce-adv summary:hover{color:rgba(129,89,1,1)}
.mce-adv summary svg{transition:transform 160ms ease}
.mce-adv[open] summary svg{transform:rotate(90deg)}
.mce-adv__body{margin-top:12px;padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01)}
.mce-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px}
.mce-run-bar{padding:16px;border-radius:14px;border:1px solid rgba(22,163,74,.18);background:rgba(22,163,74,.04);display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:16px}
.mce-run-bar__text{font-size:13px;color:rgba(31,41,55,.70)}
.mce-run-bar__text strong{color:rgba(22,163,74,.90)}
.mce-logs-link{display:flex;align-items:center;justify-content:center;gap:6px;padding:10px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface-3);font-size:13px;font-weight:650;color:rgba(31,41,55,.70);text-decoration:none;transition:all 160ms ease}
.mce-logs-link:hover{border-color:rgba(129,89,1,.22);color:rgba(129,89,1,1)}
@media(max-width:640px){.mce-row2,.mce-row3{grid-template-columns:1fr}}
</style>

<!-- Voltar -->
<a href="/marketing/automation/campaigns" class="mce-back">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para campanhas
</a>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Título -->
<div class="mce-head">
    <span class="mce-head__name"><?= htmlspecialchars($name !== '' ? $name : 'Campanha #' . $id, ENT_QUOTES, 'UTF-8') ?></span>
    <span class="mce-badge" style="background:<?= $stClr ?>18;color:<?= $stClr ?>;border:1px solid <?= $stClr ?>30"><?= htmlspecialchars($stLbl, ENT_QUOTES, 'UTF-8') ?></span>
    <span class="mce-badge" style="background:rgba(107,114,128,.08);color:rgba(31,41,55,.65);border:1px solid rgba(17,24,39,.10)"><?= htmlspecialchars($chLbl, ENT_QUOTES, 'UTF-8') ?></span>
    <?php if ($lastRun !== ''): ?>
        <span style="font-size:12px;color:rgba(31,41,55,.45);margin-left:6px;">Última execução: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($lastRun)), ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
</div>

<?php if ($can('marketing.automation.manage')): ?>

<!-- Barra de disparo -->
<div class="mce-run-bar">
    <div class="mce-run-bar__text">
        <strong>Pronto para disparar?</strong> Clique para enviar a campanha para todos os pacientes do segmento.
    </div>
    <form method="post" action="/marketing/automation/campaign/run" onsubmit="return confirm('Tem certeza? Isso vai enviar mensagens para todos os pacientes do segmento.');" style="margin:0;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="id" value="<?= $id ?>" />
        <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">▶ Rodar agora</button>
    </form>
</div>

<form method="post" action="/marketing/automation/campaign/update" class="lc-form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="id" value="<?= $id ?>" />

    <!-- Passo 1: Dados básicos -->
    <div class="mce-card">
        <div class="mce-card__step">
            <span class="mce-card__num">1</span>
            <span class="mce-card__label">Dados básicos</span>
            <span class="mce-card__hint">Nome, canal e quem recebe</span>
        </div>
        <div class="lc-field">
            <label class="lc-label">Nome da campanha</label>
            <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required />
        </div>
        <div class="mce-row3" style="margin-top:4px;">
            <div class="lc-field">
                <label class="lc-label">Canal de envio</label>
                <select class="lc-select" name="channel" id="channelSelect" onchange="toggleCh()">
                    <option value="whatsapp" <?= $channel === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                    <option value="email" <?= $channel === 'email' ? 'selected' : '' ?>>E-mail</option>
                </select>
            </div>
            <div class="lc-field">
                <label class="lc-label">Quem recebe</label>
                <select class="lc-select" name="segment_id">
                    <option value="">Todos os pacientes</option>
                    <?php foreach ($segments as $s): ?>
                        <?php $sid = (int)($s['id'] ?? 0); if ($sid <= 0) continue; ?>
                        <option value="<?= $sid ?>" <?= $segmentId === $sid ? 'selected' : '' ?>><?= htmlspecialchars((string)($s['name'] ?? ('Segmento #' . $sid)), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lc-field">
                <label class="lc-label">Status</label>
                <select class="lc-select" name="status" id="statusSelect" onchange="toggleSched()">
                    <?php foreach ($statusLabel as $k=>$lbl): ?>
                        <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $status === $k ? 'selected' : '' ?>><?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">
                    Rascunho = ainda não envia. Agendada = envia na data definida. Rodando = em execução.
                </div>
            </div>
        </div>

        <!-- Agendamento (aparece quando status = scheduled) -->
        <div id="schedWrap" style="display:<?= $status === 'scheduled' ? 'block' : 'none' ?>;margin-top:4px;padding:14px;border-radius:12px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.10);">
            <div style="font-weight:700;font-size:13px;color:rgba(129,89,1,1);margin-bottom:8px;">📅 Agendamento</div>
            <div class="lc-field">
                <label class="lc-label">Data e horário do envio</label>
                <input class="lc-input" type="datetime-local" name="scheduled_for" value="<?= htmlspecialchars($scheduledForLocal, ENT_QUOTES, 'UTF-8') ?>" style="max-width:300px;" />
                <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">A campanha será disparada automaticamente nesta data.</div>
            </div>
        </div>
    </div>

    <!-- Passo 2: Conteúdo -->
    <div class="mce-card">
        <div class="mce-card__step">
            <span class="mce-card__num">2</span>
            <span class="mce-card__label">Conteúdo da mensagem</span>
        </div>

        <!-- WhatsApp -->
        <div id="contentWa" style="display:<?= $channel === 'whatsapp' ? 'block' : 'none' ?>;">
            <div class="lc-field">
                <label class="lc-label">Template de WhatsApp</label>
                <select class="lc-select" name="whatsapp_template_code">
                    <option value="">Selecione um template</option>
                    <?php foreach ($templates as $t): ?>
                        <?php $code = (string)($t['code'] ?? ''); if (trim($code) === '') continue; ?>
                        <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= $waTpl === $code ? 'selected' : '' ?>><?= htmlspecialchars((string)($t['name'] ?? $code), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="font-size:11px;color:rgba(31,41,55,.45);margin-top:6px;line-height:1.5;">
                O template define o texto da mensagem. Variáveis disponíveis: <code>{patient_name}</code> (nome do paciente) e <code>{click_url}</code> (link rastreável).
                <br>Gerencie seus templates em <a href="/whatsapp-templates" style="color:rgba(129,89,1,1);font-weight:600;">Configurações → WhatsApp (templates)</a>.
            </div>
        </div>

        <!-- E-mail -->
        <div id="contentEm" style="display:<?= $channel === 'email' ? 'block' : 'none' ?>;">
            <div class="lc-field">
                <label class="lc-label">Assunto do e-mail</label>
                <input class="lc-input" type="text" name="email_subject" value="<?= htmlspecialchars($emailSubject, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: Novidades da clínica" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Corpo do e-mail</label>
                <textarea class="lc-input" name="email_body" rows="5" placeholder="Escreva o conteúdo..."><?= htmlspecialchars($emailBody, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div style="font-size:11px;color:rgba(31,41,55,.45);margin-top:6px;">
                Variáveis: <code>{patient_name}</code> e <code>{click_url}</code>
            </div>
        </div>
    </div>

    <!-- Passo 3: Opções avançadas (colapsado) -->
    <div class="mce-card">
        <details class="mce-adv" <?= ($triggerEvent !== '' || $clickUrl !== '') ? 'open' : '' ?>>
            <summary>
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                Opções avançadas
                <span style="font-weight:400;color:rgba(31,41,55,.40);margin-left:4px;">(disparo automático por evento, link rastreável)</span>
            </summary>
            <div class="mce-adv__body">
                <!-- Trigger -->
                <div>
                    <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.70);margin-bottom:8px;">Disparo automático por evento</div>
                    <div style="font-size:12px;color:rgba(31,41,55,.45);margin-bottom:10px;line-height:1.5;">
                        Se quiser que a campanha dispare sozinha quando algo acontecer (ex: paciente concluiu consulta), selecione o evento abaixo.
                    </div>
                    <div class="mce-row2">
                        <div class="lc-field">
                            <label class="lc-label">Evento</label>
                            <select class="lc-select" name="trigger_event">
                                <option value="" <?= $triggerEvent === '' ? 'selected' : '' ?>>Nenhum (manual)</option>
                                <?php foreach ($triggerLabel as $k=>$lbl): ?>
                                    <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $triggerEvent === $k ? 'selected' : '' ?>><?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Atraso (minutos)</label>
                            <input class="lc-input" type="number" name="trigger_delay_minutes" value="<?= htmlspecialchars($triggerDelay, ENT_QUOTES, 'UTF-8') ?>" min="0" placeholder="0" />
                            <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Ex: 60 = envia 1h depois do evento.</div>
                        </div>
                    </div>
                </div>

                <!-- Link rastreável -->
                <div style="margin-top:14px;padding-top:14px;border-top:1px solid rgba(17,24,39,.06);">
                    <div class="lc-field">
                        <label class="lc-label">Link rastreável (opcional)</label>
                        <input class="lc-input" type="url" name="click_url" value="<?= htmlspecialchars($clickUrl, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://..." style="max-width:500px;" />
                        <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Se preenchido, o sistema gera um link único por paciente. Nos logs você vê quem clicou.</div>
                    </div>
                </div>
            </div>
        </details>
    </div>

    <!-- Salvar -->
    <div class="mce-actions">
        <button class="lc-btn lc-btn--primary" type="submit">Salvar campanha</button>
        <a class="lc-btn lc-btn--secondary" href="/marketing/automation/campaigns">Cancelar</a>
    </div>
</form>

<!-- Link para logs -->
<a href="/marketing/automation/logs?campaign_id=<?= $id ?>" class="mce-logs-link">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
    Ver logs de envio desta campanha
</a>

<?php else: ?>
<!-- Somente leitura -->
<div class="mce-card">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px;">
        <div><div style="font-size:12px;color:rgba(31,41,55,.45);">Nome</div><div style="font-weight:700;margin-top:2px;"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></div></div>
        <div><div style="font-size:12px;color:rgba(31,41,55,.45);">Canal</div><div style="font-weight:700;margin-top:2px;"><?= htmlspecialchars($chLbl, ENT_QUOTES, 'UTF-8') ?></div></div>
        <div><div style="font-size:12px;color:rgba(31,41,55,.45);">Status</div><div style="font-weight:700;margin-top:2px;color:<?= $stClr ?>"><?= htmlspecialchars($stLbl, ENT_QUOTES, 'UTF-8') ?></div></div>
        <?php if ($scheduledFor !== ''): ?>
        <div><div style="font-size:12px;color:rgba(31,41,55,.45);">Agendada</div><div style="font-weight:700;margin-top:2px;"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($scheduledFor)), ENT_QUOTES, 'UTF-8') ?></div></div>
        <?php endif; ?>
    </div>
</div>
<a class="lc-btn lc-btn--secondary" href="/marketing/automation/campaigns">Voltar</a>
<?php endif; ?>

<script>
function toggleCh(){
    var s=document.getElementById('channelSelect');
    if(!s)return;
    var wa=document.getElementById('contentWa');
    var em=document.getElementById('contentEm');
    if(wa)wa.style.display=s.value==='whatsapp'?'block':'none';
    if(em)em.style.display=s.value==='email'?'block':'none';
}
function toggleSched(){
    var s=document.getElementById('statusSelect');
    var w=document.getElementById('schedWrap');
    if(!s||!w)return;
    w.style.display=s.value==='scheduled'?'block':'none';
}
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
