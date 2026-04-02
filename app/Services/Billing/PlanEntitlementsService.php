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

    /** Limite de transcrição em minutos/mês. null = ilimitado. */
    public function transcriptionLimitMinutes(int $clinicId): ?int
    {
        $limits = $this->limitsForClinic($clinicId);
        $v = $limits['transcription_minutes'] ?? null;
        if ($v === null) return null;
        $n = (int)$v;
        return $n > 0 ? $n : null;
    }

    /** Segundos de transcrição usados no mês atual. */
    public function transcriptionUsedSeconds(int $clinicId): int
    {
        $pdo = $this->container->get(\PDO::class);
        $firstOfMonth = date('Y-m-01 00:00:00');

        try {
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(
                    CASE
                        WHEN duration_seconds IS NOT NULL AND duration_seconds > 0 THEN duration_seconds
                        WHEN size_bytes IS NOT NULL AND size_bytes > 0 THEN GREATEST(1, ROUND(size_bytes / 6000))
                        ELSE 60
                    END
                ), 0) AS total_seconds
                FROM medical_record_audio_notes
                WHERE clinic_id = :clinic_id
                  AND status = 'transcribed'
                  AND created_at >= :first_of_month
                  AND deleted_at IS NULL
            ");
            $stmt->execute(['clinic_id' => $clinicId, 'first_of_month' => $firstOfMonth]);
            $row = $stmt->fetch();
            return max(0, (int)($row['total_seconds'] ?? 0));
        } catch (\Throwable $e) {
            try {
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(
                        CASE WHEN size_bytes IS NOT NULL AND size_bytes > 0 THEN GREATEST(1, ROUND(size_bytes / 6000)) ELSE 60 END
                    ), 0) AS total_seconds
                    FROM medical_record_audio_notes
                    WHERE clinic_id = :clinic_id
                      AND status = 'transcribed'
                      AND created_at >= :first_of_month
                      AND deleted_at IS NULL
                ");
                $stmt->execute(['clinic_id' => $clinicId, 'first_of_month' => $firstOfMonth]);
                $row = $stmt->fetch();
                return max(0, (int)($row['total_seconds'] ?? 0));
            } catch (\Throwable $e2) {
                return 0;
            }
        }
    }

    /** @return array{limit_seconds:?int,used_seconds:int,remaining_seconds:?int,blocked:bool} */
    public function transcriptionStatus(int $clinicId): array
    {
        $limitMinutes = $this->transcriptionLimitMinutes($clinicId);
        $limitSeconds = $limitMinutes !== null ? $limitMinutes * 60 : null;
        $usedSeconds = $this->transcriptionUsedSeconds($clinicId);
        $remainingSeconds = $limitSeconds !== null ? max(0, $limitSeconds - $usedSeconds) : null;
        $blocked = $limitSeconds !== null && $remainingSeconds !== null && $remainingSeconds <= 0;

        return [
            'limit_seconds' => $limitSeconds,
            'used_seconds' => $usedSeconds,
            'remaining_seconds' => $remainingSeconds,
            'blocked' => $blocked,
            // Compat: manter campos em minutos para views que usam
            'limit' => $limitMinutes,
            'used' => (int)floor($usedSeconds / 60),
            'remaining' => $remainingSeconds !== null ? (int)floor($remainingSeconds / 60) : null,
        ];
    }
}
