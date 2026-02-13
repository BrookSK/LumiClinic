<?php

declare(strict_types=1);

namespace App\Services\Scheduling;

final class ScheduleIndicatorsService
{
    /** @return array<string,string> */
    public function statusClassMap(): array
    {
        return [
            'scheduled' => 'scheduled',
            'cancelled' => 'cancelled',
            'confirmed' => 'confirmed',
            'in_progress' => 'in_progress',
            'completed' => 'completed',
            'no_show' => 'no_show',
        ];
    }

    public function statusClass(string $status): string
    {
        $map = $this->statusClassMap();
        return $map[$status] ?? 'scheduled';
    }

    /** @param list<array<string,mixed>> $items @return array<string,list<array<string,mixed>>> */
    public function groupAppointmentsByDay(array $items): array
    {
        $byDay = [];
        foreach ($items as $it) {
            $d = substr((string)($it['start_at'] ?? ''), 0, 10);
            if ($d === '') {
                continue;
            }
            if (!isset($byDay[$d])) {
                $byDay[$d] = [];
            }
            $byDay[$d][] = $it;
        }
        return $byDay;
    }

    /** @param list<array<string,mixed>> $workingHours @return array<int,list<array<string,mixed>>> */
    public function workingHoursByWeekday(array $workingHours): array
    {
        $byWd = [];
        foreach ($workingHours as $wh) {
            $wd = (int)($wh['weekday'] ?? -1);
            if ($wd < 0 || $wd > 6) {
                continue;
            }
            if (!isset($byWd[$wd])) {
                $byWd[$wd] = [];
            }
            $byWd[$wd][] = $wh;
        }
        return $byWd;
    }

    /** @param list<array<string,mixed>> $closedDays @return array<string,string> */
    public function closedDaysMap(array $closedDays): array
    {
        $map = [];
        foreach ($closedDays as $cd) {
            $ymd = (string)($cd['closed_date'] ?? '');
            if ($ymd === '') {
                continue;
            }
            $map[$ymd] = (string)($cd['reason'] ?? '');
        }
        return $map;
    }

    /** @param array<int,list<array<string,mixed>>> $whByWeekday @return list<int> */
    public function buildWeekSlotMinutes(\DateTimeImmutable $weekStart, array $whByWeekday): array
    {
        $toMinutes = static function (string $hhmm): int {
            $t = trim($hhmm);
            if (preg_match('/^(\d{2}):(\d{2})/', $t, $m) !== 1) {
                return 0;
            }
            return ((int)$m[1]) * 60 + ((int)$m[2]);
        };

        $slotMinutes = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $weekStart->modify('+' . $i . ' days');
            $wd = (int)$d->format('w');
            $windows = $whByWeekday[$wd] ?? [];
            foreach ($windows as $w) {
                $startM = $toMinutes((string)($w['start_time'] ?? ''));
                $endM = $toMinutes((string)($w['end_time'] ?? ''));
                if ($endM <= $startM) {
                    continue;
                }
                for ($m = $startM; $m < $endM; $m += 15) {
                    $slotMinutes[$m] = true;
                }
            }
        }

        $mins = array_keys($slotMinutes);
        sort($mins);

        /** @var list<int> $mins */
        return $mins;
    }
}
