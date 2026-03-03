<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Services\Auth\AuthService;

final class SensitiveDataAuditService
{
    public function __construct(private readonly Container $container) {}

    /** @param array<string, mixed> $meta */
    public function access(string $action, ?string $entityType, ?int $entityId, array $meta, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        $pdo = $this->container->get(\PDO::class);
        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;

        $audit->log(
            $actorId,
            $clinicId,
            $action,
            $meta,
            $ip,
            $roleCodes,
            $entityType,
            $entityId,
            $userAgent
        );
    }
}
