<?php
$title = 'Assinaturas';
$rows  = $rows ?? [];
$limit = (int)($limit ?? 200);
$scope = (string)($scope ?? 'all');

$scopeLabels = [
    'patient_portal' => 'Portal do Paciente',
    'system_user'    => 'Equipe Interna',
    'clinic_owner'   => 'Owner',
];

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;">Assinaturas</div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px;">Trilha legal de aceites e assinaturas de documentos</div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/settings/lgpd">Documentos LGPD</a>
        <a class="lc-btn lc-btn--secondary" href="/clinic">Voltar</a>
    </div>
</div>

<!-- Filtros -->
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <form method="get" action="/clinic/legal-signatures" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
            <div class="lc-field">
                <label class="lc-label">Tipo</label>
                <select class="lc-select" name="scope">
                    <option value="all" <?= $scope === 'all' ? 'selected' : '' ?>>Todos</option>
                    <option value="patient_portal" <?= $scope === 'patient_portal' ? 'selected' : '' ?>>Portal do Paciente</option>
                    <option value="system_user" <?= $scope === 'system_user' ? 'selected' : '' ?>>Equipe Interna</option>
                    <option value="clinic_owner" <?= $scope === 'clinic_owner' ? 'selected' : '' ?>>Owner</option>
                </select>
            </div>
            <button class="lc-btn lc-btn--primary" type="submit">Filtrar</button>
        </form>
    </div>
</div>

<!-- Lista -->
<div class="lc-card">
    <div class="lc-card__body" style="padding:0;">
        <?php if (empty($rows)): ?>
            <div class="lc-muted" style="padding:24px; text-align:center;">Nenhuma assinatura registrada.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Documento</th>
                    <th>Tipo</th>
                    <th>Assinante</th>
                    <th>Integridade</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php
                    $who = '';
                    $whoSub = '';
                    if ((int)($r['patient_user_id'] ?? 0) > 0) {
                        $who    = (string)($r['patient_name'] ?? '');
                        $whoSub = (string)($r['patient_user_email'] ?? '');
                    } else {
                        $who    = (string)($r['user_name'] ?? '');
                        $whoSub = (string)($r['user_email'] ?? '');
                    }
                    $scopeRaw = (string)($r['scope'] ?? '');
                    $scopeLabel = $scopeLabels[$scopeRaw] ?? $scopeRaw;
                    $hashOk = isset($r['hash_ok']) ? (bool)$r['hash_ok'] : null;

                    // Formatar data
                    $signedAt = (string)($r['signed_at'] ?? '');
                    $signedFmt = $signedAt;
                    try {
                        $signedFmt = (new \DateTimeImmutable($signedAt))->format('d/m/Y H:i');
                    } catch (\Throwable $e) {}
                    ?>
                    <tr>
                        <td style="white-space:nowrap; font-size:13px;"><?= htmlspecialchars($signedFmt, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars((string)($r['document_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="lc-muted" style="font-size:12px;">v<?= (int)($r['version_number'] ?? 0) ?></div>
                        </td>
                        <td>
                            <span class="lc-badge lc-badge--secondary" style="font-size:11px;"><?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                            <div style="font-weight:600; font-size:13px;"><?= htmlspecialchars($who, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if ($whoSub !== ''): ?>
                                <div class="lc-muted" style="font-size:12px;"><?= htmlspecialchars($whoSub, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($hashOk === true): ?>
                                <span class="lc-badge lc-badge--success" style="font-size:11px;">✓ OK</span>
                            <?php elseif ($hashOk === false): ?>
                                <span class="lc-badge lc-badge--danger" style="font-size:11px;">✗ Falhou</span>
                            <?php else: ?>
                                <span class="lc-muted" style="font-size:11px;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/clinic/legal-signatures/view?id=<?= (int)($r['id'] ?? 0) ?>">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
