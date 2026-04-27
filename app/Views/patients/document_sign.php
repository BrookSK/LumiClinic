<?php
$title = 'Enviar Documento para Assinatura';
$csrf = $_SESSION['_csrf'] ?? '';
$patient = $patient ?? null;
$items = $items ?? [];
$success = $success ?? '';
$error = $error ?? '';
$patientId = (int)($patient['id'] ?? 0);
$patientName = (string)($patient['name'] ?? '');

$statusLabel = ['pending' => 'Aguardando', 'signed' => 'Assinado', 'expired' => 'Expirado', 'cancelled' => 'Cancelado'];
$statusBadge = ['pending' => 'lc-badge--primary', 'signed' => 'lc-badge--success', 'expired' => 'lc-badge--secondary', 'cancelled' => 'lc-badge--danger'];

ob_start();
?>

<?php if ($success !== ''): ?>
<div style="padding:12px 16px;border-radius:10px;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);margin-bottom:14px;font-size:13px;color:#16a34a;font-weight:600;">✅ <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
<div style="padding:12px 16px;border-radius:10px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);margin-bottom:14px;font-size:13px;color:#dc2626;font-weight:600;">⚠ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px;gap:10px;">
    <div>
        <div style="font-weight:800;font-size:18px;">Documentos para Assinatura</div>
        <div class="lc-muted" style="font-size:13px;margin-top:2px;"><?= htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= $patientId ?>">← Voltar ao paciente</a>
    </div>
</div>

<!-- Novo documento -->
<div class="lc-card" style="margin-bottom:16px;">
    <div class="lc-card__header" style="font-weight:700;">Enviar novo documento</div>
    <div class="lc-card__body">
        <form method="post" action="/patients/document-sign/create" enctype="multipart/form-data" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="patient_id" value="<?= $patientId ?>" />

            <div class="lc-grid lc-gap-grid" style="grid-template-columns:1fr 1fr;align-items:end;">
                <div class="lc-field">
                    <label class="lc-label">Título do documento *</label>
                    <input class="lc-input" type="text" name="title" required placeholder="Ex: Termo de consentimento, Contrato de tratamento..." />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Arquivo (PDF, imagem — opcional)</label>
                    <input class="lc-input" type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" />
                </div>
            </div>

            <div class="lc-field" style="margin-top:10px;">
                <label class="lc-label">Conteúdo / Texto do documento (opcional)</label>
                <textarea class="lc-input" name="body" rows="5" placeholder="Cole aqui o texto do documento que o paciente precisa ler e assinar. Se preferir, envie apenas o arquivo acima."></textarea>
            </div>

            <div class="lc-field" style="margin-top:10px;">
                <label class="lc-label">Enviar via</label>
                <div class="lc-flex lc-gap-sm" style="margin-top:4px;">
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                        <input type="radio" name="send_via" value="whatsapp" checked /> 📱 WhatsApp
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                        <input type="radio" name="send_via" value="email" /> 📧 E-mail
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                        <input type="radio" name="send_via" value="portal" /> 🌐 Apenas no Portal
                    </label>
                </div>
            </div>

            <div style="margin-top:12px;">
                <button class="lc-btn lc-btn--primary" type="submit">Enviar para assinatura</button>
            </div>
        </form>
    </div>
</div>

<!-- Lista de documentos enviados -->
<div class="lc-card">
    <div class="lc-card__header" style="font-weight:700;">Documentos enviados <span class="lc-badge lc-badge--secondary" style="margin-left:6px;"><?= count($items) ?></span></div>
    <div class="lc-card__body" style="padding:0;">
        <?php if (empty($items)): ?>
            <div class="lc-muted" style="padding:24px;text-align:center;">Nenhum documento enviado para este paciente.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Título</th>
                    <th>Enviado via</th>
                    <th>Status</th>
                    <th>Data envio</th>
                    <th>Assinado em</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <?php
                    $st = (string)($it['status'] ?? 'pending');
                    $sentAt = trim((string)($it['sent_at'] ?? ''));
                    $signedAt = trim((string)($it['signed_at'] ?? ''));
                    ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars((string)($it['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php
                            $via = (string)($it['sent_via'] ?? '');
                            echo match($via) {
                                'whatsapp' => '📱 WhatsApp',
                                'email' => '📧 E-mail',
                                'portal' => '🌐 Portal',
                                default => '—',
                            };
                            ?>
                        </td>
                        <td><span class="lc-badge <?= $statusBadge[$st] ?? 'lc-badge--secondary' ?>"><?= $statusLabel[$st] ?? $st ?></span></td>
                        <td style="font-size:12px;"><?= $sentAt !== '' ? date('d/m/Y H:i', strtotime($sentAt)) : '—' ?></td>
                        <td style="font-size:12px;"><?= $signedAt !== '' ? date('d/m/Y H:i', strtotime($signedAt)) : '—' ?></td>
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
