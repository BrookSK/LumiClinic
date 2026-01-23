<?php

declare(strict_types=1);

namespace App\Repositories;

final class BillingEventRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(?int $clinicId, ?string $provider, string $eventType, ?string $externalId, array $payload): int
    {
        $provider = $provider !== null ? trim($provider) : null;
        if ($provider === '') {
            $provider = null;
        }

        $eventType = trim($eventType);
        if ($eventType === '') {
            throw new \RuntimeException('event_type inválido.');
        }

        $externalId = $externalId !== null ? trim($externalId) : null;
        if ($externalId === '') {
            $externalId = null;
        }

        $sql = "
            INSERT INTO billing_events (
                clinic_id,
                provider,
                event_type,
                external_id,
                payload_json,
                created_at
            ) VALUES (
                :clinic_id,
                :provider,
                :event_type,
                :external_id,
                :payload_json,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'provider' => $provider,
            'event_type' => $eventType,
            'external_id' => $externalId,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("\n            SELECT id, clinic_id, provider, event_type, external_id, payload_json, processed_at, created_at\n            FROM billing_events\n            WHERE id = :id\n            LIMIT 1\n        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function markProcessed(int $id): void
    {
        $stmt = $this->pdo->prepare("\n            UPDATE billing_events\n            SET processed_at = NOW()\n            WHERE id = :id\n            LIMIT 1\n        ");
        $stmt->execute(['id' => $id]);
    }
}
