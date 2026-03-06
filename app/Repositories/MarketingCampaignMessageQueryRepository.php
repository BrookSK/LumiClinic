<?php

declare(strict_types=1);

namespace App\Repositories;

final class MarketingCampaignMessageQueryRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /**
     * @param array{status?:string,campaign_id?:int,q?:string,from?:string,to?:string} $filters
     * @return list<array<string,mixed>>
     */
    public function search(int $clinicId, array $filters, int $limit = 200, int $offset = 0): array
    {
        $limit = max(1, min($limit, 500));
        $offset = max(0, $offset);

        $status = isset($filters['status']) ? trim((string)$filters['status']) : '';
        $campaignId = isset($filters['campaign_id']) ? (int)$filters['campaign_id'] : 0;
        $q = isset($filters['q']) ? trim((string)$filters['q']) : '';

        $from = isset($filters['from']) ? trim((string)$filters['from']) : '';
        $to = isset($filters['to']) ? trim((string)$filters['to']) : '';

        $where = [
            'm.clinic_id = :clinic_id',
            'm.deleted_at IS NULL',
        ];
        $params = ['clinic_id' => $clinicId];

        if ($status !== '') {
            $where[] = 'm.status = :status';
            $params['status'] = $status;
        }

        if ($campaignId > 0) {
            $where[] = 'm.campaign_id = :campaign_id';
            $params['campaign_id'] = $campaignId;
        }

        if ($from !== '' && $to !== '') {
            $where[] = 'DATE(m.created_at) BETWEEN :from AND :to';
            $params['from'] = $from;
            $params['to'] = $to;
        }

        if ($q !== '') {
            $where[] = '(p.name LIKE :q_like OR p.email LIKE :q_like OR p.phone LIKE :q_like)';
            $params['q_like'] = '%' . $q . '%';
        }

        $sql = "
            SELECT
                m.id,
                m.clinic_id,
                m.campaign_id,
                c.name AS campaign_name,
                c.channel AS campaign_channel,
                m.patient_id,
                p.name AS patient_name,
                p.email AS patient_email,
                p.phone AS patient_phone,
                m.channel,
                m.status,
                m.provider_message_id,
                m.scheduled_for,
                m.sent_at,
                m.delivered_at,
                m.read_at,
                m.clicked_at,
                m.error_message,
                m.created_at,
                m.updated_at
            FROM marketing_campaign_messages m
            INNER JOIN marketing_campaigns c
                    ON c.id = m.campaign_id
                   AND c.clinic_id = m.clinic_id
                   AND c.deleted_at IS NULL
            INNER JOIN patients p
                    ON p.id = m.patient_id
                   AND p.clinic_id = m.clinic_id
                   AND p.deleted_at IS NULL
            WHERE " . implode("\n              AND ", $where) . "
            ORDER BY m.id DESC
            LIMIT " . (int)$limit . "
            OFFSET " . (int)$offset . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function count(int $clinicId, array $filters): int
    {
        $status = isset($filters['status']) ? trim((string)$filters['status']) : '';
        $campaignId = isset($filters['campaign_id']) ? (int)$filters['campaign_id'] : 0;
        $q = isset($filters['q']) ? trim((string)$filters['q']) : '';

        $where = [
            'm.clinic_id = :clinic_id',
            'm.deleted_at IS NULL',
        ];
        $params = ['clinic_id' => $clinicId];

        if ($status !== '') {
            $where[] = 'm.status = :status';
            $params['status'] = $status;
        }

        if ($campaignId > 0) {
            $where[] = 'm.campaign_id = :campaign_id';
            $params['campaign_id'] = $campaignId;
        }

        if ($q !== '') {
            $where[] = '(p.name LIKE :q_like OR p.email LIKE :q_like OR p.phone LIKE :q_like)';
            $params['q_like'] = '%' . $q . '%';
        }

        $sql = "
            SELECT COUNT(*) AS c
            FROM marketing_campaign_messages m
            INNER JOIN marketing_campaigns c
                    ON c.id = m.campaign_id
                   AND c.clinic_id = m.clinic_id
                   AND c.deleted_at IS NULL
            INNER JOIN patients p
                    ON p.id = m.patient_id
                   AND p.clinic_id = m.clinic_id
                   AND p.deleted_at IS NULL
            WHERE " . implode("\n              AND ", $where) . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return (int)($row['c'] ?? 0);
    }
}
