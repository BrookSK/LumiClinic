<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\ClinicSettingsRepository;
use App\Repositories\ClinicTerminologyRepository;
use App\Services\Auth\AuthService;

final class SettingsService
{
    public function __construct(private readonly Container $container) {}

    /** @return array<string, mixed>|null */
    public function getSettings(): ?array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        if ($clinicId === null) {
            return null;
        }

        $repo = new ClinicSettingsRepository($this->container->get(\PDO::class));
        return $repo->findByClinicId($clinicId);
    }

    public function updateSettings(string $timezone, string $language, ?int $weekStartWeekday, ?int $weekEndWeekday, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ClinicSettingsRepository($this->container->get(\PDO::class));
        $repo->update($clinicId, $timezone, $language, $weekStartWeekday, $weekEndWeekday);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'settings.update', ['fields' => ['timezone', 'language', 'week_start_weekday', 'week_end_weekday']], $ip);
    }

    /** @return array<string, mixed>|null */
    public function getTerminology(): ?array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        if ($clinicId === null) {
            return null;
        }

        $repo = new ClinicTerminologyRepository($this->container->get(\PDO::class));
        return $repo->findByClinicId($clinicId);
    }

    public function updateTerminology(string $patient, string $appointment, string $professional, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ClinicTerminologyRepository($this->container->get(\PDO::class));
        $repo->update($clinicId, $patient, $appointment, $professional);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'settings.terminology_update', ['fields' => ['patient_label', 'appointment_label', 'professional_label']], $ip);
    }
}
