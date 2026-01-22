<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function searchByClinic(int $clinicId, string $q, int $limit = 20): array
    {
        $sql = "
            SELECT id, name, email, phone, status
            FROM patients
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND (
                    :q = ''
                 OR name LIKE :q_like
                 OR email LIKE :q_like
                 OR phone LIKE :q_like
              )
            ORDER BY name ASC
            LIMIT " . (int)$limit . "
        ";

        $like = '%' . $q . '%';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'q' => $q,
            'q_like' => $like,
        ]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, name, email, phone, status
            FROM patients
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

    public function create(int $clinicId, string $name, ?string $email, ?string $phone): int
    {
        $sql = "
            INSERT INTO patients (clinic_id, name, email, phone, status, created_at)
            VALUES (:clinic_id, :name, :email, :phone, 'active', NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'name' => $name,
            'email' => ($email === '' ? null : $email),
            'phone' => ($phone === '' ? null : $phone),
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
