<?php
$title = 'Solicitações de alteração de perfil';
$csrf = $_SESSION['_csrf'] ?? '';
$rows = $rows ?? [];
$status = (string)($status ?? 'pending');
$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Solicitações</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="lc-alert lc-alert--success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin: 12px 0;">
        <a class="lc-btn lc-btn--secondary" href="/patients/profile-requests?status=pending">Pendentes</a>
        <a class="lc-btn lc-btn--secondary" href="/patients/profile-requests?status=approved">Aprovadas</a>
        <a class="lc-btn lc-btn--secondary" href="/patients/profile-requests?status=rejected">Rejeitadas</a>
    </div>

    <div class="lc-tablewrap">
        <table class="lc-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Paciente</th>
                    <th>Status</th>
                    <th>Solicitado</th>
                    <th style="width:280px;">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="5">Nenhuma solicitação.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <?php
                        $payload = json_decode((string)($r['requested_fields_json'] ?? '{}'), true);
                        if (!is_array($payload)) $payload = [];
                    ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td>
                            <?= htmlspecialchars((string)($r['patient_name'] ?? ('#' . (int)$r['patient_id'])), ENT_QUOTES, 'UTF-8') ?>
                            <div style="opacity:.7; font-size:12px;">
                                <a href="/patients/view?id=<?= (int)$r['patient_id'] ?>">Ver paciente</a>
                            </div>
                        </td>
                        <td><?= htmlspecialchars((string)($r['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="max-width:420px;">
                            <?php foreach ($payload as $k => $v): ?>
                                <?php if (is_array($v)): ?>
                                    <div><strong><?= htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') ?>:</strong></div>
                                    <div style="padding-left:10px; opacity:.9;">
                                        <?php foreach ($v as $kk => $vv): ?>
                                            <div><strong><?= htmlspecialchars((string)$kk, ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars((string)$vv, ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div><strong><?= htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php if ((string)($r['status'] ?? '') === 'pending'): ?>
                                <form method="post" action="/patients/profile-requests/approve" style="display:inline-block; margin-right:6px;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                                    <button class="lc-btn lc-btn--primary" type="submit">Aprovar</button>
                                </form>

                                <form method="post" action="/patients/profile-requests/reject" style="display:inline-block;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                                    <input class="lc-input" type="text" name="notes" placeholder="Motivo (opcional)" style="width:140px; display:inline-block;" />
                                    <button class="lc-btn lc-btn--danger" type="submit">Rejeitar</button>
                                </form>
                            <?php else: ?>
                                <span style="opacity:.7;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
