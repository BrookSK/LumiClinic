<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientPackageRepository
{
    public function __construct(private readonly \PDO $pdo) {}

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
