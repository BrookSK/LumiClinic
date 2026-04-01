<?php
$title = 'Aniversariantes';
$patients = $patients ?? [];
$month = isset($month) ? (int)$month : (int)date('n');
$csrf = $_SESSION['_csrf'] ?? '';

$monthNames = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
];

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Aniversariantes</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients">Pacientes</a>
        <a class="lc-btn lc-btn--secondary" href="/patients/follow-up">Follow-up</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <form method="get" action="/patients/birthdays" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
            <div class="lc-field">
                <label class="lc-label">Mês</label>
                <select class="lc-select" name="month">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= htmlspecialchars($monthNames[$m], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button class="lc-btn lc-btn--primary" type="submit">Filtrar</button>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">
        Aniversariantes de <?= htmlspecialchars($monthNames[$month] ?? (string)$month, ENT_QUOTES, 'UTF-8') ?>
        <span class="lc-badge lc-badge--primary" style="margin-left:8px;"><?= count($patients) ?></span>
    </div>
    <div class="lc-card__body">
        <?php if ($patients === []): ?>
            <div class="lc-muted">Nenhum aniversariante neste mês.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Data de nascimento</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                    <th>WhatsApp</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($patients as $p): ?>
                    <?php
                        $birthDate = (string)($p['birth_date'] ?? '');
                        $day = '';
                        if ($birthDate !== '' && strlen($birthDate) >= 10) {
                            $day = substr($birthDate, 8, 2) . '/' . substr($birthDate, 5, 2);
                        }
                        $phone = trim((string)($p['phone'] ?? ''));
                        $waOptIn = (int)($p['whatsapp_opt_in'] ?? 0);
                        $waLink = '';
                        if ($phone !== '') {
                            $phoneDigits = preg_replace('/\D/', '', $phone);
                            $waLink = 'https://wa.me/' . $phoneDigits;
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($p['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($day, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($p['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $waOptIn ? '<span class="lc-badge lc-badge--success">Sim</span>' : '<span class="lc-badge lc-badge--secondary">Não</span>' ?></td>
                        <td class="lc-td-actions">
                            <div class="lc-flex lc-gap-sm">
                                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/patients/view?id=<?= (int)$p['id'] ?>">Ver</a>
                                <?php if ($waLink !== '' && $waOptIn): ?>
                                    <a class="lc-btn lc-btn--secondary lc-btn--sm" href="<?= htmlspecialchars($waLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">WhatsApp</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
