<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Core\Container\Container;

final class PlanEntitlementsService
{
    public function __construct(private readonly Container $container) {}

    /** @return array<string,mixed> */
    public function limitsForClinic(int $clinicId): array
    {
        $billing = new BillingService($this->container);
        $data = $billing->getOrCreateClinicSubscription($clinicId);
        $plan = $data['plan'];

        if (!is_array($plan) || !isset($plan['limits_json'])) {
            return [];
        }

        $raw = $plan['limits_json'];
        $decoded = null;

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $decoded = $raw;
        }

        return is_array($decoded) ? $decoded : [];
    }

    public function isPortalEnabled(int $clinicId): bool
    {
        $limits = $this->limitsForClinic($clinicId);
        if (!array_key_exists('portal', $limits)) {
            return true;
        }
        return (bool)$limits['portal'];
    }

    public function usersLimit(int $clinicId): ?int
    {
        $limits = $this->limitsForClinic($clinicId);
        $v = $limits['users'] ?? null;
        if ($v === null) {
            return null;
        }
        $n = (int)$v;
        return $n > 0 ? $n : null;
    }

    public function patientsLimit(int $clinicId): ?int
    {
        $limits = $this->limitsForClinic($clinicId);
        $v = $limits['patients'] ?? null;
        if ($v === null) {
            return null;
        }
        $n = (int)$v;
        return $n > 0 ? $n : null;
    }

    public function storageLimitBytes(int $clinicId): ?int
    {
        $limits = $this->limitsForClinic($clinicId);
        $v = $limits['storage_mb'] ?? null;
        if ($v === null) {
            return null;
        }
        $mb = (int)$v;
        if ($mb <= 0) {
            return null;
        }
        return $mb * 1024 * 1024;
    }
}
