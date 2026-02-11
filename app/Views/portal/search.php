<?php
$title = 'Busca';
$q = isset($q) ? (string)$q : '';
$agenda = $agenda ?? [];
$documentos = $documentos ?? [];
$notificacoes = $notificacoes ?? [];
$uploads = $uploads ?? [];

$hasAny = (is_array($agenda) && $agenda !== []) || (is_array($documentos) && $documentos !== []) || (is_array($notificacoes) && $notificacoes !== []) || (is_array($uploads) && $uploads !== []);

ob_start();
?>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Buscar</div>
        <div class="lc-card__body">
            <form method="get" action="/portal/busca" class="lc-form">
                <label class="lc-label">Termo</label>
                <input class="lc-input" type="search" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: consulta, assinatura, upload, data..." />
                <button class="lc-btn lc-btn--primary" type="submit">Pesquisar</button>
            </form>

            <?php if (trim((string)$q) === ''): ?>
                <div class="lc-muted" style="margin-top:10px;">Digite algo para pesquisar no portal.</div>
            <?php elseif (!$hasAny): ?>
                <div class="lc-alert lc-alert--info" style="margin-top:10px;">Nenhum resultado encontrado.</div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($hasAny): ?>
        <div class="lc-grid" style="margin-top:16px; gap:12px;">
            <?php if (is_array($agenda) && $agenda !== []): ?>
                <div class="lc-card" style="padding:16px;">
                    <div class="lc-card__title">Agenda</div>
                    <div class="lc-card__body">
                        <div class="lc-grid" style="gap:10px;">
                            <?php foreach ($agenda as $it): ?>
                                <div class="lc-card" style="padding:12px;">
                                    <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-md">
                                        <div>
                                            <div><strong><?= htmlspecialchars((string)($it['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                            <div><?= htmlspecialchars((string)($it['subtitle'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <div style="margin-top:6px; opacity:0.8;"><?= htmlspecialchars((string)($it['at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        </div>
                                        <div class="lc-flex" style="gap:8px; align-items:flex-start;">
                                            <div class="lc-badge lc-badge--gray"><?= htmlspecialchars((string)($it['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <a class="lc-btn lc-btn--secondary" href="/portal/agenda">Abrir</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (is_array($documentos) && $documentos !== []): ?>
                <div class="lc-card" style="padding:16px;">
                    <div class="lc-card__title">Documentos</div>
                    <div class="lc-card__body">
                        <div class="lc-grid" style="gap:10px;">
                            <?php foreach ($documentos as $it): ?>
                                <?php $href = isset($it['href']) ? (string)$it['href'] : '/portal/documentos'; ?>
                                <div class="lc-card" style="padding:12px;">
                                    <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-md">
                                        <div>
                                            <div><strong><?= htmlspecialchars((string)($it['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                            <div><?= htmlspecialchars((string)($it['subtitle'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <div style="margin-top:6px; opacity:0.8;"><?= htmlspecialchars((string)($it['at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        </div>
                                        <div class="lc-flex" style="gap:8px; align-items:flex-start;">
                                            <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" target="_blank">Abrir</a>
                                            <a class="lc-btn lc-btn--secondary" href="/portal/documentos">Ver lista</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (is_array($notificacoes) && $notificacoes !== []): ?>
                <div class="lc-card" style="padding:16px;">
                    <div class="lc-card__title">Notificações</div>
                    <div class="lc-card__body">
                        <div class="lc-grid" style="gap:10px;">
                            <?php foreach ($notificacoes as $it): ?>
                                <div class="lc-card" style="padding:12px;">
                                    <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-md">
                                        <div>
                                            <div><strong><?= htmlspecialchars((string)($it['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                            <div><?= htmlspecialchars((string)($it['subtitle'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <div style="margin-top:6px; opacity:0.8;"><?= htmlspecialchars((string)($it['at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        </div>
                                        <div class="lc-flex" style="gap:8px; align-items:flex-start;">
                                            <?php if (($it['read_at'] ?? null) === null): ?>
                                                <div class="lc-badge lc-badge--gray">Não lida</div>
                                            <?php else: ?>
                                                <div class="lc-badge lc-badge--gray">Lida</div>
                                            <?php endif; ?>
                                            <a class="lc-btn lc-btn--secondary" href="/portal/notificacoes">Abrir</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (is_array($uploads) && $uploads !== []): ?>
                <div class="lc-card" style="padding:16px;">
                    <div class="lc-card__title">Uploads</div>
                    <div class="lc-card__body">
                        <div class="lc-grid" style="gap:10px;">
                            <?php foreach ($uploads as $it): ?>
                                <div class="lc-card" style="padding:12px;">
                                    <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-md">
                                        <div>
                                            <div><strong><?= htmlspecialchars((string)($it['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                            <div><?= htmlspecialchars((string)($it['subtitle'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <div style="margin-top:6px; opacity:0.8;"><?= htmlspecialchars((string)($it['at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        </div>
                                        <div class="lc-flex" style="gap:8px; align-items:flex-start;">
                                            <div class="lc-badge lc-badge--gray"><?= htmlspecialchars((string)($it['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <a class="lc-btn lc-btn--secondary" href="/portal/uploads">Abrir</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'busca';
require __DIR__ . '/_shell.php';
