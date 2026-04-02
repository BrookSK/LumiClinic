<?php
/** @var list<array<string,mixed>> $rows */
/** @var list<array<string,mixed>> $segments */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Automação - Campanhas';

$rows = $rows ?? [];
$segments = $segments ?? [];

$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

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

ob_start();
?>

<style>
.ma-nav{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px}
.ma-nav a{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:12px;font-weight:700;font-size:13px;text-decoration:none;border:1px solid rgba(17,24,39,.10);color:rgba(31,41,55,.72);background:var(--lc-surface-3);transition:all 160ms ease}
.ma-nav a:hover{border-color:rgba(129,89,1,.22);color:rgba(129,89,1,1);background:rgba(238,184,16,.06)}
.ma-nav a.active{background:rgba(238,184,16,.14);border-color:rgba(129,89,1,.24);color:rgba(31,41,55,.96)}
.ma-hero{padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:18px}
.ma-hero__title{font-weight:850;font-size:18px;color:rgba(31,41,55,.96)}
.ma-hero__desc{margin-top:6px;font-size:13px;color:rgba(31,41,55,.60);line-height:1.5;max-width:700px}
.ma-tip{padding:14px 16px;border-radius:12px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.18);font-size:13px;color:rgba(31,41,55,.80);line-height:1.5;margin-bottom:16px}
.ma-tip strong{color:rgba(129,89,1,1)}
.mac-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:14px}
.mac-card{padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);transition:all 160ms ease;text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:10px}
.mac-card:hover{border-color:rgba(129,89,1,.22);box-shadow:0 8px 24px rgba(17,24,39,.10);transform:translateY(-1px)}
.mac-card__top{display:flex;align-items:center;justify-content:space-between;gap:8px}
.mac-card__name{font-weight:750;font-size:14px;color:rgba(31,41,55,.96)}
.mac-card__badges{display:flex;gap:6px;flex-wrap:wrap}
.mac-badge{display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.3px;text-transform:uppercase}
.mac-card__meta{display:flex;gap:16px;flex-wrap:wrap;font-size:12px;color:rgba(31,41,55,.55)}
.mac-card__meta span{display:inline-flex;align-items:center;gap:4px}
.ma-empty{text-align:center;padding:40px 20px;color:rgba(31,41,55,.50)}
.ma-empty__icon{font-size:32px;margin-bottom:8px}
.ma-empty__text{font-size:14px}
</style>

<!-- Navegação -->
<div class="ma-nav">
    <a href="/marketing/automation/segments">Segmentos</a>
    <a href="/marketing/automation/campaigns" class="active">Campanhas</a>
    <a href="/marketing/automation/logs">Logs de envio</a>
</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Explicação -->
<div class="ma-hero">
    <div class="ma-hero__title">Campanhas de marketing</div>
    <div class="ma-hero__desc">
        Campanhas são disparos de mensagens para um grupo de pacientes. Escolha um canal (WhatsApp ou e-mail), selecione um segmento de pacientes, configure o conteúdo e agende ou dispare manualmente.
    </div>
</div>

<div class="ma-tip">
    <strong>Passo a passo:</strong> 1. Crie um segmento (se ainda não tem) → 2. Crie uma campanha aqui → 3. Configure o conteúdo (template WhatsApp ou e-mail) → 4. Agende ou clique em "Rodar agora" para disparar.
</div>

