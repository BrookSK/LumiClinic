<?php
declare(strict_types=1);

namespace App\Services\Billing;

/**
 * Regra do desconto para clinicas piloto.
 * Esta classe concentra a regra pura (sem banco e sem gateway).
 *
 * Snapshot gravado em clinic_subscriptions quando a clinica e marcada como piloto:
 * pilot_discount_percent, pilot_discount_starts_at, pilot_discount_ends_at,
 * pilot_discount_plan_id (plano que recebeu o beneficio; NULL = ainda nao contratou plano pago)
 * e pilot_discount_forfeited (1 = perdeu o beneficio ao trocar de plano).
 */
final class PilotDiscountService
{
    public const UNITS = ['hours', 'days', 'months', 'years'];

    /** Limite de duracao por unidade (evita datas absurdas). */
    public const MAX_DURATION = ['hours' => 8760, 'days' => 3650, 'months' => 120, 'years' => 10];

    /** Desconto maximo: 100% zeraria a cobranca e o gateway recusa assinatura de valor zero. */
    public const MAX_PERCENT = 99.99;

    /** Fim do desconto a partir do inicio, duracao e unidade (hours|days|months|years). */
    public static function computeEndsAt(\DateTimeImmutable $start, int $duration, string $unit): \DateTimeImmutable
    {
        if ($duration <= 0 || !in_array($unit, self::UNITS, true) || $duration > self::MAX_DURATION[$unit]) {
            throw new \RuntimeException('Duração do desconto inválida.');
        }

        return $start->modify('+' . $duration . ' ' . $unit);
    }

    /** Preco com desconto em centavos: round(price * (100 - pct) / 100). */
    public static function discountedCents(int $priceCents, float $percent): int
    {
        $percent = max(0.0, min(100.0, $percent));
        return max(0, (int)round($priceCents * (100 - $percent) / 100));
    }

    /** "1 hora", "3 meses", "2 anos"... */
    public static function durationLabel(int $duration, string $unit): string
    {
        $names = [
            'hours' => ['hora', 'horas'],
            'days' => ['dia', 'dias'],
            'months' => ['mês', 'meses'],
            'years' => ['ano', 'anos'],
        ];
        if ($duration <= 0 || !isset($names[$unit])) {
            return '';
        }

        return $duration . ' ' . $names[$unit][$duration === 1 ? 0 : 1];
    }

    /** "20%", "12,5%" */
    public static function percentLabel(float $percent): string
    {
        $txt = number_format($percent, 2, ',', '');
        $txt = rtrim(rtrim($txt, '0'), ',');
        return $txt . '%';
    }

    /**
     * Clinica piloto, beneficio nao perdido e prazo ainda nao vencido
     * (independe do plano).
     *
     * @param array<string,mixed> $sub linha de clinic_subscriptions (mais a chave is_pilot)
     */
    public static function isWithinPeriod(array $sub, ?\DateTimeImmutable $now = null): bool
    {
        if ((int)($sub['is_pilot'] ?? 0) !== 1) {
            return false;
        }
        if ((int)($sub['pilot_discount_forfeited'] ?? 0) === 1) {
            return false;
        }

        $percent = (float)($sub['pilot_discount_percent'] ?? 0);
        $endsAt = trim((string)($sub['pilot_discount_ends_at'] ?? ''));
        if ($percent <= 0 || $endsAt === '') {
            return false;
        }

        $now = $now ?? new \DateTimeImmutable('now');
        try {
            return $now < new \DateTimeImmutable($endsAt);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * O desconto esta ativo quando a clinica esta no periodo do beneficio e o plano atual
     * e o plano que recebeu o beneficio. Enquanto nenhum plano pago foi contratado
     * (pilot_discount_plan_id vazio), o primeiro plano contratado recebe o desconto.
     *
     * @param array<string,mixed> $sub linha de clinic_subscriptions (mais a chave is_pilot)
     */
    public static function isActive(array $sub, ?\DateTimeImmutable $now = null): bool
    {
        if (!self::isWithinPeriod($sub, $now)) {
            return false;
        }

        $benefitPlanId = (int)($sub['pilot_discount_plan_id'] ?? 0);
        $planId = (int)($sub['plan_id'] ?? 0);
        if ($planId <= 0) {
            return false;
        }

        return $benefitPlanId <= 0 || $planId === $benefitPlanId;
    }

    /**
     * Preco efetivo em centavos para o plano informado.
     * O desconto so vale para o plano que recebeu o beneficio.
     *
     * @param array<string,mixed> $sub
     * @param array<string,mixed> $plan linha de saas_plans
     */
    public static function effectivePriceCents(array $sub, array $plan, ?\DateTimeImmutable $now = null): int
    {
        $price = max(0, (int)($plan['price_cents'] ?? 0));
        $planId = (int)($plan['id'] ?? 0);

        $forPlan = $sub;
        $forPlan['plan_id'] = $planId;
        if ($price <= 0 || !self::isActive($forPlan, $now)) {
            return $price;
        }

        return self::discountedCents($price, (float)$sub['pilot_discount_percent']);
    }

    /**
     * O que acontece com o beneficio quando a clinica passa a usar $newPlanId.
     * - 'bind': ainda nao havia plano com beneficio e o novo plano e pago -> vira o plano do beneficio.
     * - 'forfeit': ja havia plano com beneficio e o novo plano e outro -> perde definitivamente.
     * - 'none': nada muda.
     *
     * @param array<string,mixed> $sub
     */
    public static function planChangeAction(array $sub, int $newPlanId, int $newPlanPriceCents, ?\DateTimeImmutable $now = null): string
    {
        if ($newPlanId <= 0 || !self::isWithinPeriod($sub, $now)) {
            return 'none';
        }

        $benefitPlanId = (int)($sub['pilot_discount_plan_id'] ?? 0);
        if ($benefitPlanId <= 0) {
            return $newPlanPriceCents > 0 ? 'bind' : 'none';
        }

        return $benefitPlanId === $newPlanId ? 'none' : 'forfeit';
    }
}
