<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function countActiveByClinic(int $clinicId): int
    {
        $stmt = $this->pdo->prepare("\n            SELECT COUNT(*) AS c
            FROM patients
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
        ");
        $stmt->execute(['clinic_id' => $clinicId]);
        $row = $stmt->fetch();
        return (int)($row['c'] ?? 0);
    }

    public function createWithClinicalFields(
        int $clinicId,
        string $name,
        ?string $email,
        ?string $phone,
        int $whatsappOptIn,
        ?string $birthDate,
        ?string $sex,
        ?string $cpf,
        ?string $address,
        ?string $notes,
        ?int $referenceProfessionalId,
        ?int $patientOriginId = null
    ): int {
        $sql = "
            INSERT INTO patients (
                clinic_id, name, email, phone, birth_date, sex,
                whatsapp_opt_in, whatsapp_opt_in_updated_at,
                cpf,
                address, notes, reference_professional_id,
                patient_origin_id,
                status, created_at
            )
            VALUES (
                :clinic_id, :name, :email, :phone, :birth_date, :sex,
                :whatsapp_opt_in, NOW(),
                :cpf,
                :address, :notes, :reference_professional_id,
                :patient_origin_id,
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
            'whatsapp_opt_in' => $whatsappOptIn ? 1 : 0,
            'cpf' => ($cpf === '' ? null : $cpf),
            'address' => ($address === '' ? null : $address),
            'notes' => ($notes === '' ? null : $notes),
            'reference_professional_id' => ($referenceProfessionalId !== null && $referenceProfessionalId > 0 ? $referenceProfessionalId : null),
            'patient_origin_id' => ($patientOriginId !== null && $patientOriginId > 0 ? $patientOriginId : null),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public function searchByClinic(int $clinicId, string $q, int $limit = 20, int $offset = 0): array
    {
        $offset = max(0, $offset);
        $sql = "
            SELECT id, name, email, phone, whatsapp_opt_in, status
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
            OFFSET " . (int)$offset . "
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

    /** @return list<array<string, mixed>> */
    public function searchByClinicFiltered(int $clinicId, string $q, ?int $originId, int $limit = 20, int $offset = 0): array
    {
        $offset = max(0, $offset);
        $originFilter = '';
        $params = [
            'clinic_id' => $clinicId,
            'q' => $q,
            'q_like' => '%' . $q . '%',
        ];
        if ($originId !== null && $originId > 0) {
            $originFilter = ' AND patient_origin_id = :origin_id';
            $params['origin_id'] = $originId;
        }
        $sql = "
            SELECT p.id, p.name, p.email, p.phone, p.whatsapp_opt_in, p.status, p.patient_origin_id,
                   po.name AS origin_name
            FROM patients p
            LEFT JOIN clinic_patient_origins po ON po.id = p.patient_origin_id
            WHERE p.clinic_id = :clinic_id
              AND p.deleted_at IS NULL
              AND (
                    :q = ''
                 OR p.name LIKE :q_like
                 OR p.email LIKE :q_like
                 OR p.phone LIKE :q_like
              )
              {$originFilter}
            ORDER BY p.name ASC
            LIMIT " . (int)$limit . "
            OFFSET " . (int)$offset . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function exportByClinicFiltered(int $clinicId, string $q, ?int $originId, int $limit = 10000): array
    {
        $originFilter = '';
        $params = [
            'clinic_id' => $clinicId,
            'q' => $q,
            'q_like' => '%' . $q . '%',
        ];
        if ($originId !== null && $originId > 0) {
            $originFilter = ' AND p.patient_origin_id = :origin_id';
            $params['origin_id'] = $originId;
        }
        $sql = "
            SELECT p.id, p.name, p.email, p.phone, p.cpf, p.birth_date, p.sex, p.address, p.status, p.created_at,
                   po.name AS origin_name
            FROM patients p
            LEFT JOIN clinic_patient_origins po ON po.id = p.patient_origin_id
            WHERE p.clinic_id = :clinic_id
              AND p.deleted_at IS NULL
              AND (
                    :q = ''
                 OR p.name LIKE :q_like
                 OR p.email LIKE :q_like
                 OR p.phone LIKE :q_like
              )
              {$originFilter}
            ORDER BY p.name ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, name, email, phone, whatsapp_opt_in, status
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
                id, clinic_id, name, email, phone, whatsapp_opt_in, whatsapp_opt_in_updated_at, status,
                birth_date, sex, cpf, cpf_last4,
                address, notes, reference_professional_id,
                patient_origin_id
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
        int $whatsappOptIn,
        ?string $birthDate,
        ?string $sex,
        ?string $cpf,
        ?string $address,
        ?string $notes,
        ?int $referenceProfessionalId,
        ?int $patientOriginId,
        string $status
    ): void {
        $sql = "
            UPDATE patients
            SET
                name = :name,
                email = :email,
                phone = :phone,
                whatsapp_opt_in = :whatsapp_opt_in,
                whatsapp_opt_in_updated_at = NOW(),
                birth_date = :birth_date,
                sex = :sex,
                cpf = :cpf,
                address = :address,
                notes = :notes,
                reference_professional_id = :reference_professional_id,
                patient_origin_id = :patient_origin_id,
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
            'whatsapp_opt_in' => $whatsappOptIn ? 1 : 0,
            'birth_date' => ($birthDate === '' ? null : $birthDate),
            'sex' => ($sex === '' ? null : $sex),
            'cpf' => ($cpf === '' ? null : $cpf),
            'address' => ($address === '' ? null : $address),
            'notes' => ($notes === '' ? null : $notes),
            'reference_professional_id' => ($referenceProfessionalId !== null && $referenceProfessionalId > 0 ? $referenceProfessionalId : null),
            'patient_origin_id' => ($patientOriginId !== null && $patientOriginId > 0 ? $patientOriginId : null),
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

    public function anonymizeById(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE patients
            SET
                name = :name,
                email = NULL,
                phone = NULL,
                birth_date = NULL,
                sex = NULL,
                cpf = NULL,
                cpf_last4 = NULL,
                address = NULL,
                notes = NULL,
                status = 'inactive',
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
            'name' => 'Anonimizado #' . $id,
        ]);
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE patients
            SET deleted_at = NOW(),
                updated_at = NOW(),
                status = 'inactive'
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
        ]);
    }

    /**
     * Lista pacientes com aniversário no mês informado (1-12).
     * @return list<array<string, mixed>>
     */
    public function listBirthdaysByMonth(int $clinicId, int $month, int $limit = 500): array
    {
        $month = max(1, min(12, $month));
        $sql = "
            SELECT id, name, email, phone, whatsapp_opt_in, birth_date, status
            FROM patients
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND birth_date IS NOT NULL
              AND MONTH(birth_date) = :month
            ORDER BY DAY(birth_date) ASC, name ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'month' => $month]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /**
     * Lista pacientes sem consulta concluída nos últimos X dias (follow-up).
     * @return list<array<string, mixed>>
     */
    public function listInactivePatients(int $clinicId, int $daysSinceLastAppointment = 180, int $limit = 500): array
    {
        $sql = "
            SELECT p.id, p.name, p.email, p.phone, p.whatsapp_opt_in,
                   MAX(a.start_at) AS last_appointment_at
            FROM patients p
            LEFT JOIN appointments a
                   ON a.patient_id = p.id
                  AND a.clinic_id = p.clinic_id
                  AND a.deleted_at IS NULL
                  AND a.status = 'completed'
            WHERE p.clinic_id = :clinic_id
              AND p.deleted_at IS NULL
              AND p.status = 'active'
            GROUP BY p.id, p.name, p.email, p.phone, p.whatsapp_opt_in
            HAVING last_appointment_at IS NULL
                OR last_appointment_at < DATE_SUB(NOW(), INTERVAL :days DAY)
            ORDER BY last_appointment_at ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'days' => $daysSinceLastAppointment]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }
}
