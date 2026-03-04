<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use App\Core\Container\Container;
use App\Repositories\AppointmentRepository;
use App\Repositories\WhatsappMessageLogRepository;
use App\Services\Queue\QueueService;

final class WhatsappReminderSchedulerService
{
    public function __construct(private readonly Container $container) {}

    public function scheduleForAppointment(int $clinicId, int $appointmentId): void
    {
        $pdo = $this->container->get(\PDO::class);
        $apptRepo = new AppointmentRepository($pdo);
        $ctx = $apptRepo->findReminderContext($clinicId, $appointmentId);
        if ($ctx === null) {
            return;
        }

        $status = (string)($ctx['status'] ?? '');
        if (in_array($status, ['cancelled', 'no_show', 'completed'], true)) {
            (new WhatsappMessageLogRepository($pdo))->cancelPendingForAppointment($clinicId, $appointmentId);
            return;
        }

        $patientId = isset($ctx['patient_id']) ? (int)$ctx['patient_id'] : null;
        $startAt = (string)($ctx['start_at'] ?? '');
        if ($startAt === '') {
            return;
        }

        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startAt);
        if ($start === false) {
            return;
        }

        $now = new \DateTimeImmutable('now');

        $candidates = [
            ['code' => 'confirm_request', 'when' => $start->modify('-24 hours')],
            ['code' => 'reminder_24h', 'when' => $start->modify('-24 hours')],
            ['code' => 'reminder_2h', 'when' => $start->modify('-2 hours')],
        ];

        $logsRepo = new WhatsappMessageLogRepository($pdo);
        $queue = new QueueService($this->container);

        foreach ($candidates as $cand) {
            $code = (string)$cand['code'];
            /** @var \DateTimeImmutable $when */
            $when = $cand['when'];

            if ($when <= $now) {
                continue;
            }

            $scheduledFor = $when->format('Y-m-d H:i:s');
            $logId = $logsRepo->createOrUpdatePending($clinicId, $appointmentId, $patientId, $code, $scheduledFor);

            $log = $logsRepo->findById($clinicId, $logId);
            $logStatus = $log !== null ? (string)($log['status'] ?? 'pending') : 'pending';
            if ($logStatus !== 'pending') {
                continue;
            }

            $queue->enqueue(
                'whatsapp.send_reminder',
                ['appointment_id' => $appointmentId, 'template_code' => $code, 'log_id' => $logId],
                $clinicId,
                'notifications',
                $scheduledFor,
                10
            );
        }
    }
}
