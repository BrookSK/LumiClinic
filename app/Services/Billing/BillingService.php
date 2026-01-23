<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Core\Container\Container;
use App\Repositories\ClinicSubscriptionRepository;
use App\Repositories\SaasPlanRepository;

final class BillingService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{subscription:array<string,mixed>,plan:?array<string,mixed>} */
    public function getOrCreateClinicSubscription(int $clinicId): array
    {
        $pdo = $this->container->get(\PDO::class);
        $subsRepo = new ClinicSubscriptionRepository($pdo);
        $plansRepo = new SaasPlanRepository($pdo);

        $sub = $subsRepo->findByClinicId($clinicId);
        if ($sub === null) {
            $trialPlan = $plansRepo->findActiveByCode('trial');
            $planId = $trialPlan !== null ? (int)$trialPlan['id'] : null;
            $trialDays = $trialPlan !== null ? (int)($trialPlan['trial_days'] ?? 0) : 14;

            $subsRepo->createTrial($clinicId, $planId, $trialDays);
            $sub = $subsRepo->findByClinicId($clinicId);
            if ($sub === null) {
                throw new \RuntimeException('Falha ao criar assinatura da clínica.');
            }
        }

        $plan = null;
        if (isset($sub['plan_id']) && $sub['plan_id'] !== null) {
            $plan = $plansRepo->findById((int)$sub['plan_id']);
        }

        return ['subscription' => $sub, 'plan' => $plan];
    }

    public function isBlocked(array $subscription): bool
    {
        $status = (string)($subscription['status'] ?? '');
        if ($status === 'active' || $status === 'trial') {
            if ($status === 'trial') {
                $trialEndsAt = $subscription['trial_ends_at'] ?? null;
                if ($trialEndsAt !== null && (string)$trialEndsAt !== '') {
                    $ends = new \DateTimeImmutable((string)$trialEndsAt);
                    if ($ends < new \DateTimeImmutable('now')) {
                        return true;
                    }
                }
            }
            return false;
        }

        if (in_array($status, ['past_due', 'suspended', 'canceled'], true)) {
            $since = $subscription['past_due_since'] ?? null;
            if ($since === null || (string)$since === '') {
                return true;
            }

            $sinceDt = new \DateTimeImmutable((string)$since);
            $graceEnds = $sinceDt->modify('+7 days');
            return $graceEnds < new \DateTimeImmutable('now');
        }

        return false;
    }
}
