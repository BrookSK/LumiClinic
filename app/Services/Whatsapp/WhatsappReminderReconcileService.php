<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use App\Core\Container\Container;
use App\Repositories\ClinicSettingsRepository;

final class WhatsappReminderReconcileService
{
    public function __construct(private readonly Container $container) {}

    public function reconcile(?int $clinicId = null): void
    {
        if ($clinicId === null) {
            return;
        }

        $pdo = $this->container->get(\PDO::class);

        $settings = (new ClinicSettingsRepository($pdo))->findByClinicId($clinicId);
        if ($settings === null) {
            return;
        }

        $repo = new \App\Repositories\AppointmentRepository($pdo);
        $start = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $end = (new \DateTimeImmutable('now'))->modify('+3 days')->format('Y-m-d H:i:s');

        $items = $repo->listByClinicRangeDetailed($clinicId, $start, $end, null);

        $scheduler = new WhatsappReminderSchedulerService($this->container);
        foreach ($items as $it) {
            $appointmentId = (int)($it['id'] ?? 0);
            if ($appointmentId <= 0) {
                continue;
            }
            $scheduler->scheduleForAppointment($clinicId, $appointmentId);
        }
    }
}
