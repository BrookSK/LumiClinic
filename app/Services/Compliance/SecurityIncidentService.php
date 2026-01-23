<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\SecurityIncidentRepository;
use App\Services\Auth\AuthService;

final class SecurityIncidentService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function list(string $ip, ?string $userAgent = null): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new SecurityIncidentRepository($pdo);
        $items = $repo->listByClinic($clinicId, 200);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'compliance.incidents.view', [], $ip, $roleCodes, null, null, $userAgent);

        return $items;
    }

    public function create(string $severity, string $title, ?string $description, string $ip, ?string $userAgent = null): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $severity = strtolower(trim($severity));
        if (!in_array($severity, ['low', 'medium', 'high', 'critical'], true)) {
            $severity = 'medium';
        }

        $title = trim($title);
        if ($title === '') {
            throw new \RuntimeException('Título é obrigatório.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new SecurityIncidentRepository($pdo);
        $id = $repo->create($clinicId, $severity, $title, $description, $actorId);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'compliance.incidents.create', ['incident_id' => $id], $ip, $roleCodes, 'security_incident', $id, $userAgent);

        return $id;
    }

    public function update(int $id, string $status, ?int $assignedToUserId, ?string $correctiveAction, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $status = strtolower(trim($status));
        if (!in_array($status, ['open', 'investigating', 'contained', 'resolved'], true)) {
            throw new \RuntimeException('Status inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new SecurityIncidentRepository($pdo);
        $current = $repo->findById($clinicId, $id);
        if ($current === null) {
            throw new \RuntimeException('Incidente inválido.');
        }

        $repo->updateStatus($clinicId, $id, $status, $assignedToUserId, $correctiveAction);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'compliance.incidents.update', ['incident_id' => $id, 'status' => $status], $ip, $roleCodes, 'security_incident', $id, $userAgent);
    }
}
