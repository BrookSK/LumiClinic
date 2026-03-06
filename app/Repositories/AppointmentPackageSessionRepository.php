<?php

declare(strict_types=1);

namespace App\Repositories;

final class AppointmentPackageSessionRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function existsForAppointment(int $clinicId, int $appointmentId): bool
    {
        $sql = "
            SELECT id
            FROM appointment_package_sessions
            WHERE clinic_id = :clinic_id
              AND appointment_id = :appointment_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'appointment_id' => $appointmentId]);
        $row = $stmt->fetch();

        return $row !== false && $row !== null;
    }

    public function create(
        int $clinicId,
        int $appointmentId,
        int $patientPackageId,
        string $consumedAt,
        ?int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO appointment_package_sessions (
                clinic_id,
                appointment_id,
                patient_package_id,
                consumed_at,
                created_by_user_id,
                created_at
            ) VALUES (
                :clinic_id,
                :appointment_id,
                :patient_package_id,
                :consumed_at,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'appointment_id' => $appointmentId,
            'patient_package_id' => $patientPackageId,
            'consumed_at' => $consumedAt,
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
