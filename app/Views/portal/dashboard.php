<?php
$title = 'Início';
$patient_id = $patient_id ?? null;
$clinic_id = $clinic_id ?? null;
$upcoming_appointments = $upcoming_appointments ?? [];
$notifications = $notifications ?? [];
ob_start();
?>
    <div class="lc-grid" style="margin-top: 16px;">
        <div class="lc-card" style="padding: 16px;">
            <div class="lc-card__title">Próximas consultas</div>
            <div class="lc-card__body">
                <?php if (!is_array($upcoming_appointments) || $upcoming_appointments === []): ?>
                    <div>Nenhuma consulta agendada.</div>
                <?php else: ?>
                    <div class="lc-grid" style="gap:10px;">
                        <?php foreach ($upcoming_appointments as $a): ?>
                            <div class="lc-card" style="padding: 12px;">
                                <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-md">
                                    <div>
                                        <div><strong><?= htmlspecialchars((string)($a['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                        <div><?= htmlspecialchars((string)($a['professional_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                    <div style="text-align:right;">
                                        <div><?= htmlspecialchars((string)($a['start_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="lc-badge lc-badge--gray"><?= htmlspecialchars((string)($a['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="lc-mt-sm">
                        <a class="lc-link" href="/portal/agenda">Ver agenda</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="lc-card" style="padding: 16px;">
            <div class="lc-card__title">Ações rápidas</div>
            <div class="lc-card__body">
                <div class="lc-flex lc-flex--wrap lc-gap-sm">
                    <a class="lc-btn lc-btn--secondary" href="/portal/agenda">Abrir agenda</a>
                    <a class="lc-btn lc-btn--secondary" href="/portal/busca">Buscar</a>
                    <a class="lc-btn lc-btn--secondary" href="/portal/notificacoes">Avisos</a>
                </div>
            </div>
        </div>

        <div class="lc-card" style="padding: 16px;">
            <div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="gap:10px;">
                <div class="lc-card__title" style="margin:0;">Avisos</div>
                <a class="lc-link" href="/portal/notificacoes">Ver todos</a>
            </div>
            <div class="lc-card__body">
                <?php if (!is_array($notifications) || $notifications === []): ?>
                    <div>Sem avisos.</div>
                <?php else: ?>
                    <div class="lc-grid" style="gap:10px;">
                        <?php foreach ($notifications as $n): ?>
                            <div class="lc-card" style="padding:12px;">
                                <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-md">
                                    <div>
                                        <div><strong><?= htmlspecialchars((string)($n['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                        <div><?= nl2br(htmlspecialchars((string)($n['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
                                        <div class="lc-muted" style="margin-top:6px;">
                                            <?= htmlspecialchars((string)($n['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                    <div>
                                        <?= (($n['read_at'] ?? null) === null) ? '<span class="lc-badge lc-badge--primary">Novo</span>' : '<span class="lc-badge lc-badge--gray">Lido</span>' ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'dashboard';
require __DIR__ . '/_shell.php';
