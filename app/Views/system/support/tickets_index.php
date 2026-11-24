<?php
$title = 'Admin - Chamados de suporte';
$csrf = $_SESSION['_csrf'] ?? '';

$columns = isset($columns) && is_array($columns) ? $columns : [];
$counts = isset($counts) && is_array($counts) ? $counts : [];
$clinics = isset($clinics) && is_array($clinics) ? $clinics : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$showAllClosed = !empty($show_all_closed);
$success = isset($success) ? (string)$success : '';
$error = isset($error) ? (string)$error : '';

$e = static fn (mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

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

$age = static function (string $createdAt): string {
    $ts = strtotime($createdAt);
    if ($ts === false) {
        return '';
    }
    $diff = max(0, time() - $ts);
    if ($diff < 3600) {
        return max(1, intdiv($diff, 60)) . ' min';
    }
    if ($diff < 86400) {
        return intdiv($diff, 3600) . ' h';
    }
    return intdiv($diff, 86400) . ' d';
};

$fClinic = (int)($filters['clinic_id'] ?? 0);
$fSeverity = (string)($filters['severity'] ?? '');
$fCategory = (string)($filters['category'] ?? '');
$fQ = (string)($filters['q'] ?? '');

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Chamados de suporte</div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/sys/support/settings">Configuração do assistente</a>
    </div>
</div>

<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;"><?= $e($success) ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;"><?= $e($error) ?></div>
<?php endif; ?>

<div class="lc-card lc-card--soft" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <form method="get" action="/sys/support/tickets" class="lc-form lc-flex lc-flex--wrap lc-gap-sm" style="align-items:flex-end;">
            <div class="lc-field">
                <label class="lc-label">Busca</label>
                <input class="lc-input" type="text" name="q" value="<?= $e($fQ) ?>" placeholder="Código, título ou clínica" maxlength="100" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Clínica</label>
                <select class="lc-select" name="clinic_id">
                    <option value="0">Todas</option>
                    <?php foreach ($clinics as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"<?= $fClinic === (int)$c['id'] ? ' selected' : '' ?>><?= $e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lc-field">
                <label class="lc-label">Severidade</label>
                <select class="lc-select" name="severity">
                    <option value="">Todas</option>
                    <?php foreach ($sevLabels as $k => $label): ?>
                        <option value="<?= $e($k) ?>"<?= $fSeverity === $k ? ' selected' : '' ?>><?= $e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lc-field">
                <label class="lc-label">Categoria</label>
                <select class="lc-select" name="category">
                    <option value="">Todas</option>
                    <?php foreach ($catLabels as $k => $label): ?>
                        <option value="<?= $e($k) ?>"<?= $fCategory === $k ? ' selected' : '' ?>><?= $e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($showAllClosed): ?>
                <input type="hidden" name="all_closed" value="1" />
            <?php endif; ?>
            <div class="lc-flex lc-gap-sm">
                <button class="lc-btn lc-btn--primary" type="submit">Filtrar</button>
                <a class="lc-btn lc-btn--secondary" href="/sys/support/tickets">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(5,minmax(220px,1fr));gap:12px;overflow-x:auto;align-items:start;">
    <?php foreach ($statusLabels as $status => $label): ?>
        <?php $items = isset($columns[$status]) && is_array($columns[$status]) ? $columns[$status] : []; ?>
        <div style="border-radius:14px;border:1px solid rgba(17,24,39,.08);background:rgba(17,24,39,.02);padding:10px;min-width:220px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <strong style="font-size:13px;"><?= $e($label) ?></strong>
                <span class="lc-muted" style="font-size:12px;"><?= count($items) ?> / <?= (int)($counts[$status] ?? 0) ?></span>
            </div>

            <?php if ($items === []): ?>
                <div class="lc-muted" style="font-size:12px;padding:8px;">Nenhum chamado.</div>
            <?php endif; ?>

            <?php foreach ($items as $t): ?>
                <?php $sev = (string)($t['severity'] ?? 'media'); $color = $sevColors[$sev] ?? '#6b7280'; ?>
                <a href="/sys/support/tickets/show?id=<?= (int)$t['id'] ?>" style="display:block;text-decoration:none;color:inherit;padding:10px;margin-bottom:8px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);">
                    <div style="display:flex;justify-content:space-between;gap:6px;align-items:center;margin-bottom:6px;">
                        <span style="font-family:monospace;font-size:12px;font-weight:700;"><?= $e($t['public_code']) ?></span>
                        <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;background:<?= $e($color) ?>18;color:<?= $e($color) ?>;border:1px solid <?= $e($color) ?>30;"><?= $e($sevLabels[$sev] ?? $sev) ?></span>
                    </div>
                    <div style="font-weight:650;font-size:13px;line-height:1.35;margin-bottom:6px;"><?= $e($t['title']) ?></div>
                    <div class="lc-muted" style="font-size:12px;display:flex;justify-content:space-between;gap:6px;">
                        <span><?= $e($t['clinic_name'] ?? ('Clínica #' . (int)$t['clinic_id'])) ?></span>
                        <span><?= $e($age((string)$t['created_at'])) ?></span>
                    </div>
                    <div class="lc-muted" style="font-size:11px;margin-top:4px;"><?= $e($catLabels[(string)$t['category']] ?? $t['category']) ?></div>
                </a>
            <?php endforeach; ?>

            <?php if ($status === 'fechado'): ?>
                <div style="margin-top:6px;font-size:12px;">
                    <?php if ($showAllClosed): ?>
                        <?php
                        $q = array_filter(['q' => $fQ, 'clinic_id' => $fClinic > 0 ? $fClinic : '', 'severity' => $fSeverity, 'category' => $fCategory], static fn ($v) => $v !== '');
                        ?>
                        <a href="/sys/support/tickets<?= $q !== [] ? '?' . $e(http_build_query($q)) : '' ?>">Mostrar só os últimos 30 dias</a>
                    <?php else: ?>
                        <?php
                        $q = array_filter(['q' => $fQ, 'clinic_id' => $fClinic > 0 ? $fClinic : '', 'severity' => $fSeverity, 'category' => $fCategory], static fn ($v) => $v !== '');
                        $q['all_closed'] = '1';
                        ?>
                        <span class="lc-muted">Últimos 30 dias.</span> <a href="/sys/support/tickets?<?= $e(http_build_query($q)) ?>">Ver todos</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
