<?php
$title = 'Timeline de imagens';
$patient = $patient ?? null;
$items = $items ?? [];

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
        return true;
    }

    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) {
        return false;
    }

    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) {
            return false;
        }
        return in_array($permissionCode, $permissions['allow'], true);
    }

    return in_array($permissionCode, $permissions, true);
};

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div>
        <div class="lc-badge lc-badge--primary">Timeline</div>
        <div class="lc-muted" style="margin-top:6px;">Linha do tempo das imagens do paciente, agrupadas por procedimento e sessão.</div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/medical-images?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Voltar</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <div style="font-weight:700;">
            <?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="lc-muted">
            CPF: <?= isset($patient['cpf_last4']) && $patient['cpf_last4'] ? '***.' . htmlspecialchars((string)$patient['cpf_last4'], ENT_QUOTES, 'UTF-8') : '' ?>
        </div>
    </div>
</div>

<?php if (!is_array($items) || $items === []): ?>
    <div class="lc-muted">Nenhuma imagem cadastrada.</div>
<?php else: ?>
    <?php foreach ($items as $g): ?>
        <?php
        $proc = trim((string)($g['procedure_type'] ?? ''));
        $procLabel = $proc !== '' ? $proc : 'Sem procedimento';
        $session = $g['session_number'] ?? null;
        $sessionLabel = $session !== null ? ('Sessão ' . (int)$session) : 'Sessão (não informada)';
        $rows = is_array($g['images'] ?? null) ? $g['images'] : [];
        ?>
        <div class="lc-card" style="margin-bottom:14px;">
            <div class="lc-card__title">
                <?= htmlspecialchars($procLabel, ENT_QUOTES, 'UTF-8') ?>
                <span class="lc-muted" style="font-weight:400;">— <?= htmlspecialchars($sessionLabel, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="lc-card__body">
                <div class="lc-table-wrap">
                    <table class="lc-table">
                        <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Data</th>
                            <th>Ângulo/Posição</th>
                            <th>Arquivo</th>
                            <th style="width:1%; white-space:nowrap;">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $img): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($img['kind'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)(($img['taken_at'] ?? '') !== '' ? $img['taken_at'] : ($img['created_at'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($img['pose'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($img['original_filename'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="lc-flex lc-flex--wrap" style="gap:8px;">
                                    <?php if ($can('files.read')): ?>
                                        <a class="lc-btn lc-btn--secondary" href="/medical-images/file?id=<?= (int)$img['id'] ?>" target="_blank">Abrir</a>
                                        <?php if (isset($img['comparison_key']) && (string)$img['comparison_key'] !== ''): ?>
                                            <a class="lc-btn lc-btn--secondary" href="/medical-images/compare?patient_id=<?= (int)($patient['id'] ?? 0) ?>&key=<?= urlencode((string)$img['comparison_key']) ?>">Comparar</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
