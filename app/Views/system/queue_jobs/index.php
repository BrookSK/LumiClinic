<?php
$title = 'Admin - Fila de Tarefas';
$items = $items ?? [];
$status = $status ?? null;
$csrf = $_SESSION['_csrf'] ?? '';

$allowed = [''=>'Todos','pending'=>'Pendente','processing'=>'Processando','done'=>'Concluído','dead'=>'Falhou'];
$statusColor = ['pending'=>'#eeb810','processing'=>'#6b7280','done'=>'#16a34a','dead'=>'#b91c1c'];

$selected = $status !== null ? (string)$status : '';
if (!array_key_exists($selected, $allowed)) $selected = '';

ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Fila de tarefas</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Tarefas em segundo plano: envio de e-mails, WhatsApp, cobranças, relatórios, etc.</div>
    </div>
</div>

<!-- Explicação -->
<div style="padding:14px 16px;border-radius:12px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.10);font-size:13px;color:rgba(31,41,55,.70);line-height:1.5;margin-bottom:16px;">
    A fila processa tarefas automaticamente em segundo plano. Tarefas com status "Falhou" podem ser reprocessadas manualmente. Use os botões de teste para verificar se o worker está funcionando.
</div>

<!-- Filtro + Testes -->
<div style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;margin-bottom:16px;">
    <form method="get" action="/sys/queue-jobs" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
        <div class="lc-field" style="min-width:180px;">
            <label class="lc-label">Status</label>
            <select class="lc-select" name="status">
                <?php foreach ($allowed as $k=>$v): ?>
                    <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $k === $selected ? 'selected' : '' ?>><?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex;gap:6px;padding-bottom:1px;">
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Filtrar</button>
            <?php if ($selected !== ''): ?><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/queue-jobs">Limpar</a><?php endif; ?>
        </div>
    </form>

    <div style="display:flex;gap:6px;margin-left:auto;">
        <form method="post" action="/sys/queue-jobs/enqueue-test" style="margin:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="job_type" value="test.noop" />
            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Teste (sucesso)</button>
        </form>
        <form method="post" action="/sys/queue-jobs/enqueue-test" style="margin:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="job_type" value="test.throw" />
            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Teste (erro)</button>
        </form>
    </div>
</div>

<!-- Lista -->
<?php if ($items === []): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
        <div style="font-size:32px;margin-bottom:8px;">📭</div>
        <div style="font-size:14px;">Nenhuma tarefa<?= $selected !== '' ? ' com esse filtro' : '' ?>.</div>
    </div>
<?php else: ?>
<div style="border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);overflow:hidden;">
    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead><tr><th>Tarefa</th><th>Fila</th><th>Status</th><th>Tentativas</th><th>Executar em</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <?php
                $itStatus = (string)($it['status'] ?? '');
                $stClr = $statusColor[$itStatus] ?? '#6b7280';
                $stLbl = $allowed[$itStatus] ?? $itStatus;
                $isDead = $itStatus === 'dead';
                $jobCode = (string)($it['job_type'] ?? '');
                $jobHuman = $jobCode;
                if (str_starts_with($jobCode, 'mail.')) $jobHuman = 'E-mail';
                elseif (str_starts_with($jobCode, 'billing.')) $jobHuman = 'Cobrança';
                elseif (str_starts_with($jobCode, 'marketing.')) $jobHuman = 'Marketing';
                elseif (str_starts_with($jobCode, 'whatsapp.')) $jobHuman = 'WhatsApp';
                elseif ($jobCode === 'test.noop') $jobHuman = 'Teste (sucesso)';
                elseif ($jobCode === 'test.throw') $jobHuman = 'Teste (erro)';
                $queueCode = (string)($it['queue'] ?? 'default');
                $runAt = (string)($it['run_at'] ?? '');
                $runFmt = $runAt !== '' ? date('d/m H:i', strtotime($runAt)) : '—';
                ?>
                <tr>
                    <td>
                        <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($jobHuman, ENT_QUOTES, 'UTF-8') ?></div>
                        <div style="font-size:11px;color:rgba(31,41,55,.35);font-family:monospace;"><?= htmlspecialchars($jobCode, ENT_QUOTES, 'UTF-8') ?></div>
                    </td>
                    <td style="font-size:12px;"><?= htmlspecialchars($queueCode, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $stClr ?>18;color:<?= $stClr ?>;border:1px solid <?= $stClr ?>30"><?= htmlspecialchars($stLbl, ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td style="font-size:12px;"><?= (int)($it['attempts'] ?? 0) ?>/<?= (int)($it['max_attempts'] ?? 0) ?></td>
                    <td style="font-size:12px;color:rgba(31,41,55,.50);white-space:nowrap;"><?= htmlspecialchars($runFmt, ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align:right;">
                        <?php if ($isDead): ?>
                            <form method="post" action="/sys/queue-jobs/retry" style="margin:0;display:inline;">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="job_id" value="<?= (int)$it['id'] ?>" />
                                <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Reprocessar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
