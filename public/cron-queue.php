<?php
/**
 * HTTP endpoint para processar a fila de jobs via cron/URL.
 * Protegido por token secreto configurado pelo superadmin.
 *
 * URL: https://seudominio.com.br/cron-queue.php?token=SEU_TOKEN
 */

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Repositories\QueueJobRepository;
use App\Services\Queue\QueueService;
use App\Services\System\SystemSettingsService;

$app = App::bootstrap();
$container = $app->container();

// Validate token from system settings
$settings = new SystemSettingsService($container);
$expectedToken = trim((string)($settings->getText('cron.secret_token') ?? ''));

if ($expectedToken === '') {
    http_response_code(503);
    echo json_encode(['error' => 'Cron token not configured. Set it in Admin > Server.']);
    exit;
}

$providedToken = $_GET['token'] ?? '';
if (!hash_equals($expectedToken, (string)$providedToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid token.']);
    exit;
}

$pdo = $container->get(PDO::class);
$repo = new QueueJobRepository($pdo);
$svc = new QueueService($container);

$workerId = 'cron-http:' . gethostname() . ':' . getmypid();
$maxJobsPerQueue = 20;
$lockSeconds = 120;
$queues = ['default', 'notifications', 'integrations'];

$totalProcessed = 0;
$totalFailed = 0;
$details = [];

foreach ($queues as $queue) {
    $processed = 0;
    $failed = 0;

    while ($processed + $failed < $maxJobsPerQueue) {
        $job = $repo->reserveNext($queue, $workerId, $lockSeconds);
        if ($job === null) break;

        $jobId = (int)$job['id'];
        $clinicId = $job['clinic_id'] === null ? null : (int)$job['clinic_id'];
        $jobType = (string)$job['job_type'];

        $payload = [];
        $raw = (string)($job['payload_json'] ?? '');
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $payload = $decoded;
        }

        try {
            $svc->handle($jobType, $payload, $clinicId);
            $repo->ack($jobId);
            $processed++;
        } catch (\Throwable $e) {
            if ($repo->shouldDeadLetter($job)) {
                $repo->markDead($jobId, $e->getMessage());
            } else {
                $repo->release($jobId, 30, $e->getMessage());
            }
            $failed++;
        }
    }

    $totalProcessed += $processed;
    $totalFailed += $failed;
    if ($processed > 0 || $failed > 0) {
        $details[] = "$queue: {$processed} ok, {$failed} fail";
    }
}

header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'processed' => $totalProcessed,
    'failed' => $totalFailed,
    'details' => $details,
    'time' => date('Y-m-d H:i:s'),
]);
