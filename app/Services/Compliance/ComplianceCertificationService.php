<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\ComplianceControlRepository;
use App\Repositories\CompliancePolicyRepository;
use App\Services\Auth\AuthService;

final class ComplianceCertificationService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{policies:list<array<string,mixed>>,controls:list<array<string,mixed>>} */
    public function dashboard(string $ip, ?string $userAgent = null): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $policies = (new CompliancePolicyRepository($pdo))->listByClinic($clinicId, 200);
        $controls = (new ComplianceControlRepository($pdo))->listByClinic($clinicId, 500);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'compliance.policies.view', [], $ip, $roleCodes, null, null, $userAgent);

        return ['policies' => $policies, 'controls' => $controls];
    }

    public function createPolicy(
        string $code,
        string $title,
        ?string $description,
        string $status,
        int $version,
        ?int $ownerUserId,
        ?string $reviewedAt,
        ?string $nextReviewAt,
        string $ip,
        ?string $userAgent = null
    ): int {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $code = strtolower(trim($code));
        if ($code === '') {
            throw new \RuntimeException('Código é obrigatório.');
        }

        $title = trim($title);
        if ($title === '') {
            throw new \RuntimeException('Título é obrigatório.');
        }

        $status = strtolower(trim($status));
        if (!in_array($status, ['draft', 'active', 'retired'], true)) {
            $status = 'draft';
        }

        $version = max(1, $version);

        $pdo = $this->container->get(\PDO::class);
        $repo = new CompliancePolicyRepository($pdo);
        $id = $repo->create($clinicId, $code, $title, $description, $status, $version, $ownerUserId, $reviewedAt, $nextReviewAt);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'compliance.policies.create', ['policy_id' => $id], $ip, $roleCodes, 'compliance_policy', $id, $userAgent);

        return $id;
    }

    public function updatePolicy(
        int $id,
        string $title,
        ?string $description,
        string $status,
        int $version,
        ?int $ownerUserId,
        ?string $reviewedAt,
        ?string $nextReviewAt,
        string $ip,
        ?string $userAgent = null
    ): void {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($id <= 0) {
            throw new \RuntimeException('Política inválida.');
        }

        $title = trim($title);
        if ($title === '') {
            throw new \RuntimeException('Título é obrigatório.');
        }

        $status = strtolower(trim($status));
        if (!in_array($status, ['draft', 'active', 'retired'], true)) {
            throw new \RuntimeException('Status inválido.');
        }

        $version = max(1, $version);

        $pdo = $this->container->get(\PDO::class);
        $repo = new CompliancePolicyRepository($pdo);
        $current = $repo->findById($clinicId, $id);
        if ($current === null) {
            throw new \RuntimeException('Política inválida.');
        }

        $repo->update($clinicId, $id, $title, $description, $status, $version, $ownerUserId, $reviewedAt, $nextReviewAt);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'compliance.policies.update', ['policy_id' => $id, 'status' => $status, 'version' => $version], $ip, $roleCodes, 'compliance_policy', $id, $userAgent);
    }

    public function createControl(
        ?int $policyId,
        string $code,
        string $title,
        ?string $description,
        string $status,
        ?int $ownerUserId,
        ?string $evidenceUrl,
        ?string $lastTestedAt,
        string $ip,
        ?string $userAgent = null
    ): int {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $code = strtolower(trim($code));
        if ($code === '') {
            throw new \RuntimeException('Código é obrigatório.');
        }

        $title = trim($title);
        if ($title === '') {
            throw new \RuntimeException('Título é obrigatório.');
        }

        $status = strtolower(trim($status));
        if (!in_array($status, ['planned', 'implemented', 'tested', 'failed'], true)) {
            $status = 'planned';
        }

        $pdo = $this->container->get(\PDO::class);

        if ($policyId !== null && $policyId > 0) {
            $policyRepo = new CompliancePolicyRepository($pdo);
            $p = $policyRepo->findById($clinicId, $policyId);
            if ($p === null) {
                $policyId = null;
            }
        } else {
            $policyId = null;
        }

        $repo = new ComplianceControlRepository($pdo);
        $id = $repo->create($clinicId, $policyId, $code, $title, $description, $status, $ownerUserId, $evidenceUrl, $lastTestedAt);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'compliance.controls.create', ['control_id' => $id, 'policy_id' => $policyId], $ip, $roleCodes, 'compliance_control', $id, $userAgent);

        return $id;
    }

    public function updateControl(
        int $id,
        string $title,
        ?string $description,
        string $status,
        ?int $ownerUserId,
        ?string $evidenceUrl,
        ?string $lastTestedAt,
        ?int $policyId,
        string $ip,
        ?string $userAgent = null
    ): void {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($id <= 0) {
            throw new \RuntimeException('Controle inválido.');
        }

        $title = trim($title);
        if ($title === '') {
            throw new \RuntimeException('Título é obrigatório.');
        }

        $status = strtolower(trim($status));
        if (!in_array($status, ['planned', 'implemented', 'tested', 'failed'], true)) {
            throw new \RuntimeException('Status inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new ComplianceControlRepository($pdo);
        $current = $repo->findById($clinicId, $id);
        if ($current === null) {
            throw new \RuntimeException('Controle inválido.');
        }

        if ($policyId !== null && $policyId > 0) {
            $policyRepo = new CompliancePolicyRepository($pdo);
            $p = $policyRepo->findById($clinicId, $policyId);
            if ($p === null) {
                $policyId = null;
            }
        } else {
            $policyId = null;
        }

        $repo->update($clinicId, $id, $title, $description, $status, $ownerUserId, $evidenceUrl, $lastTestedAt, $policyId);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'compliance.controls.update', ['control_id' => $id, 'status' => $status, 'policy_id' => $policyId], $ip, $roleCodes, 'compliance_control', $id, $userAgent);
    }
}
