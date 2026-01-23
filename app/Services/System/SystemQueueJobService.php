<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Core\Container\Container;
use App\Repositories\QueueJobRepository;
use App\Services\Queue\QueueService;

final class SystemQueueJobService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string, mixed>> */
    public function listJobs(?string $status = null): array
    {
        $repo = new QueueJobRepository($this->container->get(\PDO::class));
        return $repo->listLatest($status, 300);
    }

    public function retryDead(int $jobId): void
    {
        $repo = new QueueJobRepository($this->container->get(\PDO::class));
        $repo->retryDead($jobId);
    }

    public function enqueueTest(string $jobType): int
    {
        $jobType = trim($jobType);
        if (!in_array($jobType, ['test.noop', 'test.throw'], true)) {
            throw new \RuntimeException('Job de teste inválido.');
        }

        return (new QueueService($this->container))->enqueue(
            $jobType,
            [],
            null,
            'default'
        );
    }
}
