<?php

declare(strict_types=1);

namespace App\Repositories;

final class MarketingCampaignRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByClinic(int $clinicId, int $limit = 200): array
    {
        $sql = "
            SELECT
                id, clinic_id,
                name, channel,
                segment_id,
                whatsapp_template_code,
                email_subject,
                status,
                scheduled_for,
                last_run_at,
                created_by_user_id,
                created_at, updated_at
            FROM marketing_campaigns
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT
                id, clinic_id,
                name, channel,
                segment_id,
                whatsapp_template_code,
                email_subject,
                email_body,
                click_url,
                status,
                scheduled_for,
                last_run_at,
                created_by_user_id,
                created_at, updated_at
            FROM marketing_campaigns
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

    public function create(
        int $clinicId,
        string $name,
        string $channel,
        ?int $segmentId,
        ?string $whatsappTemplateCode,
        ?string $emailSubject,
        ?string $emailBody,
        ?string $clickUrl,
        string $status,
        ?string $scheduledFor,
        ?int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO marketing_campaigns (
                clinic_id,
                name, channel,
                segment_id,
                whatsapp_template_code,
                email_subject,
                email_body,
                click_url,
                status,
                scheduled_for,
                created_by_user_id,
                created_at
            ) VALUES (
                :clinic_id,
                :name, :channel,
                :segment_id,
                :whatsapp_template_code,
                :email_subject,
                :email_body,
                :click_url,
                :status,
                :scheduled_for,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'name' => $name,
            'channel' => $channel,
            'segment_id' => ($segmentId !== null && $segmentId > 0 ? $segmentId : null),
            'whatsapp_template_code' => ($whatsappTemplateCode === '' ? null : $whatsappTemplateCode),
            'email_subject' => ($emailSubject === '' ? null : $emailSubject),
            'email_body' => ($emailBody === '' ? null : $emailBody),
            'click_url' => ($clickUrl === '' ? null : $clickUrl),
            'status' => $status,
            'scheduled_for' => ($scheduledFor === '' ? null : $scheduledFor),
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function markLastRun(int $clinicId, int $id, string $status): void
    {
        $sql = "
            UPDATE marketing_campaigns
               SET last_run_at = NOW(),
                   status = :status,
                   updated_at = NOW()
             WHERE clinic_id = :clinic_id
               AND id = :id
               AND deleted_at IS NULL
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id, 'status' => $status]);
    }

    /** @return list<array<string,mixed>> */
    public function listActiveByTriggerEvent(int $clinicId, string $triggerEvent, int $limit = 200): array
    {
        $triggerEvent = trim($triggerEvent);
        if ($triggerEvent === '') {
            return [];
        }

        $sql = "
            SELECT
                id, clinic_id,
                name, channel,
                segment_id,
                trigger_event,
                trigger_delay_minutes,
                whatsapp_template_code,
                email_subject,
                email_body,
                click_url,
                status,
                scheduled_for,
                last_run_at,
                created_by_user_id,
                created_at, updated_at
            FROM marketing_campaigns
            WHERE clinic_id = :clinic_id
              AND trigger_event = :trigger_event
              AND status IN ('scheduled','running')
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'trigger_event' => $triggerEvent]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }
}
