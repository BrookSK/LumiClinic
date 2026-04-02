<?php
$title   = 'Solicitações de perfil';
$csrf    = $_SESSION['_csrf'] ?? '';
$rows    = $rows ?? [];
$status  = (string)($status ?? 'pending');
$error   = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

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

$statusLabel = ['pending' => 'Pendente', 'approved' => 'Aprovada', 'rejected' => 'Rejeitada'];
$statusBadge = ['pending' => 'lc-badge--primary', 'approved' => 'lc-badge--success', 'rejected' => 'lc-badge--danger'];

$fieldLabel = [
    'name' => 'Nome', 'email' => 'E-mail', 'phone' => 'Telefone',
    'birth_date' => 'Nascimento', 'sex' => 'Sexo', 'cpf' => 'CPF',
    'address' => 'Endereço', 'street' => 'Rua', 'number' => 'Número',
    'complement' => 'Complemento', 'district' => 'Bairro',
    'city' => 'Cidade', 'state' => 'UF', 'zip' => 'CEP',
];

$pendingCount = 0;
foreach ($rows as $r) { if ((string)($r['status'] ?? '') === 'pending') $pendingCount++; }

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;">Solicitações de perfil</div>
        <?php if ($pendingCount > 0): ?>
            <div class="lc-muted" style="font-size:13px; margin-top:2px;"><?= $pendingCount ?> pendente<?= $pendingCount !== 1 ? 's' : '' ?></div>
        <?php endif; ?>
    </div>
    <a class="lc-btn lc-btn--secondary" href="/patients">Pacientes</a>
</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Filtros -->
<div class="lc-flex lc-gap-sm" style="margin-bottom:14px;">
    <?php foreach (['pending' => 'Pendentes', 'approved' => 'Aprovadas', 'rejected' => 'Rejeitadas'] as $k => $lbl): ?>
        <a class="lc-btn <?= $status === $k ? 'lc-btn--primary' : 'lc-btn--secondary' ?> lc-btn--sm" href="/patients/profile-requests?status=<?= $k ?>">
            <?= $lbl ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Lista -->
<?php if (empty($rows)): ?>
    <div class="lc-card">
        <div class="lc-card__body" style="text-align:center; padding:40px 20px;">
            <div class="lc-muted">Nenhuma solicitação <?= htmlspecialchars($statusLabel[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?>.</div>
        </div>
    </div>
<?php else: ?>
    <div style="display:flex; flex-direction:column; gap:10px;">
        <?php foreach ($rows as $r): ?>
            <?php
            $st = (string)($r['status'] ?? '');
            $isPending = $st === 'pending';
            $patientName = (string)($r['patient_name'] ?? ('#' . (int)$r['patient_id']));
            $createdAt = (string)($r['created_at'] ?? '');
            $dateFmt = '';
            try { $dateFmt = (new \DateTimeImmutable($createdAt))->format('d/m/Y H:i'); } catch (\Throwable $e) { $dateFmt = $createdAt; }

            $payload = json_decode((string)($r['requested_fields_json'] ?? '{}'), true);
            if (!is_array($payload)) $payload = [];

            // Flatten nested address_parts
            $flat = [];
            foreach ($payload as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $kk => $vv) {
                        $flat[(string)$kk] = (string)$vv;
                    }
                } else {
                    $flat[(string)$k] = (string)$v;
                }
            }
            ?>
            <div class="lc-card" style="margin:0; border-left:4px solid <?= $isPending ? '#eeb810' : ($st === 'approved' ? '#16a34a' : '#b91c1c') ?>;">
                <div class="lc-card__body">
                    <div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="gap:10px; margin-bottom:10px;">
                        <div>
                            <div class="lc-flex lc-gap-sm" style="align-items:center;">
                                <span style="font-weight:700;"><?= htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="lc-badge <?= $statusBadge[$st] ?? 'lc-badge--secondary' ?>" style="font-size:11px;">
                                    <?= htmlspecialchars($statusLabel[$st] ?? $st, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <div class="lc-muted" style="font-size:12px; margin-top:2px;"><?= htmlspecialchars($dateFmt, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/patients/view?id=<?= (int)$r['patient_id'] ?>">Ver paciente</a>
                    </div>

                    <!-- Campos solicitados -->
                    <div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));">
                        <?php foreach ($flat as $k => $v): ?>
                            <?php if (trim($v) === '') continue; ?>
                            <div style="background:rgba(0,0,0,.03); border-radius:6px; padding:8px 10px;">
                                <div style="font-size:11px; color:#6b7280; font-weight:600;"><?= htmlspecialchars($fieldLabel[$k] ?? $k, ENT_QUOTES, 'UTF-8') ?></div>
                                <div style="font-size:13px; font-weight:600; margin-top:2px;"><?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Ações -->
                    <?php if ($isPending && $can('patients.update')): ?>
                        <div class="lc-flex lc-gap-sm" style="margin-top:12px; padding-top:10px; border-top:1px solid rgba(0,0,0,.06);">
                            <form method="post" action="/patients/profile-requests/approve">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                                <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">✓ Aprovar</button>
                            </form>
                            <form method="post" action="/patients/profile-requests/reject" onsubmit="return confirm('Rejeitar esta solicitação?');">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                                <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">✕ Rejeitar</button>
                            </form>
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
