<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientProcedureRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listBySale(int $clinicId, int $saleId, int $limit = 200): array
    {
        $sql = "
            SELECT
                pp.id,
                pp.patient_id,
                pp.service_id,
                pp.professional_id,
                pp.sale_id,
                pp.sale_item_id,
                pp.total_sessions,
                pp.used_sessions,
                pp.status,
                pp.created_at,
                COALESCE(s.name, '') AS service_name,
                COALESCE(pro.name, '') AS professional_name
            FROM patient_procedures pp
            LEFT JOIN services s
                   ON s.id = pp.service_id
                  AND s.clinic_id = pp.clinic_id
                  AND s.deleted_at IS NULL
            LEFT JOIN professionals pro
                   ON pro.id = pp.professional_id
                  AND pro.clinic_id = pp.clinic_id
                  AND pro.deleted_at IS NULL
            WHERE pp.clinic_id = :clinic_id
              AND pp.sale_id = :sale_id
              AND pp.deleted_at IS NULL
            ORDER BY pp.id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'sale_id' => $saleId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function create(
        int $clinicId,
        int $patientId,
        int $serviceId,
        ?int $professionalId,
        ?int $saleId,
        ?int $saleItemId,
        int $totalSessions
    ): int {
        $totalSessions = max(1, $totalSessions);

        $sql = "
            INSERT INTO patient_procedures (
                clinic_id, patient_id,
                service_id, professional_id,
                sale_id, sale_item_id,
                total_sessions, used_sessions,
                status,
                created_at
            )
            VALUES (
                :clinic_id, :patient_id,
                :service_id, :professional_id,
                :sale_id, :sale_item_id,
                :total_sessions, 0,
                'active',
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'service_id' => $serviceId,
            'professional_id' => $professionalId,
            'sale_id' => $saleId,
            'sale_item_id' => $saleItemId,
            'total_sessions' => $totalSessions,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function createIfNotExists(
        int $clinicId,
        int $patientId,
        int $serviceId,
        ?int $professionalId,
        ?int $saleId,
        ?int $saleItemId,
        int $totalSessions
    ): void {
        $totalSessions = max(1, $totalSessions);

        $sql = "
            INSERT IGNORE INTO patient_procedures (
                clinic_id, patient_id,
                service_id, professional_id,
                sale_id, sale_item_id,
                total_sessions, used_sessions,
                status,
                created_at
            )
            VALUES (
                :clinic_id, :patient_id,
                :service_id, :professional_id,
                :sale_id, :sale_item_id,
                :total_sessions, 0,
                'active',
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'service_id' => $serviceId,
            'professional_id' => $professionalId,
            'sale_id' => $saleId,
            'sale_item_id' => $saleItemId,
            'total_sessions' => $totalSessions,
        ]);
    }

    public function addUsedSessions(int $clinicId, int $id, int $delta): void
    {
        $delta = max(0, $delta);
        if ($delta === 0) {
            return;
        }

        $sql = "
            UPDATE patient_procedures
            SET used_sessions = used_sessions + :delta,
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
            'delta' => $delta,
        ]);
    }
}
