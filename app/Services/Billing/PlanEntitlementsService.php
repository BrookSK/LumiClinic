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

    /** Minutos de transcrição usados no mês atual. */
    public function transcriptionUsedMinutes(int $clinicId): int
    {
        $pdo = $this->container->get(\PDO::class);
        $firstOfMonth = date('Y-m-01 00:00:00');
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND, '2000-01-01', SEC_TO_TIME(COALESCE(duration_seconds, 0)))), 0) AS total_seconds,
                   COUNT(*) AS total_count
            FROM medical_record_audio_notes
            WHERE clinic_id = :clinic_id
              AND status = 'transcribed'
              AND created_at >= :first_of_month
              AND deleted_at IS NULL
        ");
        $stmt->execute(['clinic_id' => $clinicId, 'first_of_month' => $firstOfMonth]);
        $row = $stmt->fetch();

        // Se não temos duration_seconds, estimamos ~1 min por transcrição
        $count = (int)($row['total_count'] ?? 0);
        return max($count, 0); // 1 transcrição ≈ 1 minuto (estimativa conservadora)
    }

    /** @return array{limit:?int,used:int,remaining:?int,blocked:bool} */
    public function transcriptionStatus(int $clinicId): array
    {
        $limit = $this->transcriptionLimitMinutes($clinicId);
        $used = $this->transcriptionUsedMinutes($clinicId);
        $remaining = $limit !== null ? max(0, $limit - $used) : null;
        $blocked = $limit !== null && $remaining !== null && $remaining <= 0;

        return [
            'limit' => $limit,
            'used' => $used,
            'remaining' => $remaining,
            'blocked' => $blocked,
        ];
    }
}
