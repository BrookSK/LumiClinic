<?php
$title = 'Documentos';
$csrf = $_SESSION['_csrf'] ?? '';
$acceptances = $acceptances ?? [];
$signatures = $signatures ?? [];
$images = $images ?? [];
ob_start();
?>

    <div class="lc-grid" style="margin-top:16px;">
        <div class="lc-card" style="padding:16px;">
            <div class="lc-card__title">Termos aceitos</div>
            <div class="lc-card__body">
                <?php if (!is_array($acceptances) || $acceptances === []): ?>
                    <div>Nenhum termo aceito.</div>
                <?php else: ?>
                    <div class="lc-table-wrap">
                        <table class="lc-table">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Termo</th>
                                <th>Procedimento</th>
                                <th>Aceito em</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($acceptances as $a): ?>
                                <tr>
                                    <td><?= (int)($a['id'] ?? 0) ?></td>
                                    <td><?= (int)($a['term_id'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars((string)($a['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($a['accepted_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="lc-card" style="padding:16px;">
            <div class="lc-card__title">Assinaturas</div>
            <div class="lc-card__body">
                <?php if (!is_array($signatures) || $signatures === []): ?>
                    <div>Nenhuma assinatura disponível.</div>
                <?php else: ?>
                    <div class="lc-table-wrap">
                        <table class="lc-table">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Aceite</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($signatures as $s): ?>
                                <tr>
                                    <td><?= (int)($s['id'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars((string)($s['term_acceptance_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($s['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <a class="lc-btn lc-btn--secondary" href="/portal/signatures/file?id=<?= (int)($s['id'] ?? 0) ?>" target="_blank">Abrir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="lc-card" style="padding:16px;">
            <div class="lc-card__title">Imagens</div>
            <div class="lc-card__body">
                <?php if (!is_array($images) || $images === []): ?>
                    <div>Nenhuma imagem disponível.</div>
                <?php else: ?>
                    <div class="lc-table-wrap">
                        <table class="lc-table">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Data</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($images as $img): ?>
                                <tr>
                                    <td><?= (int)($img['id'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars((string)($img['kind'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($img['taken_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($img['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <a class="lc-btn lc-btn--secondary" href="/portal/medical-images/file?id=<?= (int)($img['id'] ?? 0) ?>" target="_blank">Abrir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="lc-card" style="padding:16px;">
            <div class="lc-card__title">Prontuário (bloqueado)</div>
            <div class="lc-card__body">
                <div>Sem acesso a anotações internas. Relatórios liberados entram aqui numa próxima etapa.</div>
            </div>
        </div>
    </div>

<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'documentos';
require __DIR__ . '/_shell.php';
