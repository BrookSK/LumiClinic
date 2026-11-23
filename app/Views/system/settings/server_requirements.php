<?php
$title = 'Admin - Requisitos do Servidor';
$csrf = $_SESSION['_csrf'] ?? '';

// Verificar dependências
$checks = [];

// PHP
$checks[] = ['name'=>'PHP','version'=>PHP_VERSION,'ok'=>version_compare(PHP_VERSION,'8.1.0','>='),'cmd'=>'','note'=>'Mínimo 8.1'];

// Extensões PHP
$exts = ['pdo','pdo_mysql','mbstring','json','curl','fileinfo','openssl','gd'];
foreach ($exts as $e) {
    $checks[] = ['name'=>'PHP ext: '.$e,'version'=>extension_loaded($e)?'Instalada':'Não encontrada','ok'=>extension_loaded($e),'cmd'=>'apt install php-'.$e,'note'=>''];
}

// ffmpeg
$ffmpegOk = false;
$ffmpegVersion = '';
if (function_exists('exec')) {
    $out = [];
    @exec('ffmpeg -version 2>&1', $out, $code);
    if ($code === 0 && isset($out[0])) {
        $ffmpegOk = true;
        $ffmpegVersion = trim((string)$out[0]);
    }
}
$checks[] = ['name'=>'ffmpeg','version'=>$ffmpegOk ? $ffmpegVersion : 'Não verificável (exec desabilitado)','ok'=>$ffmpegOk,'cmd'=>'apt install ffmpeg','note'=>'Necessário para transcrição de áudio longo (>25MB). Verifique via SSH.'];

// Composer
$composerOk = false;
$composerVersion = '';
if (function_exists('exec')) {
    $out2 = [];
    @exec('composer --version 2>&1', $out2, $code2);
    if ($code2 === 0 && isset($out2[0])) {
        $composerOk = true;
        $composerVersion = trim((string)($out2[0] ?? ''));
    }
}
$checks[] = ['name'=>'Composer','version'=>$composerOk ? $composerVersion : 'Não verificável (exec desabilitado)','ok'=>$composerOk,'cmd'=>'curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer','note'=>'Gerenciador de dependências PHP. Verifique via SSH.'];

// Google API Client (opcional)
$googleOk = class_exists('Google\\Client');
$checks[] = ['name'=>'google/apiclient','version'=>$googleOk ? 'Instalado' : 'Não instalado','ok'=>$googleOk,'cmd'=>'composer require google/apiclient','note'=>'Opcional: integração Google Calendar'];

// dompdf (opcional)
$dompdfOk = class_exists('Dompdf\\Dompdf');
$checks[] = ['name'=>'dompdf/dompdf','version'=>$dompdfOk ? 'Instalado' : 'Não instalado','ok'=>$dompdfOk,'cmd'=>'composer require dompdf/dompdf','note'=>'Opcional: exportação de PDF'];

// Upload limits
$uploadMax = ini_get('upload_max_filesize') ?: '?';
$postMax = ini_get('post_max_size') ?: '?';
$checks[] = ['name'=>'upload_max_filesize','version'=>(string)$uploadMax,'ok'=>true,'cmd'=>'','note'=>'Recomendado: 100M para áudio longo'];
$checks[] = ['name'=>'post_max_size','version'=>(string)$postMax,'ok'=>true,'cmd'=>'','note'=>'Recomendado: 110M'];

$allOk = true;
foreach ($checks as $c) { if (!$c['ok']) { $allOk = false; break; } }

ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Requisitos do Servidor</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Verifique se todas as dependências estão instaladas para o sistema funcionar corretamente.</div>
    </div>
</div>

<!-- Status geral -->
<div style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid <?= $allOk ? 'rgba(22,163,74,.22)' : 'rgba(238,184,16,.30)' ?>;background:<?= $allOk ? 'rgba(22,163,74,.06)' : 'rgba(253,229,159,.12)' ?>;margin-bottom:16px;">
    <span style="font-size:16px;"><?= $allOk ? '✅' : '⚠️' ?></span>
    <span style="font-weight:700;font-size:13px;color:<?= $allOk ? '#16a34a' : 'rgba(129,89,1,1)' ?>;"><?= $allOk ? 'Tudo OK' : 'Algumas dependências precisam de atenção' ?></span>
</div>

