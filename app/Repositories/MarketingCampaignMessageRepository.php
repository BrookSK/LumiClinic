<?php

declare(strict_types=1);

namespace App\Repositories;

final class MarketingCampaignMessageRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT
                id, clinic_id, campaign_id, patient_id,
                channel,
                status,
                provider_message_id,
                scheduled_for,
                sent_at, delivered_at, read_at, clicked_at,
                click_token, click_url_snapshot,
                payload_json, response_json, error_message,
                created_at, updated_at
            FROM marketing_campaign_messages
            WHERE clinic_id = :clinic_id
              AND id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findByClinicCampaignAndPatient(int $clinicId, int $campaignId, int $patientId): ?array
    {
        $sql = "
            SELECT
                id, clinic_id, campaign_id, patient_id,
                channel,
                status,
                provider_message_id,
                scheduled_for,
                sent_at, delivered_at, read_at, clicked_at,
                click_token, click_url_snapshot,
                payload_json, response_json, error_message,
                created_at, updated_at
            FROM marketing_campaign_messages
            WHERE clinic_id = :clinic_id
              AND campaign_id = :campaign_id
              AND patient_id = :patient_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'campaign_id' => $campaignId, 'patient_id' => $patientId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function upsertQueued(
        int $clinicId,
        int $campaignId,
        int $patientId,
        string $channel,
        ?string $scheduledFor,
        ?string $clickToken,
        ?string $clickUrlSnapshot
    ): int {
        $sql = "
            INSERT INTO marketing_campaign_messages (
                clinic_id, campaign_id, patient_id,
                channel,
                status,
                scheduled_for,
                click_token,
                click_url_snapshot,
                created_at
            ) VALUES (
                :clinic_id, :campaign_id, :patient_id,
                :channel,
                'queued',
                :scheduled_for,
                :click_token,
                :click_url_snapshot,
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                status = IF(status IN ('sent','delivered','read','clicked'), status, 'queued'),
                scheduled_for = VALUES(scheduled_for),
                click_token = VALUES(click_token),
                click_url_snapshot = VALUES(click_url_snapshot),
                updated_at = NOW()
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'campaign_id' => $campaignId,
            'patient_id' => $patientId,
            'channel' => $channel,
            'scheduled_for' => ($scheduledFor === '' ? null : $scheduledFor),
            'click_token' => ($clickToken === '' ? null : $clickToken),
            'click_url_snapshot' => ($clickUrlSnapshot === '' ? null : $clickUrlSnapshot),
        ]);

        $existing = $this->findByClinicCampaignAndPatient($clinicId, $campaignId, $patientId);
        if ($existing === null) {
            return (int)$this->pdo->lastInsertId();
        }
        return (int)($existing['id'] ?? 0);
    }

    public function markProcessingSnapshot(int $clinicId, int $id, array $payload, ?string $providerMessageId = null): void
    {
        $sql = "
            UPDATE marketing_campaign_messages
               SET status = 'processing',
                   provider_message_id = :provider_message_id,
                   payload_json = :payload_json,
                   updated_at = NOW()
             WHERE clinic_id = :clinic_id
               AND id = :id
               AND deleted_at IS NULL
               AND status IN ('queued','failed','processing')
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'id' => $id,
            'provider_message_id' => ($providerMessageId === '' ? null : $providerMessageId),
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function markSent(int $clinicId, int $id, array $response, ?string $providerMessageId = null): void
    {
        $sql = "
            UPDATE marketing_campaign_messages
               SET status = 'sent',
                   sent_at = NOW(),
                   provider_message_id = :provider_message_id,
                   response_json = :response_json,
                   error_message = NULL,
                   updated_at = NOW()
             WHERE clinic_id = :clinic_id
               AND id = :id
               AND deleted_at IS NULL
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'id' => $id,
            'provider_message_id' => ($providerMessageId === '' ? null : $providerMessageId),
            'response_json' => json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function markFailed(int $clinicId, int $id, string $errorMessage, ?array $response = null): void
    {
        $sql = "
            UPDATE marketing_campaign_messages
               SET status = 'failed',
                   error_message = :error_message,
                   response_json = :response_json,
                   updated_at = NOW()
             WHERE clinic_id = :clinic_id
               AND id = :id
               AND deleted_at IS NULL
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'id' => $id,
            'error_message' => $errorMessage,
            'response_json' => $response === null ? null : json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    /** @return array<string,mixed>|null */
    public function findByClickToken(string $token): ?array
    {
        $sql = "
            SELECT
                id, clinic_id, campaign_id, patient_id,
                status,
                click_token,
                click_url_snapshot
            FROM marketing_campaign_messages
            WHERE click_token = :token
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function markClicked(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE marketing_campaign_messages
               SET status = IF(status IN ('sent','delivered','read','clicked'), 'clicked', status),
                   clicked_at = IF(clicked_at IS NULL, NOW(), clicked_at),
                   updated_at = NOW()
             WHERE clinic_id = :clinic_id
               AND id = :id
               AND deleted_at IS NULL
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id]);
    }

    public function markProviderStatus(int $clinicId, string $providerMessageId, string $status): void
    {
        $map = [
            'delivered' => 'delivered',
            'read' => 'read',
        ];
        if (!isset($map[$status])) {
            return;
        }

        $new = $map[$status];

        $field = $new === 'delivered' ? 'delivered_at' : 'read_at';

        $sql = "
            UPDATE marketing_campaign_messages
               SET status = :status,
                   {$field} = IF({$field} IS NULL, NOW(), {$field}),
                   updated_at = NOW()
             WHERE clinic_id = :clinic_id
               AND provider_message_id = :provider_message_id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'provider_message_id' => $providerMessageId,
            'status' => $new,
        ]);
    }
}
