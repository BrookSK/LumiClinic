<?php
declare(strict_types=1);

// Verificacao manual da regra pura do desconto piloto: php bin/pilot_discount_check.php
require __DIR__ . '/../app/Services/Billing/PilotDiscountService.php';

use App\Services\Billing\PilotDiscountService as P;

$fail = 0;
$check = function (string $name, $got, $expected) use (&$fail): void {
    $ok = $got === $expected;
    if (!$ok) {
        $fail++;
    }
    echo ($ok ? '[ok]   ' : '[FAIL] ') . $name . ' => ' . var_export($got, true) . ($ok ? '' : ' (esperado ' . var_export($expected, true) . ')') . PHP_EOL;
};

$now = new DateTimeImmutable('2026-11-17 10:00:00');
$plan = ['id' => 2, 'price_cents' => 19990];
$sub = [
    'is_pilot' => 1, 'plan_id' => 2, 'pilot_discount_percent' => '20.00',
    'pilot_discount_plan_id' => 2, 'pilot_discount_forfeited' => 0,
    'pilot_discount_ends_at' => '2027-02-17 10:00:00',
];

// Percentuais e arredondamento
$check('20% de 199,90', P::effectivePriceCents($sub, $plan, $now), 15992);
$check('100% = gratis', P::effectivePriceCents(['pilot_discount_percent' => 100] + $sub, $plan, $now), 0);
$check('33,33% de 100,00', P::effectivePriceCents(['pilot_discount_percent' => 33.33] + $sub, ['id' => 2, 'price_cents' => 10000], $now), 6667);
$check('0,5% de 0,01 arredonda', P::discountedCents(1, 50.0), 1);

// Unidades
$s = new DateTimeImmutable('2026-01-31 00:00:00');
$check('10 dias', P::computeEndsAt($s, 10, 'days')->format('Y-m-d'), '2026-02-10');
$check('3 meses', P::computeEndsAt(new DateTimeImmutable('2026-01-15'), 3, 'months')->format('Y-m-d'), '2026-04-15');
$check('2 anos', P::computeEndsAt(new DateTimeImmutable('2026-01-15'), 2, 'years')->format('Y-m-d'), '2028-01-15');
try {
    P::computeEndsAt($s, 0, 'days');
    $check('duracao 0 lanca erro', false, true);
} catch (RuntimeException $e) {
    $check('duracao 0 lanca erro', true, true);
}
try {
    P::computeEndsAt($s, 1, 'weeks');
    $check('unidade invalida lanca erro', false, true);
} catch (RuntimeException $e) {
    $check('unidade invalida lanca erro', true, true);
}

// Expiracao
$check('antes do fim', P::isActive($sub, $now), true);
$check('no instante do fim', P::isActive($sub, new DateTimeImmutable('2027-02-17 10:00:00')), false);
$check('depois do fim', P::effectivePriceCents($sub, $plan, new DateTimeImmutable('2027-03-01')), 19990);

// Plano diferente
$check('plano diferente paga integral', P::effectivePriceCents($sub, ['id' => 3, 'price_cents' => 29990], $now), 29990);
$check('plano atual diferente do beneficiado', P::isActive(['plan_id' => 3] + $sub, $now), false);

// Perda do beneficio e nao piloto
$check('beneficio perdido', P::isActive(['pilot_discount_forfeited' => 1] + $sub, $now), false);
$check('nao piloto', P::effectivePriceCents(['is_pilot' => 0] + $sub, $plan, $now), 19990);
$check('sem snapshot', P::isActive(['is_pilot' => 1, 'plan_id' => 2], $now), false);

echo PHP_EOL . ($fail === 0 ? 'Tudo certo.' : $fail . ' falha(s).') . PHP_EOL;
exit($fail === 0 ? 0 : 1);