<!-- Checklist -->
<div style="display:flex;flex-direction:column;gap:8px;margin-bottom:18px;">
    <?php foreach ($checks as $c): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;border-radius:12px;border:1px solid <?= $c['ok'] ? 'rgba(22,163,74,.14)' : 'rgba(238,184,16,.22)' ?>;background:<?= $c['ok'] ? 'rgba(22,163,74,.03)' : 'rgba(253,229,159,.06)' ?>;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                <span style="font-size:14px;"><?= $c['ok'] ? '✅' : '⚠️' ?></span>
                <div>
                    <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div style="font-size:12px;color:rgba(31,41,55,.50);"><?= htmlspecialchars($c['version'], ENT_QUOTES, 'UTF-8') ?><?= $c['note'] !== '' ? ' — ' . htmlspecialchars($c['note'], ENT_QUOTES, 'UTF-8') : '' ?></div>
                </div>
            </div>
            <?php if (!$c['ok'] && $c['cmd'] !== ''): ?>
                <code style="padding:4px 8px;border-radius:6px;background:rgba(0,0,0,.04);font-size:11px;user-select:all;word-break:break-all;"><?= htmlspecialchars($c['cmd'], ENT_QUOTES, 'UTF-8') ?></code>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<!-- Migrações do banco (rodar pelo navegador) -->