<!-- Criar nova -->
<?php if ($can('marketing.automation.manage')): ?>
<div id="formNewCampaign" style="display:none;margin-bottom:16px;">
    <div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
        <div style="font-weight:750;font-size:14px;margin-bottom:12px;">Nova campanha</div>
        <form method="post" action="/marketing/automation/campaign/create" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Nome da campanha</label>
                <input class="lc-input" type="text" name="name" required placeholder="Ex: Lembrete de retorno, Promoção de Natal..." />
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:4px;">
                <div class="lc-field">
                    <label class="lc-label">Canal de envio</label>
                    <select class="lc-select" name="channel">
                        <option value="whatsapp">WhatsApp</option>
                        <option value="email">E-mail</option>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Quem recebe</label>
                    <select class="lc-select" name="segment_id">
                        <option value="">Todos os pacientes</option>
                        <?php foreach ($segments as $s): ?>
                            <?php $sid = (int)($s['id'] ?? 0); if ($sid <= 0) continue; ?>
                            <option value="<?= $sid ?>"><?= htmlspecialchars((string)($s['name'] ?? ('Segmento #' . $sid)), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="lc-field" style="margin-top:4px;">
                <label class="lc-label">Quando enviar?</label>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:6px;">
                    <label style="display:flex;align-items:center;gap:6px;padding:10px 14px;border-radius:10px;border:1px solid rgba(17,24,39,.10);cursor:pointer;font-size:13px;font-weight:650;">
                        <input type="radio" name="status" value="draft" checked onchange="document.getElementById('newSchedWrap').style.display='none'" style="width:16px;height:16px;" />
                        Salvar como rascunho
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;padding:10px 14px;border-radius:10px;border:1px solid rgba(17,24,39,.10);cursor:pointer;font-size:13px;font-weight:650;">
                        <input type="radio" name="status" value="scheduled" onchange="document.getElementById('newSchedWrap').style.display='block'" style="width:16px;height:16px;" />
                        Agendar para uma data
                    </label>
                </div>
                <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Rascunho: você configura tudo e dispara depois manualmente. Agendar: define a data e o envio acontece automaticamente.</div>
            </div>

            <div id="newSchedWrap" style="display:none;margin-top:4px;">
                <div class="lc-field">
                    <label class="lc-label">Data e horário do envio</label>
                    <input class="lc-input" type="datetime-local" name="scheduled_for" style="max-width:300px;" />
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:14px;">
                <button class="lc-btn lc-btn--primary" type="submit">Criar campanha</button>
                <button type="button" class="lc-btn lc-btn--secondary" onclick="document.getElementById('formNewCampaign').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div style="margin-bottom:16px;">
    <button type="button" class="lc-btn lc-btn--primary" onclick="document.getElementById('formNewCampaign').style.display=document.getElementById('formNewCampaign').style.display==='none'?'block':'none'">+ Nova campanha</button>
</div>
<?php endif; ?>

<!-- Lista -->
<?php if ($rows === []): ?>
    <div class="ma-empty">
        <div class="ma-empty__icon">📣</div>
        <div class="ma-empty__text">Nenhuma campanha criada ainda. Crie a primeira para começar a enviar mensagens aos seus pacientes.</div>
    </div>
<?php else: ?>
    <div class="mac-cards">
        <?php foreach ($rows as $r): ?>
            <?php
            $cid = (int)($r['id'] ?? 0);
            if ($cid <= 0) continue;
            $nm = trim((string)($r['name'] ?? ''));
            $ch = (string)($r['channel'] ?? 'whatsapp');
            $st = (string)($r['status'] ?? 'draft');
            $sched = trim((string)($r['scheduled_for'] ?? ''));
            $lastRun = trim((string)($r['last_run_at'] ?? ''));
            $created = (string)($r['created_at'] ?? '');

            $stLbl = $statusLabel[$st] ?? $st;
            $stClr = $statusColor[$st] ?? '#6b7280';
            $chLbl = $channelLabel[$ch] ?? $ch;
            ?>
            <a href="/marketing/automation/campaign/edit?id=<?= $cid ?>" class="mac-card">
                <div class="mac-card__top">
                    <span class="mac-card__name"><?= htmlspecialchars($nm !== '' ? $nm : 'Campanha #' . $cid, ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="mac-card__badges">
                        <span class="mac-badge" style="background:<?= $stClr ?>18;color:<?= $stClr ?>;border:1px solid <?= $stClr ?>30"><?= htmlspecialchars($stLbl, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="mac-badge" style="background:rgba(107,114,128,.08);color:rgba(31,41,55,.70);border:1px solid rgba(17,24,39,.10)"><?= htmlspecialchars($chLbl, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
                <div class="mac-card__meta">
                    <?php if ($sched !== ''): ?>
                        <span>📅 Agendada: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($sched)), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <?php if ($lastRun !== ''): ?>
                        <span>▶ Última execução: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($lastRun)), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <?php if ($created !== ''): ?>
                        <span>Criada em <?= htmlspecialchars(date('d/m/Y', strtotime($created)), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
