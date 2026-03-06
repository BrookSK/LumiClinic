<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientPackageRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed>|null */
    public function findActiveByIdForPatient(int $clinicId, int $patientId, int $patientPackageId): ?array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, package_id,
                   total_sessions, used_sessions, valid_until, status
            FROM patient_packages
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND patient_id = :patient_id
              AND deleted_at IS NULL
              AND status = 'active'
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $patientPackageId, 'clinic_id' => $clinicId, 'patient_id' => $patientId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Consome 1 sessão do pacote, com trava (FOR UPDATE).
     * Retorna false se não pode consumir (expirado/sem saldo/inativo).
     */
    public function consumeOneSessionForUpdate(int $clinicId, int $patientPackageId): bool
    {
        $sql = "
            SELECT id, total_sessions, used_sessions, valid_until, status
            FROM patient_packages
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            FOR UPDATE
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $patientPackageId, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }

        if ((string)($row['status'] ?? '') !== 'active') {
            return false;
        }

        $validUntil = isset($row['valid_until']) ? (string)$row['valid_until'] : '';
        if ($validUntil !== '') {
            $d = \DateTimeImmutable::createFromFormat('Y-m-d', $validUntil);
            if ($d !== false) {
                $today = new \DateTimeImmutable('today');
                if ($d < $today) {
                    return false;
                }
            }
        }

        $total = (int)($row['total_sessions'] ?? 0);
        $used = (int)($row['used_sessions'] ?? 0);
        if ($total <= 0 || $used >= $total) {
            return false;
        }

        $upd = $this->pdo->prepare("\n            UPDATE patient_packages\n            SET used_sessions = used_sessions + 1,\n                updated_at = NOW()\n            WHERE id = :id\n              AND clinic_id = :clinic_id\n              AND deleted_at IS NULL\n            LIMIT 1\n        ");
        $upd->execute(['id' => $patientPackageId, 'clinic_id' => $clinicId]);

        return true;
    }

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
