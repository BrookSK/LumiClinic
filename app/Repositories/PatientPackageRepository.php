<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientPackageRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listActiveByPatient(int $clinicId, int $patientId, int $limit = 20): array
    {
        $sql = "
            SELECT
                pp.id,
                pp.package_id,
                pp.total_sessions,
                pp.used_sessions,
                pp.valid_until,
                pp.status,
                pkg.name AS package_name
            FROM patient_packages pp
            LEFT JOIN packages pkg
                   ON pkg.id = pp.package_id
                  AND pkg.clinic_id = pp.clinic_id
                  AND pkg.deleted_at IS NULL
            WHERE pp.clinic_id = :clinic_id
              AND pp.patient_id = :patient_id
              AND pp.deleted_at IS NULL
              AND pp.status = 'active'
            ORDER BY pp.id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function create(
        int $clinicId,
        int $patientId,
        int $packageId,
        ?int $saleId,
        ?int $saleItemId,
        int $totalSessions,
        ?string $validUntil
    ): int {
        $sql = "
            INSERT INTO patient_packages (
                clinic_id, patient_id,
                package_id, sale_id, sale_item_id,
                total_sessions, used_sessions,
                valid_until, status,
                created_at
            )
            VALUES (
                :clinic_id, :patient_id,
                :package_id, :sale_id, :sale_item_id,
                :total_sessions, 0,
                :valid_until, 'active',
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'package_id' => $packageId,
            'sale_id' => $saleId,
            'sale_item_id' => $saleItemId,
            'total_sessions' => $totalSessions,
            'valid_until' => ($validUntil === '' ? null : $validUntil),
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
