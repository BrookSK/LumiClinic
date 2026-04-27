<?php
/**
 * Queue worker para execução via cron.
 * Processa até N jobs de cada fila e sai.
 * 
 * Uso no crontab (a cada minuto):
 *   * * * * * php /var/www/lumiclinic/bin/queue_cron.php >> /var/log/lumiclinic-queue.log 2>&1
 */

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

use App\Core\App;
use App\Repositories\QueueJobRepository;
use App\Services\Queue\QueueService;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = App::bootstrap();
$container = $app->container();

$pdo = $container->get(PDO::class);
$repo = new QueueJobRepository($pdo);
$svc = new QueueService($container);

$workerId = 'cron:' . gethostname() . ':' . getmypid();
$maxJobsPerQueue = 20;
$lockSeconds = 120;

$queues = ['default', 'notifications', 'integrations'];

$totalProcessed = 0;
$totalFailed = 0;

foreach ($queues as $queue) {
    $processed = 0;

    while ($processed < $maxJobsPerQueue) {
        $job = $repo->reserveNext($queue, $workerId, $lockSeconds);

        if ($job === null) {
            break; // No more jobs in this queue
        }

        $jobId = (int)$job['id'];
        $clinicId = $job['clinic_id'] === null ? null : (int)$job['clinic_id'];
        $jobType = (string)$job['job_type'];

        $payload = [];
        $raw = (string)($job['payload_json'] ?? '');
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        try {
            $svc->handle($jobType, $payload, $clinicId);
            $repo->markCompleted($jobId);
            $processed++;
            $totalProcessed++;
        } catch (\Throwable $e) {
            $repo->markFailed($jobId, $e->getMessage());
            $totalFailed++;
            $processed++;
        }
    }
}

if ($totalProcessed > 0 || $totalFailed > 0) {
    echo date('Y-m-d H:i:s') . " Queue cron: {$totalProcessed} processed, {$totalFailed} failed\n";
}
