<?php
$title = 'Configuração Inicial';
$csrf = $_SESSION['_csrf'] ?? '';

$steps = [
    ['id'=>'clinic','num'=>1,'label'=>'Dados da clínica','desc'=>'Preencha o nome, endereço e contato da clínica.','url'=>'/clinic','btn'=>'Configurar'],
    ['id'=>'working_hours','num'=>2,'label'=>'Horários de funcionamento','desc'=>'Defina os dias e horários em que a clínica atende.','url'=>'/clinic/working-hours','btn'=>'Configurar'],
    ['id'=>'users','num'=>3,'label'=>'Cadastrar usuários','desc'=>'Adicione os profissionais e funcionários que vão usar o sistema.','url'=>'/users','btn'=>'Cadastrar'],
    ['id'=>'schedule_rules','num'=>4,'label'=>'Regras de agenda','desc'=>'Defina os horários de atendimento de cada profissional.','url'=>'/schedule-rules','btn'=>'Configurar'],
    ['id'=>'service','num'=>5,'label'=>'Cadastrar serviços','desc'=>'Crie os serviços que a clínica oferece (ex: consulta, limpeza).','url'=>'/services','btn'=>'Cadastrar'],
    ['id'=>'patient','num'=>6,'label'=>'Primeiro paciente','desc'=>'Cadastre um paciente para testar o fluxo.','url'=>'/patients/create','btn'=>'Cadastrar'],
    ['id'=>'appointment','num'=>7,'label'=>'Primeiro agendamento','desc'=>'Agende uma consulta para validar que tudo funciona.','url'=>'/schedule','btn'=>'Agendar'],
];

ob_start();
?>

<style>
.ob-head{margin-bottom:20px}
.ob-head__title{font-weight:850;font-size:22px;color:rgba(31,41,55,.96)}
.ob-head__desc{font-size:13px;color:rgba(31,41,55,.50);margin-top:4px;line-height:1.5;max-width:600px}
.ob-progress{display:flex;align-items:center;gap:10px;margin-bottom:20px;padding:14px 16px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.10)}
.ob-progress__bar{flex:1;height:8px;border-radius:999px;background:rgba(17,24,39,.08);overflow:hidden}
.ob-progress__fill{height:100%;border-radius:999px;background:linear-gradient(135deg,#fde59f,#815901);transition:width 300ms ease}
.ob-progress__text{font-size:13px;font-weight:700;color:rgba(129,89,1,1);white-space:nowrap}
.ob-step{display:flex;align-items:center;gap:14px;padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 2px 8px rgba(17,24,39,.04);margin-bottom:10px;transition:all 160ms ease}
.ob-step:hover{border-color:rgba(129,89,1,.18);box-shadow:0 4px 16px rgba(17,24,39,.08)}
.ob-step__num{width:36px;height:36px;border-radius:999px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;flex-shrink:0;border:2px solid rgba(238,184,16,.30);color:rgba(129,89,1,1);background:rgba(238,184,16,.08)}
.ob-step__num--done{background:rgba(22,163,74,.12);border-color:rgba(22,163,74,.30);color:#16a34a}
.ob-step__info{flex:1;min-width:0}
.ob-step__label{font-weight:750;font-size:14px;color:rgba(31,41,55,.96)}
.ob-step__desc{font-size:12px;color:rgba(31,41,55,.45);margin-top:2px}
.ob-step__actions{display:flex;align-items:center;gap:8px;flex-shrink:0}
.ob-step__check{font-size:18px;color:#16a34a;display:none}
.ob-step__check--visible{display:inline}
</style>

<div class="ob-head">
    <div class="ob-head__title">Configuração inicial</div>
    <div class="ob-head__desc">Siga os passos abaixo para deixar o sistema pronto. Você pode voltar aqui a qualquer momento em Ajuda → Configuração inicial.</div>
</div>

<div class="ob-progress">
    <div class="ob-progress__bar"><div class="ob-progress__fill" id="obProgressFill" style="width:0%"></div></div>
    <div class="ob-progress__text" id="obProgressText">0/<?= count($steps) ?></div>
</div>

<?php foreach ($steps as $step): ?>
    <div class="ob-step" id="step-<?= $step['id'] ?>">
        <div class="ob-step__num" id="num-<?= $step['id'] ?>"><?= $step['num'] ?></div>
        <div class="ob-step__info">
            <div class="ob-step__label"><?= htmlspecialchars($step['label'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="ob-step__desc"><?= htmlspecialchars($step['desc'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="ob-step__actions">
            <span class="ob-step__check" id="check-<?= $step['id'] ?>">✓</span>
            <a class="lc-btn lc-btn--primary lc-btn--sm" href="<?= htmlspecialchars($step['url'], ENT_QUOTES, 'UTF-8') ?>" onclick="markDone('<?= $step['id'] ?>')"><?= htmlspecialchars($step['btn'], ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>
<?php endforeach; ?>

<div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
    <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/tutorial/sistema">Ver tutoriais completos</a>
    <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/">Ir para o Dashboard</a>
</div>

<script>
(function(){
    var KEY='lc.onboarding.done',total=<?= count($steps) ?>;
    var done={};
    try{done=JSON.parse(localStorage.getItem(KEY)||'{}');}catch(e){}

    function updateProgress(){
        var c=0;
        Object.keys(done).forEach(function(k){if(done[k])c++;});
        var pct=Math.round((c/total)*100);
        var fill=document.getElementById('obProgressFill');
        var txt=document.getElementById('obProgressText');
        if(fill)fill.style.width=pct+'%';
        if(txt)txt.textContent=c+'/'+total;
    }

    function markDone(id){
        done[id]=true;
        try{localStorage.setItem(KEY,JSON.stringify(done));}catch(e){}
        var el=document.getElementById('check-'+id);
        if(el)el.classList.add('ob-step__check--visible');
        var num=document.getElementById('num-'+id);
        if(num)num.classList.add('ob-step__num--done');
        updateProgress();
    }
    window.markDone=markDone;

    Object.keys(done).forEach(function(id){
        if(done[id]){
            var el=document.getElementById('check-'+id);
            if(el)el.classList.add('ob-step__check--visible');
            var num=document.getElementById('num-'+id);
            if(num)num.classList.add('ob-step__num--done');
        }
    });
    updateProgress();
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
