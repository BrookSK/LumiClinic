<?php
$title = 'Perfil';
$patient = $patient ?? null;
$clinic = $clinic ?? null;

$clinicContact = array_filter([
    'contact_whatsapp' => $clinic['contact_whatsapp'] ?? null,
    'contact_phone' => $clinic['contact_phone'] ?? null,
    'contact_email' => $clinic['contact_email'] ?? null,
    'contact_website' => $clinic['contact_website'] ?? null,
    'contact_instagram' => $clinic['contact_instagram'] ?? null,
    'contact_facebook' => $clinic['contact_facebook'] ?? null,
    'contact_address' => $clinic['contact_address'] ?? null,
], static fn($v) => $v !== null && trim((string)$v) !== '');

ob_start();
?>
<div class="lc-card" style="margin-top:16px; padding:16px;">
    <div class="lc-card__title">Seus dados</div>
    <div class="lc-card__body">
        <div class="lc-grid lc-grid--2 lc-gap-grid">
            <div>
                <div class="lc-label">Nome</div>
                <div><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-label">E-mail</div>
                <div><?= htmlspecialchars((string)($patient['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-label">Telefone</div>
                <div><?= htmlspecialchars((string)($patient['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-label">Data de nascimento</div>
                <div><?= htmlspecialchars((string)($patient['birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <div class="lc-alert lc-alert--info" style="margin-top:12px;">
            Para alterar seus dados, entre em contato com a clínica.

            <?php if (!empty($clinicContact)): ?>
                <div style="margin-top:8px;">
                    <?php if (!empty($clinic['contact_whatsapp'])): ?>
                        <div><strong>WhatsApp:</strong> <?= htmlspecialchars((string)$clinic['contact_whatsapp'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <?php if (!empty($clinic['contact_phone'])): ?>
                        <div><strong>Telefone:</strong> <?= htmlspecialchars((string)$clinic['contact_phone'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <?php if (!empty($clinic['contact_email'])): ?>
                        <div><strong>E-mail:</strong> <?= htmlspecialchars((string)$clinic['contact_email'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="lc-card" style="margin-top:16px; padding:16px;">
    <div class="lc-card__title">Clínica</div>
    <div class="lc-card__body">
        <div class="lc-label">Nome</div>
        <div><?= htmlspecialchars((string)($clinic['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>

        <?php if (!empty($clinicContact)): ?>
            <div style="margin-top:12px;" class="lc-grid lc-grid--2 lc-gap-grid">
                <?php if (!empty($clinic['contact_phone'])): ?>
                    <div>
                        <div class="lc-label">Telefone</div>
                        <div><?= htmlspecialchars((string)$clinic['contact_phone'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($clinic['contact_whatsapp'])): ?>
                    <div>
                        <div class="lc-label">WhatsApp</div>
                        <div><?= htmlspecialchars((string)$clinic['contact_whatsapp'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($clinic['contact_email'])): ?>
                    <div>
                        <div class="lc-label">E-mail</div>
                        <div><?= htmlspecialchars((string)$clinic['contact_email'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($clinic['contact_website'])): ?>
                    <div>
                        <div class="lc-label">Site</div>
                        <div><?= htmlspecialchars((string)$clinic['contact_website'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($clinic['contact_instagram'])): ?>
                    <div>
                        <div class="lc-label">Instagram</div>
                        <div><?= htmlspecialchars((string)$clinic['contact_instagram'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($clinic['contact_facebook'])): ?>
                    <div>
                        <div class="lc-label">Facebook</div>
                        <div><?= htmlspecialchars((string)$clinic['contact_facebook'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($clinic['contact_address'])): ?>
                <div style="margin-top:12px;">
                    <div class="lc-label">Endereço</div>
                    <div><?= htmlspecialchars((string)$clinic['contact_address'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'perfil';
require __DIR__ . '/_shell.php';
