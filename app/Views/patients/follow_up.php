<?php
$title = 'Follow-up de Pacientes';
$patients = $patients ?? [];
$days = isset($days) ? (int)$days : 180;
$csrf = $_SESSION['_csrf'] ?? '';

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Follow-up</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients">Pacientes</a>
        <a class="lc-btn lc-btn--secondary" href="/patients/birthdays">Aniversariantes</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <form method="get" action="/patients/follow-up" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
            <div class="lc-field">
                <label class="lc-label">Sem consulta há mais de (dias)</label>
                <select class="lc-select" name="days">
                    <?php foreach ([90, 120, 180, 270, 365] as $d): ?>
                        <option value="<?= $d ?>" <?= $d === $days ? 'selected' : '' ?>><?= $d ?> dias</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="lc-btn lc-btn--primary" type="submit">Filtrar</button>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">
        Pacientes sem consulta há mais de <?= (int)$days ?> dias
        <span class="lc-badge lc-badge--primary" style="margin-left:8px;"><?= count($patients) ?></span>
    </div>
    <div class="lc-card__body">
        <?php if ($patients === []): ?>
            <div class="lc-muted">Nenhum paciente encontrado para este critério.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Última consulta</th>
                    <th>Telefone</th>
                    <th>WhatsApp</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($patients as $p): ?>
                    <?php
                        $lastAt = trim((string)($p['last_appointment_at'] ?? ''));
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
                        <td><?= $lastAt !== '' ? htmlspecialchars($lastAt, ENT_QUOTES, 'UTF-8') : '<span class="lc-muted">Nunca</span>' ?></td>
                        <td><?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?></td>
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
