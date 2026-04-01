<?php

declare(strict_types=1);

namespace App\Controllers\Manager;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\AppointmentRepository;
use App\Repositories\ClinicClosedDaysRepository;
use App\Repositories\ClinicWorkingHoursRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\SchedulingBlockRepository;
use App\Services\Auth\AuthService;
use App\Services\Scheduling\ScheduleIndicatorsService;

final class ManagerPanelController extends Controller
{
    public function index(Request $request): Response
    {
        // Painel oculto — acesso restrito temporariamente
        if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
            return $this->redirect('/');
        }

        $this->authorize('scheduling.read');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/');
        }

        $mode = trim((string)$request->input('mode', 'week'));
        if (!in_array($mode, ['day', 'week', 'range'], true)) {
            $mode = 'week';
        }

        $date = trim((string)$request->input('date', date('Y-m-d')));
        $startDate = trim((string)$request->input('start_date', ''));
        $endDate = trim((string)$request->input('end_date', ''));

        $professionalId = (int)$request->input('professional_id', 0);
        if ($professionalId <= 0) {
            $professionalId = 0;
        }

        $range = $this->resolveRange($mode, $date, $startDate, $endDate, $clinicId);

        $pdo = $this->container->get(\PDO::class);

        $apptRepo = new AppointmentRepository($pdo);
        $items = $apptRepo->listByClinicRangeDetailed(
            $clinicId,
            $range['start_at'],
            $range['end_at'],
            $professionalId > 0 ? $professionalId : null
        );

        $blocks = (new SchedulingBlockRepository($pdo))->listByClinicRange($clinicId, $range['start_at'], $range['end_at']);
        $workingHours = (new ClinicWorkingHoursRepository($pdo))->listByClinic($clinicId);
        $closedDays = (new ClinicClosedDaysRepository($pdo))->listByClinic($clinicId);

        $profRepo = new ProfessionalRepository($pdo);
        $professionals = $profRepo->listActiveByClinic($clinicId);

        $ind = new ScheduleIndicatorsService();
        $whByWeekday = $ind->workingHoursByWeekday($workingHours);
        $closedMap = $ind->closedDaysMap($closedDays);

        $metrics = $this->buildMetrics($range, $items, $blocks, $whByWeekday, $closedMap);

        $forecastDays = 14;
        $forecast = $this->buildForecast($clinicId, $professionalId > 0 ? $professionalId : null, $forecastDays, $whByWeekday, $closedMap);

        return $this->view('manager/panel', [
            'title' => 'Painel Gestor',
            'mode' => $mode,
            'date' => $date,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'professional_id' => $professionalId,
            'professionals' => $professionals,
            'range' => $range,
            'metrics' => $metrics,
            'forecast' => $forecast,
        ]);
    }

    /** @return array{start_at:string,end_at:string,label:string,days:list<string>} */
    private function resolveRange(string $mode, string $date, string $startDate, string $endDate, int $clinicId): array
    {
        $settingsRepo = new \App\Repositories\ClinicSettingsRepository($this->container->get(\PDO::class));
        $settings = $settingsRepo->findByClinicId($clinicId);
        $weekStartWeekday = isset($settings['week_start_weekday']) ? (int)$settings['week_start_weekday'] : 1;
        if ($weekStartWeekday < 0 || $weekStartWeekday > 6) {
            $weekStartWeekday = 1;
        }

        if ($mode === 'range') {
            $sd = \DateTimeImmutable::createFromFormat('Y-m-d', $startDate);
            $ed = \DateTimeImmutable::createFromFormat('Y-m-d', $endDate);
            if ($sd === false || $ed === false || $ed < $sd) {
                $sd = new \DateTimeImmutable(date('Y-m-d'));
                $ed = $sd;
            }

            $days = [];
            $cursor = $sd;
            while ($cursor <= $ed) {
                $days[] = $cursor->format('Y-m-d');
                $cursor = $cursor->modify('+1 day');
            }

            return [
                'start_at' => $sd->format('Y-m-d 00:00:00'),
                'end_at' => $ed->modify('+1 day')->format('Y-m-d 00:00:00'),
                'label' => $sd->format('d/m/Y') . ' a ' . $ed->format('d/m/Y'),
                'days' => $days,
            ];
        }

        $d = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if ($d === false) {
            $d = new \DateTimeImmutable(date('Y-m-d'));
        }

        if ($mode === 'day') {
            return [
                'start_at' => $d->format('Y-m-d 00:00:00'),
                'end_at' => $d->modify('+1 day')->format('Y-m-d 00:00:00'),
                'label' => $d->format('d/m/Y'),
                'days' => [$d->format('Y-m-d')],
            ];
        }

        $dayOfWeek = (int)$d->format('w');
        $delta = ($dayOfWeek - $weekStartWeekday + 7) % 7;
        $weekStart = $d->modify('-' . $delta . ' days');
        $weekEnd = $weekStart->modify('+7 days');

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $weekStart->modify('+' . $i . ' days')->format('Y-m-d');
        }

        return [
            'start_at' => $weekStart->format('Y-m-d 00:00:00'),
            'end_at' => $weekEnd->format('Y-m-d 00:00:00'),
            'label' => $weekStart->format('d/m/Y') . ' a ' . $weekStart->modify('+6 days')->format('d/m/Y'),
            'days' => $days,
        ];
    }

    /**
     * @param list<array<string,mixed>> $items
     * @param list<array<string,mixed>> $blocks
     * @param array<int,list<array<string,mixed>>> $whByWeekday
     * @param array<string,string> $closedMap
     * @return array<string,mixed>
     */
    private function buildMetrics(array $range, array $items, array $blocks, array $whByWeekday, array $closedMap): array
    {
        $byDay = [];
        foreach ($range['days'] as $ymd) {
            $byDay[$ymd] = [
                'available_slots' => 0,
                'blocked_slots' => 0,
                'occupied_slots' => 0,
                'occupancy_pct' => 0.0,
                'total' => 0,
                'no_show' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'scheduled' => 0,
                'confirmed' => 0,
            ];
        }

        $availableSlotsByDay = $this->buildAvailableSlotsByDay($range['days'], $whByWeekday, $closedMap);
        foreach ($availableSlotsByDay as $ymd => $set) {
            $byDay[$ymd]['available_slots'] = count($set);
        }

        $blockedSlotsByDay = $this->buildBlockedSlotsByDay($range['days'], $blocks);
        foreach ($blockedSlotsByDay as $ymd => $set) {
            if (!isset($byDay[$ymd])) {
                continue;
            }
            $byDay[$ymd]['blocked_slots'] = count($set);
        }

        $occupiedSlotsByDay = $this->buildOccupiedSlotsByDay($range['days'], $items);
        foreach ($occupiedSlotsByDay as $ymd => $set) {
            if (!isset($byDay[$ymd])) {
                continue;
            }
            $byDay[$ymd]['occupied_slots'] = count($set);
        }

        foreach ($items as $it) {
            $ymd = substr((string)($it['start_at'] ?? ''), 0, 10);
            if ($ymd === '' || !isset($byDay[$ymd])) {
                continue;
            }
            $status = (string)($it['status'] ?? '');
            if ($status === 'cancelled') {
                continue;
            }

            $byDay[$ymd]['total']++;
            if (isset($byDay[$ymd][$status])) {
                $byDay[$ymd][$status]++;
            }
            if ($status === 'no_show') {
                $byDay[$ymd]['no_show']++;
            }
            if ($status === 'completed') {
                $byDay[$ymd]['completed']++;
            }
            if ($status === 'in_progress') {
                $byDay[$ymd]['in_progress']++;
            }
        }

        foreach ($byDay as $ymd => &$d) {
            $available = (int)$d['available_slots'];
            $blocked = (int)$d['blocked_slots'];
            $capacity = max(0, $available - $blocked);

            $occupied = (int)$d['occupied_slots'];
            $occPct = $capacity > 0 ? (100.0 * min($occupied, $capacity) / $capacity) : 0.0;
            $d['occupancy_pct'] = round($occPct, 1);

            $total = (int)$d['total'];
            $noShow = (int)$d['no_show'];
            $d['attendance_pct'] = $total > 0 ? round((100.0 * max(0, $total - $noShow) / $total), 1) : 0.0;
        }
        unset($d);

        $totals = [
            'total' => 0,
            'no_show' => 0,
            'completed' => 0,
            'in_progress' => 0,
            'scheduled' => 0,
            'confirmed' => 0,
            'available_slots' => 0,
            'blocked_slots' => 0,
            'occupied_slots' => 0,
        ];

        foreach ($byDay as $d) {
            foreach ($totals as $k => $_) {
                $totals[$k] += (int)($d[$k] ?? 0);
            }
        }

        $capacity = max(0, (int)$totals['available_slots'] - (int)$totals['blocked_slots']);
        $totals['occupancy_pct'] = $capacity > 0 ? round((100.0 * min((int)$totals['occupied_slots'], $capacity) / $capacity), 1) : 0.0;
        $totals['attendance_pct'] = (int)$totals['total'] > 0 ? round((100.0 * max(0, (int)$totals['total'] - (int)$totals['no_show']) / (int)$totals['total']), 1) : 0.0;

        $workload = $this->buildWorkloadByProfessional($items);

        return [
            'by_day' => $byDay,
            'totals' => $totals,
            'workload' => $workload,
        ];
    }

    /**
     * @param list<array<string,mixed>> $items
     * @return array<int,array<string,mixed>>
     */
    private function buildWorkloadByProfessional(array $items): array
    {
        $byProf = [];
        foreach ($items as $it) {
            $status = (string)($it['status'] ?? '');
            if ($status === 'cancelled') {
                continue;
            }

            $pid = (int)($it['professional_id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }

            if (!isset($byProf[$pid])) {
                $byProf[$pid] = [
                    'professional_id' => $pid,
                    'professional_name' => (string)($it['professional_name'] ?? ''),
                    'appointments' => 0,
                    'minutes' => 0,
                    'no_show' => 0,
                    'completed' => 0,
                    'in_progress' => 0,
                ];
            }

            $byProf[$pid]['appointments']++;
            if ($status === 'no_show') {
                $byProf[$pid]['no_show']++;
            }
            if ($status === 'completed') {
                $byProf[$pid]['completed']++;
            }
            if ($status === 'in_progress') {
                $byProf[$pid]['in_progress']++;
            }

            $start = (string)($it['start_at'] ?? '');
            $end = (string)($it['end_at'] ?? '');
            $st = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $start);
            $en = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $end);
            if ($st !== false && $en !== false) {
                $mins = max(0, (int)round(($en->getTimestamp() - $st->getTimestamp()) / 60));
                $byProf[$pid]['minutes'] += $mins;
            }
        }

        uasort($byProf, static function ($a, $b) {
            return ((int)($b['minutes'] ?? 0)) <=> ((int)($a['minutes'] ?? 0));
        });

        return $byProf;
    }

    /**
     * @param list<string> $days
     * @param array<int,list<array<string,mixed>>> $whByWeekday
     * @param array<string,string> $closedMap
     * @return array<string,array<int,bool>>
     */
    private function buildAvailableSlotsByDay(array $days, array $whByWeekday, array $closedMap): array
    {
        $toMinutes = static function (string $hhmm): int {
            $t = trim($hhmm);
            if (preg_match('/^(\d{2}):(\d{2})/', $t, $m) !== 1) {
                return 0;
            }
            return ((int)$m[1]) * 60 + ((int)$m[2]);
        };

        $out = [];
        foreach ($days as $ymd) {
            $out[$ymd] = [];
            if (isset($closedMap[$ymd])) {
                continue;
            }

            $d = \DateTimeImmutable::createFromFormat('Y-m-d', $ymd);
            if ($d === false) {
                continue;
            }

            $wd = (int)$d->format('w');
            $windows = $whByWeekday[$wd] ?? [];
            foreach ($windows as $w) {
                $startM = $toMinutes((string)($w['start_time'] ?? ''));
                $endM = $toMinutes((string)($w['end_time'] ?? ''));
                if ($endM <= $startM) {
                    continue;
                }
                for ($m = $startM; $m < $endM; $m += 15) {
                    $out[$ymd][$m] = true;
                }
            }
        }
        return $out;
    }

    /**
     * @param list<string> $days
     * @param list<array<string,mixed>> $blocks
     * @return array<string,array<int,bool>>
     */
    private function buildBlockedSlotsByDay(array $days, array $blocks): array
    {
        $daySet = array_fill_keys($days, true);
        $out = [];
        foreach ($days as $d) {
            $out[$d] = [];
        }

        foreach ($blocks as $b) {
            $st = (string)($b['start_at'] ?? '');
            $en = (string)($b['end_at'] ?? '');
            $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $st);
            $end = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $en);
            if ($start === false || $end === false || $end <= $start) {
                continue;
            }

            $cur = $start;
            while ($cur < $end) {
                $ymd = $cur->format('Y-m-d');
                if (!isset($daySet[$ymd])) {
                    $cur = $cur->modify('+15 minutes');
                    continue;
                }
                $m = ((int)$cur->format('H')) * 60 + (int)$cur->format('i');
                $out[$ymd][$m] = true;
                $cur = $cur->modify('+15 minutes');
            }
        }

        return $out;
    }

    /**
     * @param list<string> $days
     * @param list<array<string,mixed>> $items
     * @return array<string,array<int,bool>>
     */
    private function buildOccupiedSlotsByDay(array $days, array $items): array
    {
        $daySet = array_fill_keys($days, true);
        $out = [];
        foreach ($days as $d) {
            $out[$d] = [];
        }

        foreach ($items as $it) {
            $status = (string)($it['status'] ?? '');
            if ($status === 'cancelled') {
                continue;
            }

            $st = (string)($it['start_at'] ?? '');
            $en = (string)($it['end_at'] ?? '');
            $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $st);
            $end = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $en);
            if ($start === false || $end === false || $end <= $start) {
                continue;
            }

            $bufBefore = isset($it['buffer_before_minutes']) ? (int)$it['buffer_before_minutes'] : 0;
            $bufAfter = isset($it['buffer_after_minutes']) ? (int)$it['buffer_after_minutes'] : 0;
            $bufBefore = max(0, $bufBefore);
            $bufAfter = max(0, $bufAfter);

            $cur = $start->modify('-' . $bufBefore . ' minutes');
            $end2 = $end->modify('+' . $bufAfter . ' minutes');

            while ($cur < $end2) {
                $ymd = $cur->format('Y-m-d');
                if (!isset($daySet[$ymd])) {
                    $cur = $cur->modify('+15 minutes');
                    continue;
                }
                $m = ((int)$cur->format('H')) * 60 + (int)$cur->format('i');
                $out[$ymd][$m] = true;
                $cur = $cur->modify('+15 minutes');
            }
        }

        return $out;
    }

    /**
     * @param array<int,list<array<string,mixed>>> $whByWeekday
     * @param array<string,string> $closedMap
     * @return list<array{date:string,available_slots:int,blocked_slots:int,occupied_slots:int,occupancy_pct:float}>
     */
    private function buildForecast(int $clinicId, ?int $professionalId, int $days, array $whByWeekday, array $closedMap): array
    {
        $start = new \DateTimeImmutable(date('Y-m-d'));
        $end = $start->modify('+' . $days . ' days');

        $pdo = $this->container->get(\PDO::class);
        $items = (new AppointmentRepository($pdo))->listByClinicRangeDetailed(
            $clinicId,
            $start->format('Y-m-d 00:00:00'),
            $end->format('Y-m-d 00:00:00'),
            $professionalId
        );

        $blocks = (new SchedulingBlockRepository($pdo))->listByClinicRange(
            $clinicId,
            $start->format('Y-m-d 00:00:00'),
            $end->format('Y-m-d 00:00:00')
        );

        $dates = [];
        $cursor = $start;
        while ($cursor < $end) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor = $cursor->modify('+1 day');
        }

        $available = $this->buildAvailableSlotsByDay($dates, $whByWeekday, $closedMap);
        $blocked = $this->buildBlockedSlotsByDay($dates, $blocks);
        $occupied = $this->buildOccupiedSlotsByDay($dates, $items);

        $out = [];
        foreach ($dates as $ymd) {
            $a = count($available[$ymd] ?? []);
            $b = count($blocked[$ymd] ?? []);
            $cap = max(0, $a - $b);
            $o = count($occupied[$ymd] ?? []);
            $pct = $cap > 0 ? (100.0 * min($o, $cap) / $cap) : 0.0;
            $out[] = [
                'date' => $ymd,
                'available_slots' => $a,
                'blocked_slots' => $b,
                'occupied_slots' => $o,
                'occupancy_pct' => round($pct, 1),
            ];
        }

        return $out;
    }
}
