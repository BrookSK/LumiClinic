<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Core\Container\Container;
use App\Repositories\AuditLogQueryRepository;
use App\Services\Auth\AuthService;

final class AuditLogService
{
    public function __construct(private readonly Container $container) {}

    /** @param array{action:string,from:string,to:string} $filters */
    /** @return list<array<string, mixed>> */
    public function list(array $filters, int $limit = 250, int $offset = 0): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new AuditLogQueryRepository($this->container->get(\PDO::class));
        $limit = max(1, min($limit, 5000));
        $offset = max(0, $offset);
        return $repo->search($clinicId, $filters, $limit, $offset);
    }
}
