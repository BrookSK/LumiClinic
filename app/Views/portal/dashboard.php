<?php
$title = 'Início';
$patient_id = $patient_id ?? null;
$clinic_id = $clinic_id ?? null;
$upcoming_appointments = $upcoming_appointments ?? [];
$notifications = $notifications ?? [];

$statusLabel = ['scheduled'=>'Agendado','confirmed'=>'Confirmado','in_progress'=>'Em andamento','completed'=>'Concluído','cancelled'=>'Cancelado','no_show'=>'Faltou'];

ob_start();
?>

<div style="margin-bottom:20px;">
    <div style="font-weight:850;font-size:22px;color:rgba(31,41,55,.96);">Bem-vindo ao seu portal</div>
    <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:4px;">Aqui você acompanha suas consultas, documentos e notificações.</div>
</div>

<!-- Próximas consultas -->
<div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;">
        <div style="font-weight:750;font-size:15px;color:rgba(31,41,55,.90);">Próximas consultas</div>
        <a href="/portal/agenda" style="font-size:13px;color:rgba(129,89,1,1);font-weight:600;text-decoration:none;">Ver todas →</a>
    </div>

    <?php if (!is_array($upcoming_appointments) || $upcoming_appointments === []): ?>
        <div style="text-align:center;padding:20px;color:rgba(31,41,55,.40);font-size:13px;">Nenhuma consulta agendada no momento.</div>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach (array_slice($upcoming_appointments, 0, 3) as $a): ?>
                <?php
                $st = (string)($a['status'] ?? '');
                $stLbl = $statusLabel[$st] ?? $st;
                $startAt = (string)($a['start_at'] ?? '');
                $startFmt = $startAt;
                try { $startFmt = (new \DateTimeImmutable($startAt))->format('d/m/Y \à\s H:i'); } catch (\Throwable $e) {}
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);flex-wrap:wrap;">
                    <div>
                        <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);"><?= htmlspecialchars((string)($a['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <div style="font-size:12px;color:rgba(31,41,55,.50);"><?= htmlspecialchars((string)($a['professional_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($startFmt, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <span style="display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;background:rgba(238,184,16,.12);color:rgba(129,89,1,1);border:1px solid rgba(238,184,16,.22);"><?= htmlspecialchars($stLbl, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Ações rápidas -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:16px;">
    <a href="/portal/agenda" style="padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);text-decoration:none;color:inherit;text-align:center;">
        <div style="font-size:24px;margin-bottom:6px;">📅</div>
        <div style="font-weight:700;font-size:13px;">Agenda</div>
    </a>
    <a href="/portal/documentos" style="padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);text-decoration:none;color:inherit;text-align:center;">
        <div style="font-size:24px;margin-bottom:6px;">📄</div>
        <div style="font-weight:700;font-size:13px;">Documentos</div>
    </a>
    <a href="/portal/uploads" style="padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);text-decoration:none;color:inherit;text-align:center;">
        <div style="font-size:24px;margin-bottom:6px;">📸</div>
        <div style="font-weight:700;font-size:13px;">Enviar fotos</div>
    </a>
    <a href="/portal/perfil" style="padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);text-decoration:none;color:inherit;text-align:center;">
        <div style="font-size:24px;margin-bottom:6px;">👤</div>
        <div style="font-weight:700;font-size:13px;">Meu perfil</div>
    </a>
</div>

<!-- Notificações recentes -->
<?php if (is_array($notifications) && $notifications !== []): ?>
<div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;">
        <div style="font-weight:750;font-size:15px;color:rgba(31,41,55,.90);">Avisos recentes</div>
        <a href="/portal/notificacoes" style="font-size:13px;color:rgba(129,89,1,1);font-weight:600;text-decoration:none;">Ver todos →</a>
    </div>
    <div style="display:flex;flex-direction:column;gap:6px;">
        <?php foreach (array_slice($notifications, 0, 3) as $n): ?>
            <div style="padding:10px 12px;border-radius:10px;border:1px solid rgba(17,24,39,.05);background:<?= ($n['read_at'] ?? null) === null ? 'rgba(238,184,16,.04)' : 'transparent' ?>;">
                <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars((string)($n['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div style="font-size:12px;color:rgba(31,41,55,.55);margin-top:2px;"><?= htmlspecialchars((string)($n['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'dashboard';
require __DIR__ . '/_shell.php';
