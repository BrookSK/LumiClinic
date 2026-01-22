<?php

declare(strict_types=1);

namespace App\Services\Scheduling;

use App\Core\Container\Container;
use App\Repositories\AppointmentRepository;
use App\Repositories\ClinicWorkingHoursRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\ProfessionalScheduleRepository;
use App\Repositories\SchedulingBlockRepository;
use App\Repositories\ServiceCatalogRepository;
use App\Services\Auth\AuthService;

final class AvailabilityService
{
    public function __construct(private readonly Container $container) {}

    /**
     * @return list<array{start_at:string,end_at:string}>
     */
    public function listAvailableSlots(
        int $serviceId,
        string $dateYmd,
        ?int $professionalId = null,
        ?int $intervalMinutesOverride = null,
        ?int $excludeAppointmentId = null
    ): array {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd);
        if ($date === false) {
            throw new \RuntimeException('Data inválida.');
        }

        $weekday = (int)$date->format('w');

        $serviceRepo = new ServiceCatalogRepository($this->container->get(\PDO::class));
        $service = $serviceRepo->findById($clinicId, $serviceId);
        if ($service === null) {
            throw new \RuntimeException('Serviço inválido.');
        }

        $durationMinutes = (int)$service['duration_minutes'];
        if ($durationMinutes <= 0) {
            throw new \RuntimeException('Serviço inválido.');
        }

        $clinicWhRepo = new ClinicWorkingHoursRepository($this->container->get(\PDO::class));
        $clinicWh = $clinicWhRepo->listByClinic($clinicId);

        $clinicWindows = [];
        foreach ($clinicWh as $row) {
            if ((int)$row['weekday'] === $weekday) {
                $clinicWindows[] = [
                    'start' => (string)$row['start_time'],
                    'end' => (string)$row['end_time'],
                ];
            }
        }

        if ($clinicWindows === []) {
            return [];
        }

        $scheduleRepo = new ProfessionalScheduleRepository($this->container->get(\PDO::class));
        $profRepo = new ProfessionalRepository($this->container->get(\PDO::class));

        $professionalIds = [];
        if ($professionalId !== null) {
            $p = $profRepo->findById($clinicId, $professionalId);
            if ($p === null) {
                throw new \RuntimeException('Profissional inválido.');
            }
            $professionalIds = [$professionalId];
        } else {
            $list = $profRepo->listActiveByClinic($clinicId);
            foreach ($list as $p) {
                $professionalIds[] = (int)$p['id'];
            }
        }

        if ($professionalIds === []) {
            return [];
        }

        $blockRepo = new SchedulingBlockRepository($this->container->get(\PDO::class));
        $apptRepo = new AppointmentRepository($this->container->get(\PDO::class));

        $slots = [];
        foreach ($professionalIds as $pid) {
            $dayStart = $dateYmd . ' 00:00:00';
            $dayEnd = $dateYmd . ' 23:59:59';

            $schedules = $scheduleRepo->listByProfessional($clinicId, $pid);
            $profWindows = [];
            foreach ($schedules as $s) {
                if ((int)$s['weekday'] !== $weekday) {
                    continue;
                }
                $profWindows[] = [
                    'start' => (string)$s['start_time'],
                    'end' => (string)$s['end_time'],
                    'interval' => $intervalMinutesOverride !== null ? $intervalMinutesOverride : (isset($s['interval_minutes']) ? (int)$s['interval_minutes'] : null),
                ];
            }

            if ($profWindows === []) {
                continue;
            }

            $blocks = $blockRepo->listOverlapping($clinicId, $pid, $dayStart, $dayEnd);

            foreach ($clinicWindows as $cw) {
                foreach ($profWindows as $pw) {
                    $windowStart = $this->maxTime($cw['start'], $pw['start']);
                    $windowEnd = $this->minTime($cw['end'], $pw['end']);

                    if ($windowStart === null || $windowEnd === null) {
                        continue;
                    }

                    $intervalMinutes = $pw['interval'] ?? 0;
                    $stepMinutes = max(5, (int)$intervalMinutes);

                    $cursor = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateYmd . ' ' . $windowStart . ':00');
                    $endLimit = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateYmd . ' ' . $windowEnd . ':00');
                    if ($cursor === false || $endLimit === false) {
                        continue;
                    }

                    while (true) {
                        $slotStart = $cursor;
                        $slotEnd = $slotStart->modify('+' . $durationMinutes . ' minutes');

                        if ($slotEnd > $endLimit) {
                            break;
                        }

                        $startAt = $slotStart->format('Y-m-d H:i:s');
                        $endAt = $slotEnd->format('Y-m-d H:i:s');

                        if ($this->overlapsAny($startAt, $endAt, $blocks)) {
                            $cursor = $cursor->modify('+' . $stepMinutes . ' minutes');
                            continue;
                        }

                        if ($excludeAppointmentId !== null) {
                            $conflicts = $apptRepo->listOverlappingExcludingAppointment($clinicId, $pid, $startAt, $endAt, $excludeAppointmentId);
                        } else {
                            $conflicts = $apptRepo->listOverlapping($clinicId, $pid, $startAt, $endAt);
                        }
                        if ($conflicts !== []) {
                            $cursor = $cursor->modify('+' . $stepMinutes . ' minutes');
                            continue;
                        }

                        $slots[] = ['start_at' => $startAt, 'end_at' => $endAt, 'professional_id' => $pid];

                        $cursor = $cursor->modify('+' . $stepMinutes . ' minutes');
                    }
                }
            }
        }

        usort($slots, fn ($a, $b) => strcmp($a['start_at'], $b['start_at']));

        $out = [];
        foreach ($slots as $s) {
            $out[] = ['start_at' => $s['start_at'], 'end_at' => $s['end_at']];
        }

        return $out;
    }

    private function overlapsAny(string $startAt, string $endAt, array $ranges): bool
    {
        foreach ($ranges as $r) {
            $rStart = (string)$r['start_at'];
            $rEnd = (string)$r['end_at'];
            if ($rStart < $endAt && $rEnd > $startAt) {
                return true;
            }
        }
        return false;
    }

    private function maxTime(string $a, string $b): ?string
    {
        return ($a >= $b) ? $a : $b;
    }

    private function minTime(string $a, string $b): ?string
    {
        return ($a <= $b) ? $a : $b;
    }
}