<?php
$mig = isset($migrations) && is_array($migrations) ? $migrations : ['total' => 0, 'executed' => 0, 'pending' => [], 'error' => null];
$migPending = is_array($mig['pending'] ?? null) ? $mig['pending'] : [];
$migResults = isset($migration_results) && is_array($migration_results) ? $migration_results : null;
?>
<div id="migracoes" style="padding:18px;border-radius:14px;border:1px solid <?= $migPending !== [] ? 'rgba(238,184,16,.35)' : 'rgba(22,163,74,.18)' ?>;background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:4px;">Migrações do banco</div>
    <div style="font-size:12px;color:rgba(31,41,55,.50);margin-bottom:12px;">Atualiza a estrutura do banco depois de um deploy. Faz o mesmo que o comando <code>php bin/migrate.php</code>, sem precisar de terminal.</div>

    <?php if ($migResults !== null): ?>
        <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:12px;">
            <?php if ($migResults === []): ?>
                <div class="lc-alert lc-alert--success">Nenhuma migração pendente.</div>
            <?php endif; ?>
            <?php foreach ($migResults as $r): ?>
                <div style="padding:8px 12px;border-radius:10px;font-size:12px;border:1px solid <?= $r['ok'] ? 'rgba(22,163,74,.22)' : 'rgba(185,28,28,.25)' ?>;background:<?= $r['ok'] ? 'rgba(22,163,74,.05)' : 'rgba(185,28,28,.05)' ?>;word-break:break-word;">
                    <strong><?= htmlspecialchars((string)$r['migration'], ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars((string)$r['message'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endforeach; ?>
            <?php $migFailed = array_filter($migResults, fn ($r) => !$r['ok']); ?>
            <?php if ($migFailed !== []): ?>
                <div style="font-size:12px;color:#b91c1c;">Veja a mensagem em vermelho acima. Se não souber o que fazer, envie para o desenvolvedor antes de tentar de novo.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($mig['error'])): ?>
        <div class="lc-alert lc-alert--danger">Não foi possível ler o controle de migrações: <?= htmlspecialchars((string)$mig['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php elseif ($migPending === []): ?>
        <div style="font-size:13px;font-weight:700;color:#16a34a;">Banco atualizado (<?= (int)$mig['executed'] ?> migrações executadas).</div>
    <?php else: ?>
        <style>
        .mig-list{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}
        .mig-item{display:inline-flex;align-items:center;gap:6px;padding:3px 8px;border-radius:6px;background:rgba(0,0,0,.04);font-size:12px;font-family:monospace;cursor:pointer;max-width:100%;word-break:break-all}
        .mig-item input{margin:0;flex-shrink:0}
        .mig-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
        </style>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
            <div style="font-size:13px;font-weight:700;color:rgba(129,89,1,1);"><?= count($migPending) ?> pendente(s):</div>
            <div style="font-size:12px;">
                <a href="#" onclick="document.querySelectorAll('.mig-cb').forEach(function(c){c.checked=true});return false;">Marcar todas</a> ·
                <a href="#" onclick="document.querySelectorAll('.mig-cb').forEach(function(c){c.checked=false});return false;">Desmarcar todas</a>
            </div>
        </div>
        <form method="post" action="/sys/settings/server/migrations-mark" id="migMarkForm" style="margin:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <div class="mig-list">
                <?php foreach ($migPending as $mp): ?>
                    <label class="mig-item"><input type="checkbox" class="mig-cb" name="migrations[]" value="<?= htmlspecialchars((string)$mp, ENT_QUOTES, 'UTF-8') ?>" checked /><?= htmlspecialchars((string)$mp, ENT_QUOTES, 'UTF-8') ?></label>
                <?php endforeach; ?>
            </div>
        </form>

        <div style="font-size:12px;color:rgba(31,41,55,.60);line-height:1.6;margin-bottom:12px;">
            <strong>Já rodou essas migrações direto no banco?</strong> Use “Marcar como já executadas”: o sistema só registra que estão feitas, sem rodar o SQL de novo.<br>
            Use “Executar pendentes” apenas para migrações que ainda não foram aplicadas no banco.
        </div>
        <?php if (count($migPending) > 5): ?>
            <div style="font-size:12px;color:#b91c1c;margin-bottom:10px;">Há muitas migrações pendentes, o que normalmente significa que o banco foi atualizado por fora deste controle. Nesse caso, não clique em “Executar pendentes”: use “Marcar como já executadas”.</div>
        <?php endif; ?>

        <div class="mig-actions">
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit" form="migMarkForm" onclick="var n=document.querySelectorAll('.mig-cb:checked').length; if(!n){alert('Selecione ao menos uma migração.');return false;} return confirm('Registrar ' + n + ' migração(ões) como já executada(s)? O SQL não será rodado.');">Marcar como já executadas</button>
            <form method="post" action="/sys/settings/server/migrate" style="margin:0;" onsubmit="return confirm('Executar AGORA o SQL de todas as migrações pendentes no banco?\n\nSe você já rodou essas migrações manualmente, cancele e use “Marcar como já executadas”.');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Executar pendentes</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Cron URL (fila de jobs) -->
<?php
$cronToken = trim((string)($cron_token ?? ''));
$cronBaseUrl = getenv('APP_BASE_URL') ?: ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$cronUrl = rtrim($cronBaseUrl, '/') . '/cron-queue.php?token=' . urlencode($cronToken);
?>
<div style="padding:18px;border-radius:14px;border:1px solid rgba(99,102,241,.15);background:rgba(99,102,241,.03);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:4px;">🔄 Cron — Processamento da fila</div>
    <div style="font-size:12px;color:rgba(31,41,55,.50);margin-bottom:14px;">Configure essa URL no cron do servidor (Plesk, cPanel, etc.) para processar jobs automaticamente a cada minuto.</div>

    <form method="post" action="/sys/settings/server" style="margin-bottom:14px;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;">
                <label style="font-weight:700;font-size:12px;color:rgba(31,41,55,.60);display:block;margin-bottom:4px;">Token secreto do cron</label>
                <input class="lc-input" type="text" name="cron_token" value="<?= htmlspecialchars($cronToken, ENT_QUOTES, 'UTF-8') ?>" placeholder="Gere ou digite um token secreto" />
            </div>
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Salvar</button>
            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="button" onclick="document.querySelector('[name=cron_token]').value=Array.from(crypto.getRandomValues(new Uint8Array(24)),b=>b.toString(16).padStart(2,'0')).join('');">Gerar token</button>
        </div>
    </form>

    <?php if ($cronToken !== ''): ?>
    <div style="margin-bottom:10px;">
        <label style="font-weight:700;font-size:12px;color:rgba(31,41,55,.60);display:block;margin-bottom:4px;">URL do cron (copie e configure no Plesk/cPanel)</label>
        <code style="display:block;padding:10px 12px;border-radius:8px;background:rgba(255,255,255,.70);border:1px solid rgba(17,24,39,.08);font-size:12px;user-select:all;word-break:break-all;"><?= htmlspecialchars($cronUrl, ENT_QUOTES, 'UTF-8') ?></code>
    </div>
    <div style="font-size:11px;color:rgba(31,41,55,.40);">
        Frequência recomendada: <strong>a cada 1 minuto</strong> (estilo cron: <code>* * * * *</code>). Processa até 20 jobs por fila a cada execução.
    </div>
    <?php else: ?>
    <div style="font-size:12px;color:rgba(245,158,11,.8);font-weight:600;">⚠ Configure um token secreto para gerar a URL do cron.</div>
    <?php endif; ?>
</div>

<!-- Comandos essenciais -->
<div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:12px;">Comandos para rodar no servidor (SSH)</div>
    <div style="font-size:13px;color:rgba(31,41,55,.55);margin-bottom:14px;">Copie e cole esses comandos no terminal do servidor após o deploy.</div>

    <div style="display:flex;flex-direction:column;gap:10px;">
        <div style="padding:12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.02);">
            <div style="font-weight:700;font-size:12px;color:rgba(31,41,55,.70);margin-bottom:4px;">1. Instalar dependências do sistema</div>
            <code style="display:block;padding:8px 10px;border-radius:6px;background:rgba(0,0,0,.04);font-size:12px;user-select:all;white-space:pre-wrap;">sudo apt update && sudo apt install -y ffmpeg</code>
        </div>

        <div style="padding:12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.02);">
            <div style="font-weight:700;font-size:12px;color:rgba(31,41,55,.70);margin-bottom:4px;">2. Instalar dependências PHP (Composer)</div>
            <code style="display:block;padding:8px 10px;border-radius:6px;background:rgba(0,0,0,.04);font-size:12px;user-select:all;white-space:pre-wrap;">composer install --no-dev --optimize-autoloader</code>
        </div>

        <div style="padding:12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.02);">
            <div style="font-weight:700;font-size:12px;color:rgba(31,41,55,.70);margin-bottom:4px;">3. Dependências opcionais (Google Calendar + PDF)</div>
            <code style="display:block;padding:8px 10px;border-radius:6px;background:rgba(0,0,0,.04);font-size:12px;user-select:all;white-space:pre-wrap;">composer require google/apiclient dompdf/dompdf</code>
        </div>

        <div style="padding:12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.02);">
            <div style="font-weight:700;font-size:12px;color:rgba(31,41,55,.70);margin-bottom:4px;">4. Iniciar workers da fila (manter rodando)</div>
            <code style="display:block;padding:8px 10px;border-radius:6px;background:rgba(0,0,0,.04);font-size:12px;user-select:all;white-space:pre-wrap;">php bin/queue_work.php --queue=default &
php bin/queue_work.php --queue=notifications &
php bin/queue_work.php --queue=integrations &</code>
            <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Recomendado: use Supervisor ou systemd para manter permanentemente.</div>
        </div>

        <div style="padding:12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.02);">
            <div style="font-weight:700;font-size:12px;color:rgba(31,41,55,.70);margin-bottom:4px;">5. Rodar migrations do banco de dados</div>
            <code style="display:block;padding:8px 10px;border-radius:6px;background:rgba(0,0,0,.04);font-size:12px;user-select:all;white-space:pre-wrap;">php bin/migrate.php</code>
        </div>

        <div style="padding:12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.02);">
            <div style="font-weight:700;font-size:12px;color:rgba(31,41,55,.70);margin-bottom:4px;">6. Permissões de diretórios</div>
            <code style="display:block;padding:8px 10px;border-radius:6px;background:rgba(0,0,0,.04);font-size:12px;user-select:all;white-space:pre-wrap;">chmod -R 775 storage/
chown -R www-data:www-data storage/</code>
        </div>

        <div style="padding:12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.02);">
            <div style="font-weight:700;font-size:12px;color:rgba(31,41,55,.70);margin-bottom:4px;">7. Configurar Supervisor (exemplo)</div>
            <code style="display:block;padding:8px 10px;border-radius:6px;background:rgba(0,0,0,.04);font-size:12px;user-select:all;white-space:pre-wrap;">[program:lumiclinic-queue-default]
command=php /var/www/lumiclinic/bin/queue_work.php --queue=default
autostart=true
autorestart=true
user=www-data
numprocs=1

[program:lumiclinic-queue-notifications]
command=php /var/www/lumiclinic/bin/queue_work.php --queue=notifications
autostart=true
autorestart=true
user=www-data
numprocs=1

[program:lumiclinic-queue-integrations]
command=php /var/www/lumiclinic/bin/queue_work.php --queue=integrations
autostart=true
autorestart=true
user=www-data
numprocs=1</code>
            <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Salve em /etc/supervisor/conf.d/lumiclinic.conf e rode: sudo supervisorctl reread && sudo supervisorctl update</div>
        </div>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
