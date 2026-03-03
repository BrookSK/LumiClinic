<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use App\Core\Container\Container;
use App\Repositories\DataExportRepository;
use App\Services\Auth\AuthService;

final class DataExportService
{
    public function __construct(private readonly Container $container) {}

    /** @param array<string, mixed> $meta */
    public function record(
        string $action,
        ?string $entityType,
        ?int $entityId,
        ?string $format,
        ?string $filename,
        array $meta,
        string $ip,
        ?string $userAgent = null
    ): int {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        return (new DataExportRepository($pdo))->create(
            $clinicId,
            $actorId,
            $action,
            $entityType,
            $entityId,
            $format,
            $filename,
            $meta,
            $ip,
            $userAgent
        );
    }
}
