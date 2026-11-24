<?php
$title = 'Admin - WhatsApp do suporte';
$csrf = $_SESSION['_csrf'] ?? '';

$globalConfigured = !empty($global_configured);
$instance = (string)($instance ?? '');
$phones = isset($phones) && is_array($phones) ? $phones : [];
$editPhone = isset($edit_phone) && is_array($edit_phone) ? $edit_phone : null;
$maxPhones = (int)($max_phones ?? 20);
$deliveries = isset($deliveries) && is_array($deliveries) ? $deliveries : [];
$success = isset($success) ? (string)$success : '';
$error = isset($error) ? (string)$error : '';

$e = static fn (mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$maskPhone = static function (string $phone): string {
    $d = preg_replace('/\D/', '', $phone) ?? '';
    return strlen($d) > 4 ? str_repeat('*', strlen($d) - 4) . substr($d, -4) : '****';
};
$fmt = static function (?string $dt): string {
    $ts = $dt !== null && $dt !== '' ? strtotime($dt) : false;
    return $ts !== false ? date('d/m/Y H:i', $ts) : '-';
};
$statusLabels = ['sent' => 'Enviado', 'failed' => 'Falhou', 'skipped' => 'Ignorado'];
$statusColors = ['sent' => '#16a34a', 'failed' => '#b91c1c', 'skipped' => '#6b7280'];

ob_start();
?>

<style>
.sw-qr{display:none;justify-content:center;padding:16px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:#fff;margin-top:14px}
.sw-qr img{max-width:260px;width:100%;height:auto}
.sw-table{width:100%;border-collapse:collapse;font-size:13px}
.sw-table th,.sw-table td{text-align:left;padding:8px 6px;border-bottom:1px solid rgba(17,24,39,.06);vertical-align:middle}
.sw-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:700;color:#fff}
</style>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">WhatsApp do suporte</div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/sys/support/tickets">Chamados</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/support/settings">Configuração do assistente</a>
    </div>
</div>

<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;"><?= $e($success) ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;"><?= $e($error) ?></div>
<?php endif; ?>

<?php if (!$globalConfigured): ?>
    <div class="lc-alert lc-alert--warning" style="margin-bottom:12px;">
        A Evolution API global ainda não está configurada (URL e token). Configure em
        <a href="/sys/settings/whatsapp">/sys/settings/whatsapp</a> para conectar o WhatsApp do suporte.
    </div>
<?php endif; ?>

<div class="lc-card lc-card--soft" style="margin-bottom:16px;">
    <div class="lc-card__header"><div class="lc-card__title">Conexão do WhatsApp</div></div>
    <div class="lc-card__body">
        <input type="hidden" id="sw-csrf" value="<?= $e($csrf) ?>" />
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
            <span>Status:</span>
            <span id="sw-status" class="sw-badge" style="background:#6b7280;"><?= $globalConfigured ? 'Consultando...' : 'Não configurado' ?></span>
            <span class="lc-muted" style="font-size:12px;">Instância: <code><?= $e($instance) ?></code></span>
        </div>
        <div class="lc-muted" style="font-size:13px;line-height:1.5;margin-bottom:12px;">
            Este número envia os avisos de novos chamados aos desenvolvedores. Clique em "Gerar QR Code" e, no celular, abra
            WhatsApp &gt; Aparelhos conectados &gt; Conectar aparelho e leia o código.
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button id="sw-connect" type="button" class="lc-btn lc-btn--primary"<?= $globalConfigured ? '' : ' disabled' ?>>Gerar QR Code</button>
            <button id="sw-disconnect" type="button" class="lc-btn lc-btn--danger"<?= $globalConfigured ? '' : ' disabled' ?>>Desconectar</button>
        </div>
        <div id="sw-msg" class="lc-muted" style="font-size:12px;margin-top:8px;"></div>
        <div id="sw-qr" class="sw-qr"><img id="sw-qr-img" alt="QR Code do WhatsApp" /></div>
        <div id="sw-pair" class="lc-muted" style="display:none;margin-top:8px;font-size:13px;"></div>
        <pre id="sw-code" style="display:none;white-space:pre-wrap;word-break:break-word;font-size:11px;margin-top:8px;"></pre>
    </div>
</div>

<div class="lc-card lc-card--soft" style="margin-bottom:16px;">
    <div class="lc-card__header"><div class="lc-card__title">Números dos desenvolvedores (<?= count($phones) ?>/<?= $maxPhones ?>)</div></div>
    <div class="lc-card__body">
        <?php if ($phones === []): ?>
            <div class="lc-muted" style="font-size:13px;margin-bottom:10px;">Nenhum número cadastrado. Sem números ativos, ninguém recebe o aviso de novos chamados no WhatsApp.</div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="sw-table">
                    <thead><tr><th>Nome</th><th>Telefone</th><th>Ativo</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php foreach ($phones as $p): ?>
                        <tr>
                            <td><?= $e($p['name']) ?></td>
                            <td style="font-family:monospace;"><?= $e($maskPhone((string)$p['phone'])) ?></td>
                            <td>
                                <form method="post" action="/sys/support/whatsapp/phones/toggle" style="margin:0;">
                                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>" />
                                    <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit"><?= (int)$p['is_active'] === 1 ? 'Ativo (desativar)' : 'Inativo (ativar)' ?></button>
                                </form>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/support/whatsapp?edit=<?= (int)$p['id'] ?>#sw-form">Editar</a>
                                    <form method="post" action="/sys/support/whatsapp/test" style="margin:0;">
                                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>" />
                                        <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Enviar teste</button>
                                    </form>
                                    <form method="post" action="/sys/support/whatsapp/phones/delete" style="margin:0;" onsubmit="return confirm('Remover este número?');">
                                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>" />
                                        <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <form id="sw-form" method="post" action="/sys/support/whatsapp/phones/save" class="lc-form lc-grid lc-grid--2 lc-gap-grid" style="margin-top:14px;">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
            <input type="hidden" name="id" value="<?= $editPhone !== null ? (int)$editPhone['id'] : 0 ?>" />
            <div class="lc-field">
                <label class="lc-label">Nome</label>
                <input class="lc-input" type="text" name="name" maxlength="120" required value="<?= $editPhone !== null ? $e($editPhone['name']) : '' ?>" placeholder="Nome do desenvolvedor" />
            </div>
            <div class="lc-field">
                <label class="lc-label">WhatsApp (com DDD)</label>
                <input class="lc-input" type="text" name="phone" maxlength="32" required inputmode="tel" value="" autocomplete="off"
                       placeholder="<?= $editPhone !== null ? 'Atual: ' . $e($maskPhone((string)$editPhone['phone'])) . ' (digite o número completo)' : '(11) 91234-5678' ?>" />
                <div class="lc-muted" style="font-size:12px;margin-top:6px;">O código do Brasil (55) é adicionado automaticamente.</div>
            </div>
            <div style="grid-column:1 / -1;display:flex;gap:10px;">
                <button class="lc-btn lc-btn--primary" type="submit"><?= $editPhone !== null ? 'Salvar alterações' : 'Adicionar número' ?></button>
                <?php if ($editPhone !== null): ?>
                    <a class="lc-btn lc-btn--secondary" href="/sys/support/whatsapp">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="lc-card lc-card--soft">
    <div class="lc-card__header"><div class="lc-card__title">Últimas notificações enviadas</div></div>
    <div class="lc-card__body">
        <?php if ($deliveries === []): ?>
            <div class="lc-muted" style="font-size:13px;">Nenhuma notificação registrada ainda.</div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="sw-table">
                    <thead><tr><th>Data</th><th>Chamado</th><th>Canal</th><th>Destino</th><th>Situação</th><th>Detalhe</th></tr></thead>
                    <tbody>
                    <?php foreach ($deliveries as $d): ?>
                        <?php
                        $st = (string)$d['status'];
                        $dest = (string)$d['destination'];
                        $destShown = str_contains($dest, '@')
                            ? mb_substr(explode('@', $dest, 2)[0], 0, 2) . '***@' . (explode('@', $dest, 2)[1] ?? '')
                            : $maskPhone($dest);
                        ?>
                        <tr>
                            <td><?= $e($fmt((string)$d['created_at'])) ?></td>
                            <td><a href="/sys/support/tickets/show?id=<?= (int)$d['ticket_id'] ?>"><?= $e($d['public_code'] ?? ('#' . (int)$d['ticket_id'])) ?></a></td>
                            <td><?= $e($d['channel']) ?></td>
                            <td style="font-family:monospace;"><?= $e($destShown) ?></td>
                            <td><span class="sw-badge" style="background:<?= $e($statusColors[$st] ?? '#6b7280') ?>;"><?= $e($statusLabels[$st] ?? $st) ?></span></td>
                            <td style="word-break:break-word;"><?= $e($d['error'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($globalConfigured): ?>
<script>
(function(){
    var csrf=document.getElementById('sw-csrf').value,
        st=document.getElementById('sw-status'),msg=document.getElementById('sw-msg'),
        qrBox=document.getElementById('sw-qr'),qrImg=document.getElementById('sw-qr-img'),
        pair=document.getElementById('sw-pair'),code=document.getElementById('sw-code'),
        btnC=document.getElementById('sw-connect'),btnD=document.getElementById('sw-disconnect');
    var lastQr=0,wantQr=false,busy=false;
    function setSt(t,color){st.textContent=t;st.style.background=color;}
    function hideQr(){qrBox.style.display='none';pair.style.display='none';code.style.display='none';}
    function post(u){return fetch(u,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json;charset=UTF-8','X-CSRF-Token':csrf},body:'{}'}).then(function(r){return r.json();});}
    function showQr(d){
        lastQr=Date.now();
        if(d.image&&/^data:image\//.test(d.image)){qrImg.src=d.image;qrBox.style.display='flex';code.style.display='none';}
        else{qrBox.style.display='none';code.textContent=d.code||'';code.style.display=d.code?'block':'none';}
        if(d.pairing){pair.textContent='Código de pareamento: '+d.pairing;pair.style.display='block';}else{pair.style.display='none';}
    }
    function gen(){
        if(busy)return;busy=true;wantQr=true;btnC.disabled=true;msg.textContent='Gerando QR Code...';
        post('/sys/support/whatsapp/connect').then(function(d){
            busy=false;btnC.disabled=false;
            if(d&&d.ok){msg.textContent='Leia o QR Code com o WhatsApp. A página atualiza sozinha quando conectar.';showQr(d);}
            else{msg.textContent=(d&&d.error)||'Falha ao gerar o QR Code.';}
        }).catch(function(){busy=false;btnC.disabled=false;msg.textContent='Falha de comunicação ao gerar o QR Code.';});
    }
    function tick(){
        fetch('/sys/support/whatsapp/status-json',{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(d){
            if(!d){return;}
            if(d.connected){setSt('Conectado','#16a34a');hideQr();wantQr=false;return;}
            if(d.error){setSt('Erro ao consultar','#b91c1c');msg.textContent=d.error;return;}
            setSt(d.label||'Desconectado',d.state==='connecting'?'#d97706':'#6b7280');
            if(wantQr&&!busy&&Date.now()-lastQr>25000){gen();}
        }).catch(function(){setSt('Sem resposta','#6b7280');});
    }
    btnC.addEventListener('click',gen);
    btnD.addEventListener('click',function(){
        if(!confirm('Desconectar o WhatsApp do suporte? Os avisos de chamados deixarão de ser enviados até reconectar.'))return;
        btnD.disabled=true;
        post('/sys/support/whatsapp/disconnect').then(function(d){
            btnD.disabled=false;wantQr=false;hideQr();
            msg.textContent=(d&&d.ok)?'WhatsApp desconectado.':((d&&d.error)||'Falha ao desconectar.');
            tick();
        }).catch(function(){btnD.disabled=false;msg.textContent='Falha de comunicação ao desconectar.';});
    });
    tick();setInterval(tick,3000);
})();
</script>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
