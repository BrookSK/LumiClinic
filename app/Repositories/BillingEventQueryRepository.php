<?php

declare(strict_types=1);

namespace App\Repositories;

final class BillingEventQueryRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /**
     * @param array{provider?:string,event_type?:string,external_id?:string,processed?:string,from?:string,to?:string} $filters
     * @return list<array<string,mixed>>
     */
    public function search(array $filters, int $limit = 200, int $offset = 0): array
    {
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);

        $where = ['1=1'];
        $params = [];

        $provider = trim((string)($filters['provider'] ?? ''));
        if ($provider !== '') {
            $where[] = 'be.provider = :provider';
            $params['provider'] = $provider;
        }

        $eventType = trim((string)($filters['event_type'] ?? ''));
        if ($eventType !== '') {
            $where[] = 'be.event_type LIKE :event_type';
            $params['event_type'] = '%' . $eventType . '%';
        }

        $externalId = trim((string)($filters['external_id'] ?? ''));
        if ($externalId !== '') {
            $where[] = 'be.external_id LIKE :external_id';
            $params['external_id'] = '%' . $externalId . '%';
        }

        $processed = trim((string)($filters['processed'] ?? ''));
        if ($processed === 'yes') {
            $where[] = 'be.processed_at IS NOT NULL';
        } elseif ($processed === 'no') {
            $where[] = 'be.processed_at IS NULL';
        }

        $from = trim((string)($filters['from'] ?? ''));
        if ($from !== '') {
            $d = \DateTimeImmutable::createFromFormat('Y-m-d', $from);
            if ($d !== false) {
                $where[] = 'be.created_at >= :from_dt';
                $params['from_dt'] = $d->format('Y-m-d 00:00:00');
            }
        }

        $to = trim((string)($filters['to'] ?? ''));
        if ($to !== '') {
            $d = \DateTimeImmutable::createFromFormat('Y-m-d', $to);
            if ($d !== false) {
                $where[] = 'be.created_at <= :to_dt';
                $params['to_dt'] = $d->format('Y-m-d 23:59:59');
            }
        }

        $sql = "
            SELECT
                be.id,
                be.clinic_id,
                c.name AS clinic_name,
                be.provider,
                be.event_type,
                be.external_id,
                be.processed_at,
                be.created_at
            FROM billing_events be
            LEFT JOIN clinics c ON c.id = be.clinic_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY be.id DESC
            LIMIT {$limit}
            OFFSET {$offset}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }
}
