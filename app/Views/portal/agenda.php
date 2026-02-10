<?php
$title = 'Agenda';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$appointments = $appointments ?? [];
$pending_requests = $pending_requests ?? [];
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 10;
$hasNext = isset($has_next) ? (bool)$has_next : false;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/design-system.css" />
</head>
<body class="lc-body">
<div class="lc-app" style="padding: 16px; max-width: 980px; margin: 0 auto;">
    <div class="lc-page__header">
        <div>
            <h1 class="lc-page__title">Agenda</h1>
            <div class="lc-page__subtitle">Portal do Paciente</div>
        </div>
        <div class="lc-flex lc-gap-sm">
            <a class="lc-btn lc-btn--secondary" href="/portal">Dashboard</a>
            <form method="post" action="/portal/logout">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Sair</button>
            </form>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger" style="margin-top:12px;">
            <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="lc-grid" style="margin-top:16px;">
        <div class="lc-card" style="padding: 16px;">
            <div class="lc-card__title">Próximas consultas</div>
            <div class="lc-card__body">
                <?php if (!is_array($appointments) || $appointments === []): ?>
                    <div>Nenhuma consulta agendada.</div>
                <?php else: ?>
                    <div class="lc-grid" style="gap:10px;">
                        <?php foreach ($appointments as $a): ?>
                            <div class="lc-card" style="padding:12px;">
                                <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-md">
                                    <div>
                                        <div><strong><?= htmlspecialchars((string)($a['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                        <div><?= htmlspecialchars((string)($a['professional_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div><?= htmlspecialchars((string)($a['start_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                    <div class="lc-flex lc-flex--wrap" style="gap:8px; align-items:flex-start;">
                                        <div class="lc-badge lc-badge--gray"><?= htmlspecialchars((string)($a['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>

                                        <form method="post" action="/portal/agenda/confirm">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="appointment_id" value="<?= (int)($a['id'] ?? 0) ?>" />
                                            <button class="lc-btn lc-btn--primary" type="submit">Confirmar</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="lc-grid" style="margin-top:10px; gap:10px;">
                                    <form method="post" action="/portal/agenda/reschedule-request" class="lc-form">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                        <input type="hidden" name="appointment_id" value="<?= (int)($a['id'] ?? 0) ?>" />
                                        <label class="lc-label">Solicitar reagendamento (data/hora)</label>
                                        <input class="lc-input" type="datetime-local" name="requested_start_at" />
                                        <label class="lc-label">Observação (opcional)</label>
                                        <input class="lc-input" type="text" name="note" />
                                        <button class="lc-btn lc-btn--secondary" type="submit">Solicitar reagendamento</button>
                                    </form>

                                    <form method="post" action="/portal/agenda/cancel-request" class="lc-form">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                        <input type="hidden" name="appointment_id" value="<?= (int)($a['id'] ?? 0) ?>" />
                                        <label class="lc-label">Solicitar cancelamento (motivo opcional)</label>
                                        <input class="lc-input" type="text" name="note" />
                                        <button class="lc-btn lc-btn--danger" type="submit">Solicitar cancelamento</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-sm" style="margin-top:12px;">
                    <div class="lc-muted">Página <?= (int)$page ?></div>
                    <div class="lc-flex lc-gap-sm">
                        <?php if ($page > 1): ?>
                            <a class="lc-btn lc-btn--secondary" href="/portal/agenda?per_page=<?= (int)$perPage ?>&page=<?= (int)($page - 1) ?>">Anterior</a>
                        <?php endif; ?>
                        <?php if ($hasNext): ?>
                            <a class="lc-btn lc-btn--secondary" href="/portal/agenda?per_page=<?= (int)$perPage ?>&page=<?= (int)($page + 1) ?>">Próxima</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="lc-card" style="padding: 16px;">
            <div class="lc-card__title">Solicitações pendentes</div>
            <div class="lc-card__body">
                <?php if (!is_array($pending_requests) || $pending_requests === []): ?>
                    <div>Nenhuma solicitação pendente.</div>
                <?php else: ?>
                    <div class="lc-grid" style="gap:10px;">
                        <?php foreach ($pending_requests as $r): ?>
                            <div class="lc-card" style="padding:12px;">
                                <div><strong><?= htmlspecialchars((string)($r['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                <div>Agendamento #<?= (int)($r['appointment_id'] ?? 0) ?></div>
                                <div>Data solicitada: <?= htmlspecialchars((string)($r['requested_start_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div>Obs: <?= htmlspecialchars((string)($r['note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
