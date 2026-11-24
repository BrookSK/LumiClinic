<?php
$title = 'Admin - Base de conhecimento do suporte';
$csrf = $_SESSION['_csrf'] ?? '';

$tab = isset($tab) ? (string)$tab : 'draft';
$tabs = isset($tabs) && is_array($tabs) ? $tabs : [];
$counts = isset($counts) && is_array($counts) ? $counts : [];
$entries = isset($entries) && is_array($entries) ? $entries : [];
$ticketCodes = isset($ticket_codes) && is_array($ticket_codes) ? $ticket_codes : [];
$success = isset($success) ? (string)$success : '';
$error = isset($error) ? (string)$error : '';

$e = static fn (mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$fmt = static function (?string $dt): string {
    $ts = $dt !== null && $dt !== '' ? strtotime($dt) : false;
    return $ts !== false ? date('d/m/Y H:i', $ts) : '-';
};

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Base de conhecimento do suporte</div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/sys/support/tickets">Chamados</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/support/kb/export.md">Exportar aprovadas (.md)</a>
    </div>
</div>

<div class="lc-alert lc-alert--info" style="margin-bottom:12px;">
    Nada chega ao assistente sem a sua aprovação. Antes de aprovar, confira se a resposta serve para qualquer clínica,
    sem nomes, dados de pacientes, detalhes internos da plataforma ou promessas de prazo. Entradas aprovadas valem na hora.
</div>

<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;"><?= $e($success) ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;"><?= $e($error) ?></div>
<?php endif; ?>

<div class="lc-flex lc-gap-sm" style="margin-bottom:14px;">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="lc-btn <?= $tab === $key ? 'lc-btn--primary' : 'lc-btn--secondary' ?>" href="/sys/support/kb?tab=<?= $e($key) ?>">
            <?= $e($label) ?> (<?= (int)($counts[$key] ?? 0) ?>)
        </a>
    <?php endforeach; ?>
</div>

<?php if ($entries === []): ?>
    <div class="lc-card lc-card--soft"><div class="lc-muted" style="padding:12px;">Nenhuma entrada nesta aba.</div></div>
<?php endif; ?>

<?php foreach ($entries as $en): ?>
    <?php
    $id = (int)$en['id'];
    $status = (string)$en['status'];
    $tid = (int)($en['source_ticket_id'] ?? 0);
    $warnings = trim((string)($en['warnings'] ?? ''));
    $readonly = $status === 'approved';
    ?>
    <div class="lc-card lc-card--soft" style="margin-bottom:14px;">
        <div class="lc-card__header">
            <div class="lc-card__title">Entrada #<?= $id ?></div>
            <div class="lc-muted" style="font-size:12px;">
                <?php if ($tid > 0): ?>
                    Origem: <a href="/sys/support/tickets/show?id=<?= $tid ?>"><?= $e($ticketCodes[$tid] ?? ('chamado ' . $tid)) ?></a> &middot;
                <?php endif; ?>
                Criada em <?= $e($fmt((string)($en['created_at'] ?? ''))) ?>
                <?php if (!empty($en['reviewed_at'])): ?>&middot; Revisada em <?= $e($fmt((string)$en['reviewed_at'])) ?><?php endif; ?>
                <?php if (!empty($en['exported_at'])): ?>&middot; Exportada em <?= $e($fmt((string)$en['exported_at'])) ?><?php endif; ?>
            </div>
        </div>

        <?php if ($warnings !== ''): ?>
            <div class="lc-alert lc-alert--warning" style="margin:8px 12px;">
                <b>Atenção:</b>
                <ul style="margin:6px 0 0 18px;padding:0;">
                    <?php foreach (explode("\n", $warnings) as $w): ?>
                        <?php if (trim($w) !== ''): ?><li><?= $e($w) ?></li><?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="lc-form" style="padding:0 12px 12px;">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
            <input type="hidden" name="id" value="<?= $id ?>" />
            <div class="lc-field">
                <label class="lc-label">Pergunta</label>
                <input class="lc-input" type="text" name="question" maxlength="300" value="<?= $e($en['question']) ?>" <?= $readonly ? 'readonly' : 'required' ?> />
            </div>
            <div class="lc-field">
                <label class="lc-label">Resposta</label>
                <textarea class="lc-textarea" name="answer" rows="7" maxlength="2000" <?= $readonly ? 'readonly' : 'required' ?>><?= $e($en['answer']) ?></textarea>
            </div>
            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:8px;">
                <?php if (!$readonly): ?>
                    <button class="lc-btn lc-btn--primary" type="submit" formaction="/sys/support/kb/approve">Aprovar</button>
                    <button class="lc-btn lc-btn--secondary" type="submit" formaction="/sys/support/kb/save">Salvar edição</button>
                <?php endif; ?>
                <?php if ($status !== 'rejected'): ?>
                    <button class="lc-btn lc-btn--secondary" type="submit" formaction="/sys/support/kb/reject" formnovalidate>Rejeitar</button>
                <?php endif; ?>
                <button class="lc-btn lc-btn--danger" type="submit" formaction="/sys/support/kb/delete" formnovalidate onclick="return confirm('Excluir esta entrada?');">Excluir</button>
            </div>
        </form>
    </div>
<?php endforeach; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
