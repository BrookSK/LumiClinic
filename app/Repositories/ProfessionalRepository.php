<?php

declare(strict_types=1);

namespace App\Repositories;

final class ProfessionalRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listActiveByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, clinic_id, user_id, name, specialty, allow_online_booking, status
            FROM professionals
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'active'
            ORDER BY name ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $professionalId): ?array
    {
        $sql = "
            SELECT id, clinic_id, user_id, name, specialty, allow_online_booking, status
            FROM professionals
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $professionalId, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findByUserId(int $clinicId, int $userId): ?array
    {
        $sql = "
            SELECT id, clinic_id, user_id, name, specialty, allow_online_booking, status
            FROM professionals
            WHERE clinic_id = :clinic_id
              AND user_id = :user_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(int $clinicId, ?int $userId, string $name, ?string $specialty, bool $allowOnlineBooking): int
    {
        $sql = "
            INSERT INTO professionals (clinic_id, user_id, name, specialty, allow_online_booking, status, created_at)
            VALUES (:clinic_id, :user_id, :name, :specialty, :allow_online_booking, 'active', NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'user_id' => $userId,
            'name' => $name,
            'specialty' => ($specialty === '' ? null : $specialty),
            'allow_online_booking' => $allowOnlineBooking ? 1 : 0,
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
