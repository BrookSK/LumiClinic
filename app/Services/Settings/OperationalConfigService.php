<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\ClinicFunnelStageRepository;
use App\Repositories\ClinicLostReasonRepository;
use App\Repositories\ClinicPatientOriginRepository;
use App\Services\Auth\AuthService;

final class OperationalConfigService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{stages:list<array<string,mixed>>,lost_reasons:list<array<string,mixed>>,origins:list<array<string,mixed>>} */
    public function listAll(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        return [
            'stages' => (new ClinicFunnelStageRepository($pdo))->listAllByClinic($clinicId, 500),
            'lost_reasons' => (new ClinicLostReasonRepository($pdo))->listAllByClinic($clinicId, 500),
            'origins' => (new ClinicPatientOriginRepository($pdo))->listAllByClinic($clinicId, 500),
        ];
    }

    /** @return list<array<string,mixed>> */
    public function listActiveFunnelStages(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        return (new ClinicFunnelStageRepository($this->container->get(\PDO::class)))->listActiveByClinic($clinicId, 500);
    }

    /** @return list<array<string,mixed>> */
    public function listActiveLostReasons(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        return (new ClinicLostReasonRepository($this->container->get(\PDO::class)))->listActiveByClinic($clinicId, 500);
    }

    /** @return list<array<string,mixed>> */
    public function listActivePatientOrigins(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        return (new ClinicPatientOriginRepository($this->container->get(\PDO::class)))->listActiveByClinic($clinicId, 500);
    }

    public function createFunnelStage(string $name, int $sortOrder, string $ip): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $name = trim($name);
        if ($name === '') {
            throw new \RuntimeException('Nome obrigatório.');
        }
        if (mb_strlen($name) > 80) {
            throw new \RuntimeException('Nome muito longo.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new ClinicFunnelStageRepository($pdo);
        if ($repo->existsActiveByClinicAndName($clinicId, $name)) {
            throw new \RuntimeException('Etapa já existe.');
        }

        $id = $repo->create($clinicId, $name, $sortOrder);
        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'settings.operational.funnel_stages.create', ['id' => $id, 'name' => $name], $ip);
        return $id;
    }

    public function deleteFunnelStage(int $id, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($id <= 0) {
            return;
        }

        $pdo = $this->container->get(\PDO::class);
        (new ClinicFunnelStageRepository($pdo))->softDelete($clinicId, $id);
        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'settings.operational.funnel_stages.delete', ['id' => $id], $ip);
    }

    public function createLostReason(string $name, int $sortOrder, string $ip): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $name = trim($name);
        if ($name === '') {
            throw new \RuntimeException('Nome obrigatório.');
        }
        if (mb_strlen($name) > 100) {
            throw new \RuntimeException('Nome muito longo.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new ClinicLostReasonRepository($pdo);
        if ($repo->existsActiveByClinicAndName($clinicId, $name)) {
            throw new \RuntimeException('Motivo já existe.');
        }

        $id = $repo->create($clinicId, $name, $sortOrder);
        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'settings.operational.lost_reasons.create', ['id' => $id, 'name' => $name], $ip);
        return $id;
    }

    public function deleteLostReason(int $id, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($id <= 0) {
            return;
        }

        $pdo = $this->container->get(\PDO::class);
        (new ClinicLostReasonRepository($pdo))->softDelete($clinicId, $id);
        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'settings.operational.lost_reasons.delete', ['id' => $id], $ip);
    }

    public function createPatientOrigin(string $name, int $sortOrder, string $ip): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $name = trim($name);
        if ($name === '') {
            throw new \RuntimeException('Nome obrigatório.');
        }
        if (mb_strlen($name) > 100) {
            throw new \RuntimeException('Nome muito longo.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new ClinicPatientOriginRepository($pdo);
        if ($repo->existsActiveByClinicAndName($clinicId, $name)) {
            throw new \RuntimeException('Origem já existe.');
        }

        $id = $repo->create($clinicId, $name, $sortOrder);
        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'settings.operational.patient_origins.create', ['id' => $id, 'name' => $name], $ip);
        return $id;
    }

    public function deletePatientOrigin(int $id, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($id <= 0) {
            return;
        }

        $pdo = $this->container->get(\PDO::class);
        (new ClinicPatientOriginRepository($pdo))->softDelete($clinicId, $id);
        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'settings.operational.patient_origins.delete', ['id' => $id], $ip);
    }
}
