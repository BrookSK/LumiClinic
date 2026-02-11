<?php
$title = 'Documentos legais (Equipe)';
$rows = $rows ?? [];
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Documentos legais do Sistema (usuários internos)</div>

    <div class="lc-flex lc-gap-sm" style="margin-top:12px;">
        <a class="lc-btn lc-btn--primary" href="/settings/legal-documents/edit">Novo</a>
        <a class="lc-btn lc-btn--secondary" href="/settings">Voltar</a>
    </div>

    <div class="lc-tablewrap" style="margin-top:12px;">
        <table class="lc-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Papel alvo</th>
                    <th>Obrigatório</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6">Nenhum documento cadastrado.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int)($r['id'] ?? 0) ?></td>
                        <td><?= htmlspecialchars((string)($r['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['target_role_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)($r['is_required'] ?? 0) === 1 ? 'Sim' : 'Não' ?></td>
                        <td><?= htmlspecialchars((string)($r['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a class="lc-btn lc-btn--secondary" href="/settings/legal-documents/edit?id=<?= (int)($r['id'] ?? 0) ?>">Editar</a>
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
