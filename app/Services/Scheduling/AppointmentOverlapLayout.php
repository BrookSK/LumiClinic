<?php

declare(strict_types=1);

namespace App\Services\Scheduling;

/**
 * Calcula a disposição lado a lado de agendamentos que se sobrepõem no tempo
 * (estilo Google Calendar), para as visões de dia e semana da agenda.
 *
 * Para cada item retorna:
 *  - lane:  índice da coluna (0-based) dentro do grupo de sobreposição
 *  - lanes: total de colunas do grupo (1 = sem conflito, ocupa a largura toda)
 */
final class AppointmentOverlapLayout
{
    /**
     * @param array<int|string, array<string,mixed>> $items  Agendamentos com start_at/end_at ("Y-m-d H:i:s").
     * @return array<int|string, array{lane:int, lanes:int}> Indexado pela mesma chave de $items.
     */
    public static function compute(array $items): array
    {
        $events = [];
        foreach ($items as $key => $it) {
            $start = strtotime((string)($it['start_at'] ?? ''));
            $end = strtotime((string)($it['end_at'] ?? ''));
            if ($start === false || $end === false || $end <= $start) {
                continue;
            }
            $events[] = ['key' => $key, 'start' => $start, 'end' => $end];
        }

        usort($events, static function (array $a, array $b): int {
            return [$a['start'], $a['end']] <=> [$b['start'], $b['end']];
        });

        $result = [];
        $cluster = [];      // chaves do grupo atual de sobreposição
        $laneEnds = [];     // fim do último evento em cada coluna do grupo
        $clusterEnd = PHP_INT_MIN;

        $flush = static function () use (&$cluster, &$laneEnds, &$result): void {
            $lanes = max(1, count($laneEnds));
            foreach ($cluster as $key) {
                $result[$key]['lanes'] = $lanes;
            }
            $cluster = [];
            $laneEnds = [];
        };

        foreach ($events as $ev) {
            // Novo grupo quando o evento começa depois de todos os anteriores terminarem.
            if ($cluster !== [] && $ev['start'] >= $clusterEnd) {
                $flush();
                $clusterEnd = PHP_INT_MIN;
            }

            // Primeira coluna livre; se nenhuma, abre uma nova.
            $lane = null;
            foreach ($laneEnds as $i => $laneEnd) {
                if ($laneEnd <= $ev['start']) {
                    $lane = $i;
                    break;
                }
            }
            if ($lane === null) {
                $lane = count($laneEnds);
            }

            $laneEnds[$lane] = $ev['end'];
            $result[$ev['key']] = ['lane' => $lane, 'lanes' => 1];
            $cluster[] = $ev['key'];
            $clusterEnd = max($clusterEnd, $ev['end']);
        }

        if ($cluster !== []) {
            $flush();
        }

        return $result;
    }

    /**
     * Gera o CSS de posição horizontal (left/width) para o item, dentro de um
     * container com 6px de respiro em cada lado.
     *
     * @param array{lane:int, lanes:int}|null $pos
     */
    public static function horizontalStyle(?array $pos): string
    {
        $lane = (int)($pos['lane'] ?? 0);
        $lanes = max(1, (int)($pos['lanes'] ?? 1));

        if ($lanes === 1) {
            return 'left:6px; right:6px; width:auto;';
        }

        $gap = 4; // px entre colunas
        $frac = 1 / $lanes;
        $leftPct = round($lane * $frac * 100, 4);
        $widthPct = round($frac * 100, 4);

        return sprintf(
            'left:calc(6px + (100%% - 12px) * %s / 100); width:calc((100%% - 12px) * %s / 100 - %dpx); right:auto;',
            $leftPct,
            $widthPct,
            $lane < $lanes - 1 ? $gap : 0
        );
    }
}
