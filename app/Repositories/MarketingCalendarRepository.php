<?php

declare(strict_types=1);

namespace App\Repositories;

final class MarketingCalendarRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByMonth(int $clinicId, string $monthYmd, int $limit = 2000): array
    {
        $limit = max(1, min($limit, 5000));

        $d = \DateTimeImmutable::createFromFormat('Y-m-d', $monthYmd);
        if ($d === false) {
            $d = new \DateTimeImmutable('first day of this month');
        }

        $from = $d->modify('first day of this month')->format('Y-m-d');
        $to = $d->modify('last day of this month')->format('Y-m-d');

        $sql = "
            SELECT
                id, clinic_id, entry_date, content_type, status, title, notes,
                assigned_user_id, created_by_user_id,
                created_at, updated_at
            FROM marketing_calendar_entries
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND entry_date >= :from_date
              AND entry_date <= :to_date
            ORDER BY entry_date ASC, id ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'from_date' => $from,
            'to_date' => $to,
        ]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listByDay(int $clinicId, string $dateYmd, int $limit = 200): array
    {
        $limit = max(1, min($limit, 500));

        $sql = "
            SELECT
                id, clinic_id, entry_date, content_type, status, title, notes,
                assigned_user_id, created_by_user_id,
                created_at, updated_at
            FROM marketing_calendar_entries
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND entry_date = :entry_date
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'entry_date' => $dateYmd,
        ]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT
                id, clinic_id, entry_date, content_type, status, title, notes,
                assigned_user_id, created_by_user_id,
                created_at, updated_at
            FROM marketing_calendar_entries
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(
        int $clinicId,
        string $entryDate,
        string $contentType,
        string $status,
        string $title,
        ?string $notes,
        ?int $assignedUserId,
        ?int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO marketing_calendar_entries (
                clinic_id,
                entry_date, content_type, status, title, notes,
                assigned_user_id,
                created_by_user_id,
                created_at
            ) VALUES (
                :clinic_id,
                :entry_date, :content_type, :status, :title, :notes,
                :assigned_user_id,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'entry_date' => $entryDate,
            'content_type' => $contentType,
            'status' => $status,
            'title' => $title,
            'notes' => ($notes === '' ? null : $notes),
            'assigned_user_id' => $assignedUserId,
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(
        int $clinicId,
        int $id,
        string $entryDate,
        string $contentType,
        string $status,
        string $title,
        ?string $notes,
        ?int $assignedUserId
    ): void {
        $sql = "
            UPDATE marketing_calendar_entries
            SET entry_date = :entry_date,
                content_type = :content_type,
                status = :status,
                title = :title,
                notes = :notes,
                assigned_user_id = :assigned_user_id,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
            'entry_date' => $entryDate,
            'content_type' => $contentType,
            'status' => $status,
            'title' => $title,
            'notes' => ($notes === '' ? null : $notes),
            'assigned_user_id' => $assignedUserId,
        ]);
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE marketing_calendar_entries
            SET deleted_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
    }
}
