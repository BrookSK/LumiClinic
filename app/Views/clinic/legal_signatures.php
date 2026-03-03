<?php
$title = 'Assinaturas de termos';
$rows = $rows ?? [];
$limit = (int)($limit ?? 200);
$scope = (string)($scope ?? 'all');
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Assinaturas (trilha legal)</div>

    <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:12px; align-items:center;">
        <a class="lc-btn lc-btn--secondary" href="/clinic/legal-documents">Configurar textos (Portal)</a>
        <a class="lc-btn lc-btn--secondary" href="/settings/legal-documents">Configurar textos (Sistema)</a>
        <a class="lc-btn lc-btn--secondary" href="/clinic">Voltar</a>
    </div>

    <form method="get" action="/clinic/legal-signatures" class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:12px; align-items:flex-end;">
        <div>
            <label class="lc-label">Escopo</label>
            <select class="lc-input" name="scope">
                <option value="all" <?= $scope === 'all' ? 'selected' : '' ?>>Todos</option>
                <option value="patient_portal" <?= $scope === 'patient_portal' ? 'selected' : '' ?>>Portal do paciente</option>
                <option value="system_user" <?= $scope === 'system_user' ? 'selected' : '' ?>>Usuários internos</option>
                <option value="clinic_owner" <?= $scope === 'clinic_owner' ? 'selected' : '' ?>>Owner</option>
            </select>
        </div>
        <div>
            <label class="lc-label">Limite</label>
            <input class="lc-input" type="number" name="limit" value="<?= (int)$limit ?>" min="50" max="1000" />
        </div>
        <div>
            <button class="lc-btn lc-btn--primary" type="submit">Filtrar</button>
        </div>
    </form>

    <div class="lc-tablewrap" style="margin-top:12px;">
        <table class="lc-table">
            <thead>
                <tr>
                    <th>Quando</th>
                    <th>Documento</th>
                    <th>Escopo</th>
                    <th>Versão</th>
                    <th>Assinante</th>
                    <th>IP</th>
                    <th>User-Agent</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="8">Nenhum registro.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <?php
                        $who = '';
                        if ((int)($r['patient_user_id'] ?? 0) > 0) {
                            $who = (string)($r['patient_name'] ?? '') . ' (' . (string)($r['patient_user_email'] ?? '') . ')';
                        } else {
                            $who = (string)($r['user_name'] ?? '') . ' (' . (string)($r['user_email'] ?? '') . ')';
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($r['signed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['document_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['scope'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>#<?= (int)($r['version_number'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($who, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="max-width:320px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            <?= htmlspecialchars((string)($r['user_agent'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <a class="lc-btn lc-btn--secondary" href="/clinic/legal-signatures/view?id=<?= (int)($r['id'] ?? 0) ?>">Ver</a>
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
