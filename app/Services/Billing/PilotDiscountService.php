<?php
declare(strict_types=1);

namespace App\Services\Billing;

/**
 * Regra do desconto para clinicas piloto.
 * Esta classe concentra a regra pura (sem banco e sem gateway).
 */
final class PilotDiscountService
{
    public const UNITS = ['days', 'months', 'years'];

    /** Fim do desconto a partir do inicio, duracao e unidade (days|months|years). */
    public static function computeEndsAt(\DateTimeImmutable $start, int $duration, string $unit): \DateTimeImmutable
    {
        if ($duration <= 0 || !in_array($unit, self::UNITS, true)) {
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

    /**
     * O desconto esta ativo quando a clinica e piloto, o beneficio nao foi perdido,
     * o plano atual e o plano que recebeu o beneficio e o prazo nao venceu.
     *
     * @param array<string,mixed> $sub linha de clinic_subscriptions (mais a chave is_pilot)
     */
    public static function isActive(array $sub, ?\DateTimeImmutable $now = null): bool
    {
        if ((int)($sub['is_pilot'] ?? 0) !== 1) {
            return false;
        }
        if ((int)($sub['pilot_discount_forfeited'] ?? 0) === 1) {
            return false;
        }

        $percent = (float)($sub['pilot_discount_percent'] ?? 0);
        $benefitPlanId = (int)($sub['pilot_discount_plan_id'] ?? 0);
        $planId = (int)($sub['plan_id'] ?? 0);
        $endsAt = trim((string)($sub['pilot_discount_ends_at'] ?? ''));
        if ($percent <= 0 || $benefitPlanId <= 0 || $planId !== $benefitPlanId || $endsAt === '') {
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
        if (!self::isActive($forPlan, $now)) {
            return $price;
        }

        return self::discountedCents($price, (float)$sub['pilot_discount_percent']);
    }
}
