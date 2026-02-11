<?php

declare(strict_types=1);

namespace App\Services\Clinics;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\ClinicClosedDaysRepository;
use App\Repositories\ClinicRepository;
use App\Repositories\ClinicWorkingHoursRepository;
use App\Services\Auth\AuthService;

final class ClinicService
{
    public function __construct(private readonly Container $container) {}

    /** @return array<string, mixed>|null */
    public function getCurrentClinic(): ?array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        if ($clinicId === null) {
            return null;
        }

        $repo = new ClinicRepository($this->container->get(\PDO::class));
        return $repo->findById($clinicId);
    }

    public function updateClinicName(string $name, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ClinicRepository($this->container->get(\PDO::class));
        $repo->updateName($clinicId, $name);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'clinics.update', ['fields' => ['name']], $ip);
    }

    public function updateTenantKey(?string $tenantKey, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $tenantKey = $tenantKey !== null ? trim($tenantKey) : null;
        if ($tenantKey === '') {
            $tenantKey = null;
        }

        $repo = new ClinicRepository($this->container->get(\PDO::class));
        $repo->updateTenantKey($clinicId, $tenantKey);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'clinics.update', ['fields' => ['tenant_key']], $ip);
    }

    /** @param array<string, string|null> $fields */
    public function updateContactFields(array $fields, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $normalized = [];
        foreach ([
            'contact_email',
            'contact_phone',
            'contact_whatsapp',
            'contact_address',
            'contact_website',
            'contact_instagram',
            'contact_facebook',
        ] as $key) {
            $value = array_key_exists($key, $fields) ? $fields[$key] : null;
            $value = $value !== null ? trim((string)$value) : null;
            if ($value === '') {
                $value = null;
            }
            $normalized[$key] = $value;
        }

        $repo = new ClinicRepository($this->container->get(\PDO::class));
        $repo->updateContactFields($clinicId, $normalized);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'clinics.update', ['fields' => array_keys($normalized)], $ip);
    }

    /** @return list<array<string, mixed>> */
    public function listWorkingHours(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ClinicWorkingHoursRepository($this->container->get(\PDO::class));
        return $repo->listByClinic($clinicId);
    }

    public function createWorkingHour(int $weekday, string $start, string $end, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ClinicWorkingHoursRepository($this->container->get(\PDO::class));
        $id = $repo->create($clinicId, $weekday, $start, $end);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'clinics.working_hours_create', ['id' => $id, 'weekday' => $weekday], $ip);
    }

    public function deleteWorkingHour(int $id, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ClinicWorkingHoursRepository($this->container->get(\PDO::class));
        $repo->softDelete($clinicId, $id);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'clinics.working_hours_delete', ['id' => $id], $ip);
    }

    /** @return list<array<string, mixed>> */
    public function listClosedDays(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ClinicClosedDaysRepository($this->container->get(\PDO::class));
        return $repo->listByClinic($clinicId);
    }

    public function createClosedDay(string $date, string $reason, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ClinicClosedDaysRepository($this->container->get(\PDO::class));
        $id = $repo->create($clinicId, $date, $reason);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'clinics.closed_days_create', ['id' => $id, 'date' => $date], $ip);
    }

    public function deleteClosedDay(int $id, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ClinicClosedDaysRepository($this->container->get(\PDO::class));
        $repo->softDelete($clinicId, $id);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'clinics.closed_days_delete', ['id' => $id], $ip);
    }
}
