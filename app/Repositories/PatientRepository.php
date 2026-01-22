<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function createWithClinicalFields(
        int $clinicId,
        string $name,
        ?string $email,
        ?string $phone,
        ?string $birthDate,
        ?string $sex,
        ?string $cpfEncrypted,
        ?string $cpfLast4,
        ?string $address,
        ?string $notes,
        ?int $referenceProfessionalId
    ): int {
        $sql = "
            INSERT INTO patients (
                clinic_id, name, email, phone, birth_date, sex,
                cpf_encrypted, cpf_last4,
                address, notes, reference_professional_id,
                status, created_at
            )
            VALUES (
                :clinic_id, :name, :email, :phone, :birth_date, :sex,
                :cpf_encrypted, :cpf_last4,
                :address, :notes, :reference_professional_id,
                'active', NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'name' => $name,
            'email' => ($email === '' ? null : $email),
            'phone' => ($phone === '' ? null : $phone),
            'birth_date' => ($birthDate === '' ? null : $birthDate),
            'sex' => ($sex === '' ? null : $sex),
            'cpf_encrypted' => ($cpfEncrypted === '' ? null : $cpfEncrypted),
            'cpf_last4' => ($cpfLast4 === '' ? null : $cpfLast4),
            'address' => ($address === '' ? null : $address),
            'notes' => ($notes === '' ? null : $notes),
            'reference_professional_id' => ($referenceProfessionalId !== null && $referenceProfessionalId > 0 ? $referenceProfessionalId : null),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

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

    /** @return array<string, mixed>|null */
    public function findClinicalById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT
                id, clinic_id, name, email, phone, status,
                birth_date, sex, cpf_last4,
                address, notes, reference_professional_id
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

    public function updateClinicalFields(
        int $clinicId,
        int $id,
        string $name,
        ?string $email,
        ?string $phone,
        ?string $birthDate,
        ?string $sex,
        ?string $cpfEncrypted,
        ?string $cpfLast4,
        ?string $address,
        ?string $notes,
        ?int $referenceProfessionalId,
        string $status
    ): void {
        $sql = "
            UPDATE patients
            SET
                name = :name,
                email = :email,
                phone = :phone,
                birth_date = :birth_date,
                sex = :sex,
                cpf_encrypted = :cpf_encrypted,
                cpf_last4 = :cpf_last4,
                address = :address,
                notes = :notes,
                reference_professional_id = :reference_professional_id,
                status = :status,
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
            'name' => $name,
            'email' => ($email === '' ? null : $email),
            'phone' => ($phone === '' ? null : $phone),
            'birth_date' => ($birthDate === '' ? null : $birthDate),
            'sex' => ($sex === '' ? null : $sex),
            'cpf_encrypted' => ($cpfEncrypted === '' ? null : $cpfEncrypted),
            'cpf_last4' => ($cpfLast4 === '' ? null : $cpfLast4),
            'address' => ($address === '' ? null : $address),
            'notes' => ($notes === '' ? null : $notes),
            'reference_professional_id' => ($referenceProfessionalId !== null && $referenceProfessionalId > 0 ? $referenceProfessionalId : null),
            'status' => $status,
        ]);
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
