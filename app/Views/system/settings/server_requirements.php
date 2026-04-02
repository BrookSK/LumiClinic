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
$out = [];
@exec('ffmpeg -version 2>&1', $out, $code);
if ($code === 0 && isset($out[0])) {
    $ffmpegOk = true;
    $ffmpegVersion = trim((string)$out[0]);
}
$checks[] = ['name'=>'ffmpeg','version'=>$ffmpegOk ? $ffmpegVersion : 'Não instalado','ok'=>$ffmpegOk,'cmd'=>'apt install ffmpeg','note'=>'Necessário para transcrição de áudio longo (>25MB)'];

// Composer
$composerOk = false;
$out2 = [];
@exec('composer --version 2>&1', $out2, $code2);
if ($code2 === 0 && isset($out2[0])) $composerOk = true;
$checks[] = ['name'=>'Composer','version'=>$composerOk ? trim((string)($out2[0] ?? '')) : 'Não encontrado','ok'=>$composerOk,'cmd'=>'curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer','note'=>'Gerenciador de dependências PHP'];

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
