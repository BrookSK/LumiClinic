<?php
$title   = 'Linha do tempo';
$patient = $patient ?? null;
$items   = $items ?? [];
$filters = $filters ?? [];

$patientId = (int)($patient['id'] ?? 0);

// Filtros ativos
$activeTypes = is_array($filters['types'] ?? null) ? $filters['types'] : [];
$from = (string)($filters['from'] ?? '');
$to   = (string)($filters['to'] ?? '');

$typeOptions = [
    'appointment'            => 'Agendamentos',
    'medical_record'         => 'Prontuários',
    'consultation'           => 'Consultas',
    'medical_image'          => 'Imagens',
    'clinical_alert'         => 'Alertas clínicos',
    'allergy'                => 'Alergias',
    'condition'              => 'Condições',
    'consent_acceptance'     => 'Aceites de termo',
    'signature'              => 'Assinaturas',
];

// Ícones por tipo
$typeIcon = [
    'appointment'            => '📅',
    'medical_record'         => '📋',
    'consultation'           => '🩺',
    'medical_image'          => '🖼',
    'clinical_alert'         => '⚠️',
    'allergy'                => '🚫',
    'condition'              => '💊',
    'consent_acceptance'     => '✍️',
    'signature'              => '✍️',
    'consultation_attachment'=> '📎',
];

// Cores por tipo
$typeColor = [
    'appointment'            => '#eeb810',
    'medical_record'         => '#2563eb',
    'consultation'           => '#16a34a',
    'medical_image'          => '#7c3aed',
    'clinical_alert'         => '#b91c1c',
    'allergy'                => '#b91c1c',
    'condition'              => '#d97706',
    'consent_acceptance'     => '#6b7280',
    'signature'              => '#6b7280',
    'consultation_attachment'=> '#6b7280',
];

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

ob_start();
?>

<!-- Cabeçalho -->
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px;">Linha do tempo · <?= count($items) ?> eventos</div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= $patientId ?>">Paciente</a>
        <a class="lc-btn lc-btn--primary" href="/medical-records?patient_id=<?= $patientId ?>">Prontuário</a>
    </div>
</div>

<!-- Filtros -->
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <form method="get" action="/patients/timeline" class="lc-form">
            <input type="hidden" name="patient_id" value="<?= $patientId ?>" />

            <div style="margin-bottom:10px;">
                <div class="lc-label" style="margin-bottom:6px;">Mostrar</div>
                <div class="lc-flex lc-gap-sm lc-flex--wrap">
                    <?php foreach ($typeOptions as $key => $label): ?>
                        <label style="display:inline-flex; align-items:center; gap:5px; cursor:pointer; font-size:13px; background:<?= in_array($key, $activeTypes, true) ? 'rgba(238,184,16,.15)' : 'rgba(0,0,0,.04)' ?>; border:1px solid <?= in_array($key, $activeTypes, true) ? '#eeb810' : 'rgba(0,0,0,.1)' ?>; border-radius:6px; padding:4px 10px;">
                            <input type="checkbox" name="types[]" value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= in_array($key, $activeTypes, true) ? 'checked' : '' ?> style="margin:0;" />
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
                <div class="lc-field">
                    <label class="lc-label">De</label>
                    <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Até</label>
                    <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" />
                </div>
                <button class="lc-btn lc-btn--primary" type="submit">Filtrar</button>
                <?php if (!empty($activeTypes) || $from !== '' || $to !== ''): ?>
                    <a class="lc-btn lc-btn--secondary" href="/patients/timeline?patient_id=<?= $patientId ?>">Limpar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Timeline -->
<?php if (empty($items)): ?>
    <div class="lc-card">
        <div class="lc-card__body" style="text-align:center; padding:40px 20px;">
            <div style="font-size:32px; margin-bottom:10px;">📭</div>
            <div class="lc-muted">Nenhum evento encontrado para os filtros selecionados.</div>
        </div>
    </div>
<?php else: ?>
    <div style="position:relative; padding-left:28px;">
        <!-- Linha vertical -->
        <div style="position:absolute; left:10px; top:0; bottom:0; width:2px; background:rgba(0,0,0,.08);"></div>

        <?php
        $lastDate = '';
        foreach ($items as $it):
            $type = (string)($it['type'] ?? '');
            $occurredAt = (string)($it['occurred_at'] ?? '');
            $link = (string)($it['link'] ?? '');
            $title = (string)($it['title'] ?? '');
            $desc = (string)($it['description'] ?? '');
            $icon = $typeIcon[$type] ?? '•';
            $color = $typeColor[$type] ?? '#6b7280';
            $typeLabel = $typeOptions[$type] ?? $type;

            // Formatar data
            $dateFmt = '';
            $timeFmt = '';
            try {
                $dt = new \DateTimeImmutable($occurredAt);
                $dateFmt = $dt->format('d/m/Y');
                $timeFmt = $dt->format('H:i');
            } catch (\Throwable $e) {
                $dateFmt = $occurredAt;
            }

            // Separador de data
            if ($dateFmt !== $lastDate):
                $lastDate = $dateFmt;
        ?>
        <div style="position:relative; margin-bottom:8px; margin-top:<?= $lastDate === $dateFmt ? '0' : '16px' ?>;">
            <div style="position:absolute; left:-24px; top:50%; transform:translateY(-50%); width:10px; height:10px; border-radius:50%; background:#e5e7eb; border:2px solid #fff; box-shadow:0 0 0 2px #e5e7eb;"></div>
            <div style="font-size:12px; font-weight:700; color:#6b7280; padding:2px 0;"><?= htmlspecialchars($dateFmt, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php endif; ?>

        <div style="position:relative; margin-bottom:8px;">
            <!-- Ponto na linha -->
            <div style="position:absolute; left:-24px; top:14px; width:12px; height:12px; border-radius:50%; background:<?= $color ?>; border:2px solid #fff; box-shadow:0 0 0 2px <?= $color ?>33;"></div>

            <div style="background:var(--lc-surface,#fffdf8); border:1px solid rgba(0,0,0,.08); border-radius:10px; padding:10px 14px; display:flex; justify-content:space-between; align-items:center; gap:12px;">
                <div style="display:flex; align-items:flex-start; gap:10px; flex:1; min-width:0;">
                    <span style="font-size:16px; flex-shrink:0; margin-top:1px;"><?= $icon ?></span>
                    <div style="min-width:0;">
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="font-weight:600; font-size:13px;"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></span>
                            <span style="font-size:11px; color:<?= $color ?>; background:<?= $color ?>18; border-radius:4px; padding:1px 6px;"><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($timeFmt !== '' && $timeFmt !== '00:00'): ?>
                                <span class="lc-muted" style="font-size:12px;"><?= htmlspecialchars($timeFmt, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($desc !== ''): ?>
                            <div class="lc-muted" style="font-size:12px; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:500px;">
                                <?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($link !== ''): ?>
                    <div class="lc-flex lc-gap-sm" style="flex-shrink:0;">
                        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>">Abrir</a>
                        <?php if (!empty($it['extra_link']) && !empty($it['extra_link_label'])): ?>
                            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="<?= htmlspecialchars((string)$it['extra_link'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string)$it['extra_link_label'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
