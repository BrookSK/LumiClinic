<?php

declare(strict_types=1);

use App\Core\App;
use App\Repositories\QueueJobRepository;
use App\Services\Queue\QueueService;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = App::bootstrap();
$container = $app->container();

$args = $argv;
array_shift($args);

$options = [
    'queue' => 'default',
    'sleep' => 2,
    'once' => false,
    'lock_seconds' => 60,
    'max_jobs' => 0,
];

foreach ($args as $a) {
    if ($a === '--once') {
        $options['once'] = true;
        continue;
    }
    if (str_starts_with($a, '--queue=')) {
        $options['queue'] = (string)substr($a, 8);
        continue;
    }
    if (str_starts_with($a, '--sleep=')) {
        $options['sleep'] = (int)substr($a, 8);
        continue;
    }
    if (str_starts_with($a, '--lock-seconds=')) {
        $options['lock_seconds'] = (int)substr($a, 15);
        continue;
    }
    if (str_starts_with($a, '--max-jobs=')) {
        $options['max_jobs'] = (int)substr($a, 11);
        continue;
    }
}

$workerId = gethostname() . ':' . getmypid();
$pdo = $container->get(PDO::class);
$repo = new QueueJobRepository($pdo);
$svc = new QueueService($container);

$processed = 0;

while (true) {
    $job = $repo->reserveNext((string)$options['queue'], $workerId, (int)$options['lock_seconds']);

    if ($job === null) {
        if ($options['once']) {
            exit(0);
        }
        sleep(max(0, (int)$options['sleep']));
        continue;
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
        $repo->ack($jobId);
    } catch (Throwable $e) {
        $msg = substr($e->getMessage(), 0, 65000);
        if ($repo->shouldDeadLetter($job)) {
            $repo->markDead($jobId, $msg);
        } else {
            $attempts = (int)($job['attempts'] ?? 0);
            $delay = min(300, (int)pow(2, max(0, $attempts)));
            $repo->release($jobId, $delay, $msg);
        }
    }

    $processed++;
    if ($options['max_jobs'] > 0 && $processed >= (int)$options['max_jobs']) {
        exit(0);
    }

    if ($options['once']) {
        exit(0);
    }
}
