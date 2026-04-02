<?php
$title = 'Agenda';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$appointments = $appointments ?? [];
$pending_requests = $pending_requests ?? [];
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 10;
$hasNext = isset($has_next) ? (bool)$has_next : false;

$statusLabel = ['scheduled'=>'Agendado','confirmed'=>'Confirmado','in_progress'=>'Em andamento','completed'=>'Concluído','cancelled'=>'Cancelado','no_show'=>'Faltou'];
$statusColor = ['scheduled'=>'#6b7280','confirmed'=>'#16a34a','in_progress'=>'#eeb810','completed'=>'#815901','cancelled'=>'#b91c1c','no_show'=>'#b91c1c'];

ob_start();
?>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:18px;">Minha agenda</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Consultas -->
<?php if (!is_array($appointments) || $appointments === []): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
        <div style="font-size:32px;margin-bottom:8px;">📅</div>
        <div style="font-size:14px;">Nenhuma consulta agendada no momento.</div>
    </div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:12px;">
    <?php foreach ($appointments as $a): ?>
        <?php
        $st = (string)($a['status'] ?? '');
        $stLbl = $statusLabel[$st] ?? $st;
        $stClr = $statusColor[$st] ?? '#6b7280';
        $startAt = (string)($a['start_at'] ?? '');
        $startFmt = $startAt;
        try { $startFmt = (new \DateTimeImmutable($startAt))->format('d/m/Y \à\s H:i'); } catch (\Throwable $e) {}
        $canConfirm = in_array($st, ['scheduled'], true);
        $canAction = in_array($st, ['scheduled', 'confirmed'], true);
        $aid = (int)($a['id'] ?? 0);
        ?>
        <div style="padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <div>
                    <div style="font-weight:750;font-size:15px;color:rgba(31,41,55,.96);"><?= htmlspecialchars((string)($a['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    <div style="font-size:13px;color:rgba(31,41,55,.55);margin-top:2px;"><?= htmlspecialchars((string)($a['professional_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    <div style="font-size:13px;color:rgba(31,41,55,.70);margin-top:4px;font-weight:600;">📅 <?= htmlspecialchars($startFmt, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $stClr ?>18;color:<?= $stClr ?>;border:1px solid <?= $stClr ?>30"><?= htmlspecialchars($stLbl, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($canConfirm): ?>
                        <form method="post" action="/portal/agenda/confirm" style="margin:0;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="appointment_id" value="<?= $aid ?>" />
                            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Confirmar</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($canAction): ?>
            <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">
                <details style="flex:1;min-width:200px;">
                    <summary style="font-size:12px;color:rgba(31,41,55,.50);cursor:pointer;list-style:none;">Solicitar reagendamento</summary>
                    <form method="post" action="/portal/agenda/reschedule-request" style="margin-top:8px;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="hidden" name="appointment_id" value="<?= $aid ?>" />
                        <div class="lc-field"><label class="lc-label">Nova data/hora</label><input class="lc-input" type="datetime-local" name="requested_start_at" /></div>
                        <div class="lc-field"><label class="lc-label">Observação</label><input class="lc-input" type="text" name="note" placeholder="Opcional..." /></div>
                        <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="margin-top:8px;">Enviar solicitação</button>
                    </form>
                </details>
                <details style="flex:1;min-width:200px;">
                    <summary style="font-size:12px;color:rgba(185,28,28,.60);cursor:pointer;list-style:none;">Solicitar cancelamento</summary>
                    <form method="post" action="/portal/agenda/cancel-request" style="margin-top:8px;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="hidden" name="appointment_id" value="<?= $aid ?>" />
                        <div class="lc-field"><label class="lc-label">Motivo</label><input class="lc-input" type="text" name="note" placeholder="Opcional..." /></div>
                        <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit" style="margin-top:8px;">Solicitar cancelamento</button>
                    </form>
                </details>
            </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;flex-wrap:wrap;gap:10px;">
    <span style="font-size:12px;color:rgba(31,41,55,.40);">Página <?= (int)$page ?></span>
    <div style="display:flex;gap:8px;">
        <?php if ($page > 1): ?><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/portal/agenda?per_page=<?= (int)$perPage ?>&page=<?= (int)($page - 1) ?>">← Anterior</a><?php endif; ?>
        <?php if ($hasNext): ?><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/portal/agenda?per_page=<?= (int)$perPage ?>&page=<?= (int)($page + 1) ?>">Próxima →</a><?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Solicitações pendentes -->
<?php if (is_array($pending_requests) && $pending_requests !== []): ?>
<div style="padding:18px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.06);margin-top:16px;">
    <div style="font-weight:750;font-size:14px;color:rgba(129,89,1,1);margin-bottom:10px;">Solicitações pendentes</div>
    <div style="display:flex;flex-direction:column;gap:6px;">
        <?php foreach ($pending_requests as $r): ?>
            <?php
            $typeLabel = ['reschedule'=>'Reagendamento','cancel'=>'Cancelamento'];
            $tLbl = $typeLabel[(string)($r['type'] ?? '')] ?? (string)($r['type'] ?? '');
            ?>
            <div style="padding:10px 12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(255,255,255,.60);">
                <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($tLbl, ENT_QUOTES, 'UTF-8') ?></div>
                <?php if (!empty($r['requested_start_at'])): ?>
                    <div style="font-size:12px;color:rgba(31,41,55,.50);">Data solicitada: <?= htmlspecialchars((string)$r['requested_start_at'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if (!empty($r['note'])): ?>
                    <div style="font-size:12px;color:rgba(31,41,55,.50);">Obs: <?= htmlspecialchars((string)$r['note'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'agenda';
require __DIR__ . '/_shell.php';
