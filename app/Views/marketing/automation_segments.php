<?php
/** @var list<array<string,mixed>> $rows */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Automação - Segmentos';

$rows = $rows ?? [];
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
.ma-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px}
.ma-seg{padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);transition:all 160ms ease;text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:8px}
.ma-seg:hover{border-color:rgba(129,89,1,.22);box-shadow:0 8px 24px rgba(17,24,39,.10);transform:translateY(-1px)}
.ma-seg__top{display:flex;align-items:center;justify-content:space-between;gap:8px}
.ma-seg__name{font-weight:750;font-size:14px;color:rgba(31,41,55,.96)}
.ma-seg__badge{display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.3px;text-transform:uppercase}
.ma-seg__badge--active{background:rgba(22,163,74,.12);color:#16a34a;border:1px solid rgba(22,163,74,.22)}
.ma-seg__badge--disabled{background:rgba(107,114,128,.10);color:#6b7280;border:1px solid rgba(107,114,128,.18)}
.ma-seg__date{font-size:12px;color:rgba(31,41,55,.50)}
.ma-empty{text-align:center;padding:40px 20px;color:rgba(31,41,55,.50)}
.ma-empty__icon{font-size:32px;margin-bottom:8px}
.ma-empty__text{font-size:14px}
</style>

<!-- Navegação -->
<div class="ma-nav">
    <a href="/marketing/automation/segments" class="active">Segmentos</a>
    <a href="/marketing/automation/campaigns">Campanhas</a>
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
    <div class="ma-hero__title">Segmentos de pacientes</div>
    <div class="ma-hero__desc">
        Segmentos são grupos de pacientes filtrados por regras. Você cria um segmento definindo critérios (ex: pacientes ativos, com WhatsApp, com telefone) e depois usa esse segmento em uma campanha para enviar mensagens apenas para quem se encaixa nessas regras.
    </div>
</div>

<div class="ma-tip">
    <strong>Como funciona:</strong> Crie um segmento → Defina as regras (quem recebe) → Use o segmento em uma campanha para disparar mensagens via WhatsApp ou e-mail.
</div>

<!-- Criar novo -->
<?php if ($can('marketing.automation.manage')): ?>
<div id="formNewSegment" style="display:none;margin-bottom:16px;">
    <div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
        <div style="font-weight:750;font-size:14px;margin-bottom:12px;">Novo segmento</div>
        <form method="post" action="/marketing/automation/segment/create" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="rule_status" value="active" />

            <div class="lc-field">
                <label class="lc-label">Nome do segmento</label>
                <input class="lc-input" type="text" name="name" required placeholder="Ex: Pacientes ativos com WhatsApp" />
            </div>

            <div style="display:grid;grid-template-columns:180px 1fr;gap:12px;margin-top:4px;">
                <div class="lc-field">
                    <label class="lc-label">Status</label>
                    <select class="lc-select" name="status">
                        <option value="active">Ativo</option>
                        <option value="disabled">Desativado</option>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Regras de filtro</label>
                    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:8px;">
                        <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                            <input type="checkbox" name="rule_whatsapp_opt_in" value="1" checked style="width:16px;height:16px;" /> Aceitou WhatsApp
                        </label>
                        <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                            <input type="checkbox" name="rule_has_phone" value="1" checked style="width:16px;height:16px;" /> Tem telefone
                        </label>
                        <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                            <input type="checkbox" name="rule_has_email" value="1" style="width:16px;height:16px;" /> Tem e-mail
                        </label>
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:14px;">
                <button class="lc-btn lc-btn--primary" type="submit">Criar segmento</button>
                <button type="button" class="lc-btn lc-btn--secondary" onclick="document.getElementById('formNewSegment').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div style="margin-bottom:16px;">
    <button type="button" class="lc-btn lc-btn--primary" onclick="document.getElementById('formNewSegment').style.display=document.getElementById('formNewSegment').style.display==='none'?'block':'none'">+ Novo segmento</button>
</div>
<?php endif; ?>

<!-- Lista -->
<?php if ($rows === []): ?>
    <div class="ma-empty">
        <div class="ma-empty__icon">📋</div>
        <div class="ma-empty__text">Nenhum segmento criado ainda. Crie o primeiro para começar a segmentar seus pacientes.</div>
    </div>
<?php else: ?>
    <div class="ma-cards">
        <?php foreach ($rows as $r): ?>
            <?php
            $id = (int)($r['id'] ?? 0);
            if ($id <= 0) continue;
            $nm = trim((string)($r['name'] ?? ''));
            $st = (string)($r['status'] ?? 'active');
            $created = (string)($r['created_at'] ?? '');
            $createdFmt = $created !== '' ? date('d/m/Y', strtotime($created)) : '—';
            ?>
            <a href="/marketing/automation/segment/edit?id=<?= $id ?>" class="ma-seg">
                <div class="ma-seg__top">
                    <span class="ma-seg__name"><?= htmlspecialchars($nm !== '' ? $nm : 'Segmento #' . $id, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="ma-seg__badge ma-seg__badge--<?= $st === 'active' ? 'active' : 'disabled' ?>"><?= $st === 'active' ? 'Ativo' : 'Desativado' ?></span>
                </div>
                <div class="ma-seg__date">Criado em <?= htmlspecialchars($createdFmt, ENT_QUOTES, 'UTF-8') ?></div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
