<?php
$title = 'Admin - Chamado de suporte';
$csrf = $_SESSION['_csrf'] ?? '';

$ticket = isset($ticket) && is_array($ticket) ? $ticket : [];
$context = isset($context) && is_array($context) ? $context : [];
$transcript = isset($transcript) && is_array($transcript) ? $transcript : [];
$events = isset($events) && is_array($events) ? $events : [];
$notifications = isset($notifications) && is_array($notifications) ? $notifications : [];
$success = isset($success) ? (string)$success : '';
$error = isset($error) ? (string)$error : '';

$e = static fn (mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$fmt = static function (?string $dt): string {
    $ts = $dt !== null && $dt !== '' ? strtotime($dt) : false;
    return $ts !== false ? date('d/m/Y H:i', $ts) : '-';
};

$statusLabels = [
    'novo' => 'Novo',
    'em_analise' => 'Em análise',
    'aguardando_usuario' => 'Aguardando usuário',
    'resolvido' => 'Resolvido',
    'fechado' => 'Fechado',
];
$sevLabels = ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta', 'critica' => 'Crítica'];
$sevColors = ['baixa' => '#6b7280', 'media' => '#2563eb', 'alta' => '#d97706', 'critica' => '#b91c1c'];
$catLabels = ['bug' => 'Erro', 'duvida' => 'Dúvida', 'acesso' => 'Acesso', 'financeiro' => 'Financeiro', 'integracao' => 'Integração', 'outro' => 'Outro'];
$eventLabels = ['created' => 'Criado', 'status_change' => 'Mudança de status', 'note' => 'Anotação', 'notification' => 'Notificação', 'resolution' => 'Resolução'];
$roleLabels = ['user' => 'Usuário', 'assistant' => 'Assistente', 'system' => 'Sistema'];

$mask = static function (string $dest): string {
    if (str_contains($dest, '@')) {
        [$local, $domain] = explode('@', $dest, 2);
        return mb_substr($local, 0, 2) . '***@' . $domain;
    }
    $digits = preg_replace('/\D/', '', $dest) ?? '';
    return strlen($digits) > 4 ? str_repeat('*', strlen($digits) - 4) . substr($digits, -4) : '****';
};

$sev = (string)($ticket['severity'] ?? 'media');
$sevColor = $sevColors[$sev] ?? '#6b7280';
$status = (string)($ticket['status'] ?? 'novo');
$id = (int)($ticket['id'] ?? 0);

$ctxClinic = is_array($context['clinic'] ?? null) ? $context['clinic'] : [];
$ctxUser = is_array($context['user'] ?? null) ? $context['user'] : [];
$roleCodes = is_array($context['role_codes'] ?? null) ? $context['role_codes'] : [];
$permissions = is_array($context['permissions'] ?? null) ? $context['permissions'] : [];
$recentErrors = is_array($context['recent_errors'] ?? null) ? $context['recent_errors'] : [];
$kbChunks = is_array($context['kb_chunk_ids'] ?? null) ? $context['kb_chunk_ids'] : [];

$card = 'padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);margin-bottom:14px;';
$h = 'font-weight:750;font-size:13px;margin-bottom:8px;';
$pre = 'white-space:pre-wrap;line-height:1.5;font-size:13px;margin:0;';

ob_start();
?>

<a href="/sys/support/tickets" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para chamados
</a>

<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;"><?= $e($success) ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;"><?= $e($error) ?></div>
<?php endif; ?>

<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
    <span style="font-family:monospace;font-weight:800;font-size:15px;"><?= $e($ticket['public_code'] ?? '') ?></span>
    <span style="font-size:12px;font-weight:700;padding:3px 10px;border-radius:999px;background:<?= $e($sevColor) ?>18;color:<?= $e($sevColor) ?>;border:1px solid <?= $e($sevColor) ?>30;"><?= $e($sevLabels[$sev] ?? $sev) ?></span>
    <span class="lc-badge"><?= $e($statusLabels[$status] ?? $status) ?></span>
    <span class="lc-badge"><?= $e($catLabels[(string)($ticket['category'] ?? '')] ?? ($ticket['category'] ?? '')) ?></span>
    <span class="lc-muted" style="font-size:12px;">Aberto em <?= $e($fmt((string)($ticket['created_at'] ?? ''))) ?></span>
</div>
<h2 style="margin:0 0 16px;font-size:20px;"><?= $e($ticket['title'] ?? '') ?></h2>

<div style="display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:16px;align-items:start;">
    <div>
        <div style="<?= $card ?>">
            <div style="<?= $h ?>">Resumo</div>
            <div style="<?= $pre ?>"><?= $e(($ticket['summary'] ?? '') !== '' ? $ticket['summary'] : '-') ?></div>
        </div>

        <div style="<?= $card ?>display:grid;gap:12px;">
            <div>
                <div style="<?= $h ?>">O que o usuário já tentou</div>
                <div style="<?= $pre ?>"><?= $e(($ticket['steps_tried'] ?? '') !== '' ? $ticket['steps_tried'] : '-') ?></div>
            </div>
            <div>
                <div style="<?= $h ?>">Resultado esperado</div>
                <div style="<?= $pre ?>"><?= $e(($ticket['expected'] ?? '') !== '' ? $ticket['expected'] : '-') ?></div>
            </div>
            <div>
                <div style="<?= $h ?>">O que aconteceu</div>
                <div style="<?= $pre ?>"><?= $e(($ticket['actual'] ?? '') !== '' ? $ticket['actual'] : '-') ?></div>
            </div>
        </div>

        <div style="<?= $card ?>">
            <div style="<?= $h ?>">Conversa com o assistente (<?= count($transcript) ?>)</div>
            <?php if ($transcript === []): ?>
                <div class="lc-muted" style="font-size:13px;">Sem mensagens registradas.</div>
            <?php endif; ?>
            <?php foreach ($transcript as $m): ?>
                <?php $role = (string)($m['role'] ?? ''); ?>
                <div style="padding:8px 10px;margin-bottom:6px;border-radius:10px;background:<?= $role === 'user' ? 'rgba(37,99,235,.06)' : 'rgba(17,24,39,.04)' ?>;">
                    <div class="lc-muted" style="font-size:11px;margin-bottom:2px;"><?= $e($roleLabels[$role] ?? $role) ?> <?= $e($fmt((string)($m['at'] ?? ''))) ?></div>
                    <div style="<?= $pre ?>"><?= $e($m['content'] ?? '') ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="<?= $card ?>">
            <div style="<?= $h ?>">Linha do tempo</div>
            <?php foreach ($events as $ev): ?>
                <div style="padding:8px 0;border-bottom:1px solid rgba(17,24,39,.06);">
                    <div class="lc-muted" style="font-size:11px;">
                        <?= $e($eventLabels[(string)$ev['type']] ?? $ev['type']) ?>
                        &middot; <?= $e($fmt((string)$ev['created_at'])) ?>
                        <?php if (!empty($ev['actor_name'])): ?>&middot; <?= $e($ev['actor_name']) ?><?php endif; ?>
                    </div>
                    <?php if (($ev['body'] ?? '') !== ''): ?>
                        <div style="<?= $pre ?>"><?= $e($ev['body']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <form method="post" action="/sys/support/tickets/note" class="lc-form" style="margin-top:12px;">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
                <input type="hidden" name="id" value="<?= $id ?>" />
                <div class="lc-field">
                    <label class="lc-label">Nova anotação interna</label>
                    <textarea class="lc-textarea" name="note" rows="3" maxlength="4000" required></textarea>
                </div>
                <div style="margin-top:8px;"><button class="lc-btn lc-btn--secondary" type="submit">Adicionar anotação</button></div>
            </form>
        </div>
    </div>

    <div>
        <div style="<?= $card ?>">
            <div style="<?= $h ?>">Status</div>
            <form method="post" action="/sys/support/tickets/status" class="lc-form lc-flex lc-gap-sm">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
                <input type="hidden" name="id" value="<?= $id ?>" />
                <select class="lc-select" name="status">
                    <?php foreach ($statusLabels as $k => $label): ?>
                        <option value="<?= $e($k) ?>"<?= $status === $k ? ' selected' : '' ?>><?= $e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            </form>
        </div>

        <div style="<?= $card ?>">
            <div style="<?= $h ?>">Resolução</div>
            <?php if (($ticket['resolution_text'] ?? '') !== ''): ?>
                <div class="lc-muted" style="font-size:11px;margin-bottom:4px;">Resolvido em <?= $e($fmt((string)($ticket['resolved_at'] ?? ''))) ?></div>
                <div style="<?= $pre ?>margin-bottom:10px;"><?= $e($ticket['resolution_text']) ?></div>
            <?php endif; ?>
            <form method="post" action="/sys/support/tickets/resolve" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
                <input type="hidden" name="id" value="<?= $id ?>" />
                <div class="lc-field">
                    <label class="lc-label"><?= ($ticket['resolution_text'] ?? '') !== '' ? 'Atualizar resolução' : 'Como foi resolvido' ?></label>
                    <textarea class="lc-textarea" name="resolution" rows="4" maxlength="4000" required placeholder="Explique a solução em linguagem simples, sem dados pessoais."></textarea>
                </div>
                <label style="display:flex;gap:6px;align-items:center;font-size:13px;margin-top:8px;">
                    <input type="checkbox" name="suggest_kb" value="1" checked />
                    Sugerir entrada para a base de conhecimento
                </label>
                <div class="lc-muted" style="font-size:11px;">Cria apenas um rascunho anonimizado. Ele só vale para o assistente depois que você aprovar.</div>
                <div style="margin-top:8px;"><button class="lc-btn lc-btn--primary" type="submit">Marcar como resolvido</button></div>
            </form>
            <?php if (($ticket['resolution_text'] ?? '') !== ''): ?>
                <form method="post" action="/sys/support/kb/generate" style="margin-top:8px;">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
                    <input type="hidden" name="ticket_id" value="<?= $id ?>" />
                    <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Gerar sugestão de FAQ</button>
                </form>
            <?php endif; ?>
        </div>

        <div style="<?= $card ?>">
            <div style="<?= $h ?>">Solicitante</div>
            <div style="font-size:13px;line-height:1.6;">
                <div><b>Clínica:</b> <?= $e($ctxClinic['name'] ?? ($ticket['clinic_name'] ?? '-')) ?> (#<?= (int)($ticket['clinic_id'] ?? 0) ?>)</div>
                <div><b>Usuário:</b> <?= $e($ctxUser['name'] ?? ($ticket['user_name'] ?? '-')) ?> (#<?= (int)($ticket['user_id'] ?? 0) ?>)</div>
                <div><b>E-mail:</b> <?= $e(($ctxUser['email'] ?? '') !== '' ? $ctxUser['email'] : ($ticket['user_email'] ?? '-')) ?></div>
                <div><b>Telefone:</b> <?= $e(($ctxUser['phone'] ?? '') !== '' ? $ctxUser['phone'] : '-') ?></div>
                <div><b>Perfis:</b> <?= $e($roleCodes !== [] ? implode(', ', $roleCodes) : '-') ?></div>
                <?php if (!empty($context['impersonated_session'])): ?>
                    <div style="font-weight:700;color:#b45309;">Aberto por super admin em modo Logar como<?= (int)($context['impersonator_user_id'] ?? 0) > 0 ? ' (usuário #' . (int)$context['impersonator_user_id'] . ')' : '' ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div style="<?= $card ?>">
            <div style="<?= $h ?>">Contexto técnico</div>
            <div style="font-size:12px;line-height:1.6;word-break:break-word;">
                <div><b>Página:</b> <span style="font-family:monospace;"><?= $e(($ticket['page_path'] ?? '') !== '' ? $ticket['page_path'] : '-') ?></span></div>
                <div><b>Origem (referrer):</b> <span style="font-family:monospace;"><?= $e(($context['referrer_path'] ?? '') !== '' ? $context['referrer_path'] : '-') ?></span></div>
                <div><b>Navegador:</b> <?= $e(($context['user_agent'] ?? '') !== '' ? $context['user_agent'] : '-') ?></div>
                <div><b>Hora no cliente:</b> <?= $e(($context['client_time'] ?? '') !== '' ? $context['client_time'] : '-') ?></div>
                <div><b>Hora no servidor:</b> <?= $e($context['server_time'] ?? '-') ?></div>
                <div><b>Ambiente:</b> <?= $e($context['app_env'] ?? '-') ?></div>
                <div><b>Trechos da base consultados:</b> <?= $e($kbChunks !== [] ? implode(', ', $kbChunks) : '-') ?></div>
            </div>
            <details style="margin-top:8px;">
                <summary style="cursor:pointer;font-size:12px;font-weight:700;">Permissões (<?= count($permissions) ?>)</summary>
                <div style="font-family:monospace;font-size:11px;margin-top:6px;word-break:break-word;"><?= $e($permissions !== [] ? implode(', ', $permissions) : '-') ?></div>
            </details>
        </div>

        <div style="<?= $card ?>">
            <div style="<?= $h ?>">Erros recentes do usuário (60 min)</div>
            <?php if ($recentErrors === []): ?>
                <div class="lc-muted" style="font-size:12px;">Nenhum erro registrado.</div>
            <?php endif; ?>
            <?php foreach ($recentErrors as $er): ?>
                <div style="font-size:12px;padding:6px 0;border-bottom:1px solid rgba(17,24,39,.06);word-break:break-word;">
                    <b><?= (int)($er['status'] ?? 0) ?></b>
                    <span style="font-family:monospace;"><?= $e(($er['method'] ?? '') . ' ' . ($er['path'] ?? '')) ?></span>
                    <div><?= $e($er['message'] ?? '') ?></div>
                    <div class="lc-muted"><?= $e($er['at'] ?? '') ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="<?= $card ?>">
            <div style="<?= $h ?>">Notificações à equipe</div>
            <?php if ($notifications === []): ?>
                <div class="lc-muted" style="font-size:12px;">Nenhuma notificação registrada.</div>
            <?php endif; ?>
            <?php foreach ($notifications as $n): ?>
                <div style="font-size:12px;padding:6px 0;border-bottom:1px solid rgba(17,24,39,.06);word-break:break-word;">
                    <b><?= $e($n['channel']) ?></b> &middot; <?= $e($mask((string)$n['destination'])) ?>
                    &middot; <?= $e($n['status']) ?> (<?= (int)$n['attempts'] ?> tentativa(s))
                    <?php if (($n['error'] ?? '') !== ''): ?><div class="lc-muted"><?= $e($n['error']) ?></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
                <form method="post" action="/sys/support/tickets/resend-notification" style="margin:0;">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
                    <input type="hidden" name="id" value="<?= $id ?>" />
                    <input type="hidden" name="mode" value="failed" />
                    <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Reenviar notificação (só falhas)</button>
                </form>
                <form method="post" action="/sys/support/tickets/resend-notification" style="margin:0;" onsubmit="return confirm('Reenviar para todos os números ativos?');">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
                    <input type="hidden" name="id" value="<?= $id ?>" />
                    <input type="hidden" name="mode" value="all" />
                    <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Reenviar para todos</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
