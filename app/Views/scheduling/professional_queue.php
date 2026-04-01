<?php
$title = 'Fila de chegada';
$csrf = $_SESSION['_csrf'] ?? '';
$items = is_array($items ?? null) ? (array)$items : [];
$viewingAll = $viewing_all ?? false;
$profName = $prof_name ?? '';

$can = function (string $p): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $perms = $_SESSION['permissions'] ?? [];
    if (!is_array($perms)) return false;
    if (isset($perms['allow'], $perms['deny'])) {
        if (in_array($p, $perms['deny'], true)) return false;
        return in_array($p, $perms['allow'], true);
    }
    return in_array($p, $perms, true);
};

// Calcular tempo de espera
$waitTime = function (string $checkedInAt): string {
    if ($checkedInAt === '') return '';
    try {
        $dt = new \DateTimeImmutable($checkedInAt);
        $diff = (new \DateTimeImmutable('now'))->getTimestamp() - $dt->getTimestamp();
        $mins = (int)floor($diff / 60);
        if ($mins < 1) return 'agora';
        if ($mins < 60) return $mins . 'min';
        return floor($mins / 60) . 'h' . str_pad((string)($mins % 60), 2, '0', STR_PAD_LEFT);
    } catch (\Throwable $e) {
        return '';
    }
};

ob_start();
?>

<div class="lc-pagehead" style="margin-bottom:16px;">
    <div>
        <div class="lc-pagehead__title">Fila de chegada</div>
        <div class="lc-pagehead__meta">
            <?php if ($viewingAll): ?>
                <span class="lc-badge">Todos os profissionais</span>
            <?php else: ?>
                <span class="lc-badge lc-badge--primary"><?= htmlspecialchars($profName, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
            <?php if (!empty($items)): ?>
                <span class="lc-badge lc-badge--success"><?= count($items) ?> aguardando</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="lc-pagehead__actions">
        <a class="lc-btn lc-btn--secondary" href="/schedule/queue" title="Atualizar">↻ Atualizar</a>
        <a class="lc-btn lc-btn--secondary" href="/schedule">Ver agenda</a>
    </div>
</div>

<?php if (empty($items)): ?>
    <div class="lc-card">
        <div class="lc-card__body" style="text-align:center; padding:48px 20px;">
            <div style="font-size:40px; margin-bottom:12px;">✓</div>
            <div style="font-weight:700; font-size:16px; margin-bottom:6px;">Nenhum paciente aguardando</div>
            <div class="lc-muted">Quando um paciente fizer check-in na recepção, ele aparecerá aqui.</div>
        </div>
    </div>
<?php else: ?>
    <div style="display:flex; flex-direction:column; gap:12px;">
        <?php foreach ($items as $it): ?>
            <?php
            $apptId = (int)($it['id'] ?? 0);
            $st = (string)($it['status'] ?? '');
            $checkedInAt = (string)($it['checked_in_at'] ?? '');
            $startAt = (string)($it['start_at'] ?? '');
            $patientName = trim((string)($it['patient_name'] ?? ''));
            $serviceName = trim((string)($it['service_name'] ?? ''));
            $professionalName = trim((string)($it['professional_name'] ?? ''));
            $apptDate = $startAt !== '' ? substr($startAt, 0, 10) : date('Y-m-d');
            $apptTime = $startAt !== '' ? substr($startAt, 11, 5) : '';
            $wait = $waitTime($checkedInAt);
            $isInProgress = $st === 'in_progress';
            ?>
            <div class="lc-card" style="border-left:4px solid <?= $isInProgress ? '#16a34a' : '#eeb810' ?>; margin:0;">
                <div class="lc-card__body">
                    <div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="gap:12px;">

                        <!-- Info do paciente -->
                        <div class="lc-flex lc-gap-md" style="align-items:center; flex:1; min-width:0;">
                            <div style="width:48px; height:48px; border-radius:50%; background:<?= $isInProgress ? '#dcfce7' : '#fef9c3' ?>; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:18px; color:<?= $isInProgress ? '#16a34a' : '#92400e' ?>; flex-shrink:0;">
                                <?= htmlspecialchars(mb_strtoupper(mb_substr($patientName, 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div style="min-width:0;">
                                <div style="font-weight:700; font-size:16px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($patientName !== '' ? $patientName : '—', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="lc-muted" style="font-size:13px; margin-top:2px;">
                                    <?= htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($viewingAll && $professionalName !== ''): ?>
                                        <span style="margin:0 4px;">·</span>
                                        <?= htmlspecialchars($professionalName, ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Horário e espera -->
                        <div style="text-align:center; flex-shrink:0;">
                            <div style="font-weight:700; font-size:18px; color:#eeb810;">
                                <?= htmlspecialchars($apptTime, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <?php if ($wait !== ''): ?>
                                <div class="lc-muted" style="font-size:12px;">
                                    Esperando <?= htmlspecialchars($wait, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Ações -->
                        <div class="lc-flex lc-gap-sm" style="flex-shrink:0;">
                            <?php if ($isInProgress): ?>
                                <span class="lc-badge lc-badge--success" style="padding:8px 14px; font-size:13px;">Em atendimento</span>
                            <?php elseif ($can('scheduling.finalize')): ?>
                                <form method="post" action="/schedule/start">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= $apptId ?>" />
                                    <input type="hidden" name="view" value="day" />
                                    <input type="hidden" name="date" value="<?= htmlspecialchars($apptDate, ENT_QUOTES, 'UTF-8') ?>" />
                                    <button class="lc-btn lc-btn--primary" type="submit" style="font-size:14px; padding:10px 20px;">
                                        ▶ Iniciar atendimento
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="lc-muted" style="margin-top:16px; font-size:12px; text-align:center;">
        Clique em "↻ Atualizar" para ver novos pacientes que chegaram.
    </div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
