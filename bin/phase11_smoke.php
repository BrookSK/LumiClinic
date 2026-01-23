<?php

declare(strict_types=1);

use App\Core\App;
use App\Services\Observability\ObservabilityRetentionService;
use App\Services\Observability\SystemEvent;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = App::bootstrap();
$container = $app->container();
$pdo = $container->get(PDO::class);

$args = $argv;
array_shift($args);

$flags = [
    'purge' => in_array('--purge', $args, true),
    'i_understand' => in_array('--i-understand', $args, true),
];

$testId = 'smoke-' . (string)time();

SystemEvent::dispatch($container, 'smoke.phase11', [
    'test_id' => $testId,
    'password' => 'super-secret',
    'token' => 'abc123',
    'nested' => [
        'authorization' => 'Bearer xyz',
    ],
]);

$stmt = $pdo->prepare("SELECT payload_json FROM event_logs WHERE event = 'smoke.phase11' ORDER BY id DESC LIMIT 1");
$stmt->execute();
$row = $stmt->fetch();

if (!$row || !isset($row['payload_json'])) {
    fwrite(STDERR, "FAIL: event_logs row not found\n");
    exit(1);
}

$payloadJson = (string)$row['payload_json'];

if (!str_contains($payloadJson, '[REDACTED]')) {
    fwrite(STDERR, "FAIL: expected [REDACTED] in payload_json\n");
    exit(1);
}

if (str_contains($payloadJson, 'super-secret') || str_contains($payloadJson, 'Bearer xyz') || str_contains($payloadJson, 'abc123')) {
    fwrite(STDERR, "FAIL: sensitive values leaked in payload_json\n");
    exit(1);
}

fwrite(STDOUT, "OK: SystemEvent masking\n");

if ($flags['purge']) {
    if (!$flags['i_understand']) {
        fwrite(STDERR, "Refusing to run purge without --i-understand\n");
        exit(2);
    }

    $ret = new ObservabilityRetentionService($container);
    $counts = $ret->purge();
    fwrite(STDOUT, "OK: purge executed. deleted event_logs={$counts['event_logs']} performance_logs={$counts['performance_logs']} system_metrics={$counts['system_metrics']}\n");
}
