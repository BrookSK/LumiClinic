<?php
$title = 'Início';
$patient_id = $patient_id ?? null;
$clinic_id = $clinic_id ?? null;
$upcoming_appointments = $upcoming_appointments ?? [];
$packages = $packages ?? [];
$subscriptions = $subscriptions ?? [];
$finance = $finance ?? ['total' => 0.0, 'paid' => 0.0, 'open' => 0.0];
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
                <?php endif; ?>
            </div>
        </div>

        <div class="lc-card" style="padding: 16px;">
            <div class="lc-card__title">Financeiro</div>
            <div class="lc-card__body">
                <div class="lc-grid lc-grid--3 lc-gap-grid">
                    <div>
                        <div class="lc-label">Total</div>
                        <div><?= htmlspecialchars(number_format((float)($finance['total'] ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div>
                        <div class="lc-label">Pago</div>
                        <div><?= htmlspecialchars(number_format((float)($finance['paid'] ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div>
                        <div class="lc-label">Em aberto</div>
                        <div><?= htmlspecialchars(number_format((float)($finance['open'] ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lc-card" style="padding: 16px;">
            <div class="lc-card__title">Pacotes</div>
            <div class="lc-card__body">
                <?php if (!is_array($packages) || $packages === []): ?>
                    <div>Nenhum pacote ativo.</div>
                <?php else: ?>
                    <div class="lc-grid" style="gap:10px;">
                        <?php foreach ($packages as $pp): ?>
                            <?php
                            $totalSessions = isset($pp['total_sessions']) ? (int)$pp['total_sessions'] : 0;
                            $usedSessions = isset($pp['used_sessions']) ? (int)$pp['used_sessions'] : 0;
                            $remaining = max(0, $totalSessions - $usedSessions);
                            ?>
                            <div class="lc-card" style="padding: 12px;">
                                <div><strong><?= htmlspecialchars((string)($pp['package_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                <div>Sessões restantes: <?= (int)$remaining ?> / <?= (int)$totalSessions ?></div>
                                <div>Validade: <?= htmlspecialchars((string)($pp['valid_until'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="lc-card" style="padding: 16px;">
            <div class="lc-card__title">Assinaturas</div>
            <div class="lc-card__body">
                <?php if (!is_array($subscriptions) || $subscriptions === []): ?>
                    <div>Nenhuma assinatura ativa.</div>
                <?php else: ?>
                    <div class="lc-grid" style="gap:10px;">
                        <?php foreach ($subscriptions as $ps): ?>
                            <div class="lc-card" style="padding: 12px;">
                                <div><strong><?= htmlspecialchars((string)($ps['plan_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                <div>Início: <?= htmlspecialchars((string)($ps['started_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div>Fim: <?= htmlspecialchars((string)($ps['ends_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="lc-card" style="padding: 16px;">
            <div class="lc-card__title">Notificações</div>
            <div class="lc-card__body">
                <?php if (!is_array($notifications) || $notifications === []): ?>
                    <div>Sem notificações.</div>
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
                                        <?= (($n['read_at'] ?? null) === null) ? '<span class="lc-badge lc-badge--primary">Nova</span>' : '<span class="lc-badge lc-badge--gray">Lida</span>' ?>
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
