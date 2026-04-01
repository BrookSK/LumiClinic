<?php
$title = 'Configuração inicial';
$csrf = $_SESSION['_csrf'] ?? '';

// Checklist de passos mínimos para o sistema funcionar
$steps = [
    [
        'id'    => 'clinic',
        'label' => 'Dados da clínica',
        'desc'  => 'Nome, endereço e informações de contato.',
        'url'   => '/clinic',
        'btn'   => 'Configurar clínica',
    ],
    [
        'id'    => 'working_hours',
        'label' => 'Horários de funcionamento',
        'desc'  => 'Defina os dias e horários em que a clínica atende.',
        'url'   => '/clinic/working-hours',
        'btn'   => 'Configurar horários',
    ],
    [
        'id'    => 'professional',
        'label' => 'Cadastrar profissional',
        'desc'  => 'Adicione pelo menos um profissional para poder agendar.',
        'url'   => '/professionals',
        'btn'   => 'Cadastrar profissional',
    ],
    [
        'id'    => 'service',
        'label' => 'Cadastrar serviço',
        'desc'  => 'Crie os serviços/procedimentos que a clínica oferece.',
        'url'   => '/services',
        'btn'   => 'Cadastrar serviço',
    ],
    [
        'id'    => 'schedule_rules',
        'label' => 'Regras de agenda do profissional',
        'desc'  => 'Defina os dias e horários disponíveis de cada profissional.',
        'url'   => '/schedule-rules',
        'btn'   => 'Configurar regras',
    ],
    [
        'id'    => 'patient',
        'label' => 'Cadastrar primeiro paciente',
        'desc'  => 'Adicione um paciente para testar o fluxo completo.',
        'url'   => '/patients/create',
        'btn'   => 'Cadastrar paciente',
    ],
    [
        'id'    => 'appointment',
        'label' => 'Fazer primeiro agendamento',
        'desc'  => 'Agende uma consulta para validar que tudo está funcionando.',
        'url'   => '/schedule',
        'btn'   => 'Abrir agenda',
    ],
];

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div>
        <div class="lc-badge lc-badge--primary">Configuração inicial</div>
        <div class="lc-muted" style="margin-top:6px;">Siga os passos abaixo para deixar o sistema pronto para uso.</div>
    </div>
    <a class="lc-btn lc-btn--secondary" href="/">Ir para o Dashboard</a>
</div>

<div class="lc-card">
    <div class="lc-card__body" style="padding:0;">
        <?php foreach ($steps as $i => $step): ?>
            <div id="step-<?= htmlspecialchars($step['id'], ENT_QUOTES, 'UTF-8') ?>"
                 class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap"
                 style="padding:16px 20px; border-bottom:1px solid rgba(0,0,0,.06); gap:12px;">
                <div class="lc-flex lc-gap-md" style="align-items:flex-start; flex:1; min-width:0;">
                    <div style="width:32px; height:32px; border-radius:50%; background:var(--lc-primary, #2563eb); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; flex-shrink:0; font-size:14px;">
                        <?= $i + 1 ?>
                    </div>
                    <div>
                        <div style="font-weight:700;"><?= htmlspecialchars($step['label'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="lc-muted" style="font-size:13px; margin-top:2px;"><?= htmlspecialchars($step['desc'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
                <div class="lc-flex lc-gap-sm" style="flex-shrink:0; align-items:center;">
                    <span id="check-<?= htmlspecialchars($step['id'], ENT_QUOTES, 'UTF-8') ?>"
                          style="display:none; color:#16a34a; font-weight:700; font-size:18px;">✓</span>
                    <a class="lc-btn lc-btn--primary lc-btn--sm"
                       href="<?= htmlspecialchars($step['url'], ENT_QUOTES, 'UTF-8') ?>"
                       onclick="markDone('<?= htmlspecialchars($step['id'], ENT_QUOTES, 'UTF-8') ?>')">
                        <?= htmlspecialchars($step['btn'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="lc-card" style="margin-top:16px;">
    <div class="lc-card__body">
        <div class="lc-muted" style="margin-bottom:8px;">Pode voltar a este tutorial a qualquer momento em <strong>Ajuda → Configuração inicial</strong>.</div>
        <a class="lc-btn lc-btn--secondary" href="/tutorial/sistema">Ver tutoriais completos</a>
    </div>
</div>

<script>
(function(){
    var KEY = 'lc.onboarding.done';
    var done = {};
    try { done = JSON.parse(localStorage.getItem(KEY) || '{}'); } catch(e) {}

    function markDone(id) {
        done[id] = true;
        try { localStorage.setItem(KEY, JSON.stringify(done)); } catch(e) {}
        var el = document.getElementById('check-' + id);
        if (el) el.style.display = 'inline';
    }
    window.markDone = markDone;

    // Restaurar estado salvo
    Object.keys(done).forEach(function(id){
        if (done[id]) {
            var el = document.getElementById('check-' + id);
            if (el) el.style.display = 'inline';
        }
    });
})();
</script>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
