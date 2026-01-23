<?php

declare(strict_types=1);

namespace App\Repositories;

final class SecurityRateLimitRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now');
    }

    /**
     * @return array{allowed:bool, blocked_until:?string, remaining:int, reset_at:string}
     */
    public function hit(string $scope, string $key, int $windowSeconds, int $maxHits, int $blockSeconds): array
    {
        $windowSeconds = max(1, $windowSeconds);
        $maxHits = max(1, $maxHits);
        $blockSeconds = max(1, $blockSeconds);

        $keyHash = hash('sha256', $key);
        $now = $this->now();

        $stmt = $this->pdo->prepare(
            'SELECT id, window_start, window_seconds, hits, blocked_until FROM security_rate_limits WHERE scope = :scope AND key_hash = :key_hash LIMIT 1'
        );
        $stmt->execute(['scope' => $scope, 'key_hash' => $keyHash]);
        $row = $stmt->fetch();

        $windowStart = $now;
        $hits = 0;
        $blockedUntil = null;

        if ($row) {
            $windowStart = new \DateTimeImmutable((string)$row['window_start']);
            $hits = (int)$row['hits'];
            $blockedUntil = $row['blocked_until'] !== null ? new \DateTimeImmutable((string)$row['blocked_until']) : null;

            if ($blockedUntil !== null && $blockedUntil > $now) {
                return [
                    'allowed' => false,
                    'blocked_until' => $blockedUntil->format('Y-m-d H:i:s'),
                    'remaining' => 0,
                    'reset_at' => $windowStart->modify('+' . $windowSeconds . ' seconds')->format('Y-m-d H:i:s'),
                ];
            }

            $windowEnd = $windowStart->modify('+' . $windowSeconds . ' seconds');
            if ($now >= $windowEnd) {
                $windowStart = $now;
                $hits = 0;
                $blockedUntil = null;
            }
        }

        $hits++;
        $allowed = $hits <= $maxHits;

        $newBlockedUntil = null;
        if (!$allowed) {
            $newBlockedUntil = $now->modify('+' . $blockSeconds . ' seconds');
        }

        if (!$row) {
            $insert = $this->pdo->prepare(
                'INSERT INTO security_rate_limits (scope, key_hash, window_start, window_seconds, hits, blocked_until, created_at) VALUES (:scope, :key_hash, :window_start, :window_seconds, :hits, :blocked_until, NOW())'
            );
            $insert->execute([
                'scope' => $scope,
                'key_hash' => $keyHash,
                'window_start' => $windowStart->format('Y-m-d H:i:s'),
                'window_seconds' => $windowSeconds,
                'hits' => $hits,
                'blocked_until' => $newBlockedUntil?->format('Y-m-d H:i:s'),
            ]);
        } else {
            $update = $this->pdo->prepare(
                'UPDATE security_rate_limits SET window_start = :window_start, window_seconds = :window_seconds, hits = :hits, blocked_until = :blocked_until, updated_at = NOW() WHERE scope = :scope AND key_hash = :key_hash'
            );
            $update->execute([
                'window_start' => $windowStart->format('Y-m-d H:i:s'),
                'window_seconds' => $windowSeconds,
                'hits' => $hits,
                'blocked_until' => $newBlockedUntil?->format('Y-m-d H:i:s'),
                'scope' => $scope,
                'key_hash' => $keyHash,
            ]);
        }

        $resetAt = $windowStart->modify('+' . $windowSeconds . ' seconds');

        return [
            'allowed' => $allowed,
            'blocked_until' => $newBlockedUntil?->format('Y-m-d H:i:s'),
            'remaining' => max(0, $maxHits - $hits),
            'reset_at' => $resetAt->format('Y-m-d H:i:s'),
        ];
    }
}
