<?php

declare(strict_types=1);

namespace App\Services\MedicalRecords;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\MedicalRecordRepository;
use App\Repositories\PatientRepository;
use App\Repositories\ProfessionalRepository;
use App\Services\Auth\AuthService;

final class MedicalRecordService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{patient:array<string,mixed>,records:list<array<string,mixed>>} */
    public function timeline(int $patientId, string $ip, ?string $userAgent = null): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $patients = new PatientRepository($pdo);
        $patient = $patients->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $repo = new MedicalRecordRepository($pdo);
        $records = $repo->listByPatient($clinicId, $patientId, 200);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log(
            $actorId,
            $clinicId,
            'medical_records.view',
            ['patient_id' => $patientId],
            $ip,
            $roleCodes,
            'patient',
            $patientId,
            $userAgent
        );

        return ['patient' => $patient, 'records' => $records];
    }

    /** @return list<array<string, mixed>> */
    public function listProfessionals(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ProfessionalRepository($this->container->get(\PDO::class));
        return $repo->listActiveByClinic($clinicId);
    }

    /** @param array{professional_id:?int,attended_at:string,procedure_type:?string,clinical_description:?string,clinical_evolution:?string,notes:?string} $data */
    public function create(int $patientId, array $data, string $ip, ?string $userAgent = null): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $patients = new PatientRepository($pdo);
        if ($patients->findById($clinicId, $patientId) === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $repo = new MedicalRecordRepository($pdo);
        $id = $repo->create(
            $clinicId,
            $patientId,
            $data['professional_id'],
            $data['attended_at'],
            $data['procedure_type'],
            $data['clinical_description'],
            $data['clinical_evolution'],
            $data['notes'],
            $actorId
        );

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log(
            $actorId,
            $clinicId,
            'medical_records.create',
            ['medical_record_id' => $id, 'patient_id' => $patientId],
            $ip,
            $roleCodes,
            'medical_record',
            $id,
            $userAgent
        );

        return $id;
    }

    /** @return array{patient:array<string,mixed>,record:array<string,mixed>} */
    public function getForEdit(int $patientId, int $recordId, string $ip, ?string $userAgent = null): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $patients = new PatientRepository($pdo);
        $patient = $patients->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $repo = new MedicalRecordRepository($pdo);
        $record = $repo->findById($clinicId, $recordId);
        if ($record === null || (int)$record['patient_id'] !== $patientId) {
            throw new \RuntimeException('Registro inválido.');
        }

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log(
            $actorId,
            $clinicId,
            'medical_records.view',
            ['patient_id' => $patientId, 'medical_record_id' => $recordId, 'context' => 'edit'],
            $ip,
            $roleCodes,
            'medical_record',
            $recordId,
            $userAgent
        );

        return ['patient' => $patient, 'record' => $record];
    }

    /** @param array{professional_id:?int,attended_at:string,procedure_type:?string,clinical_description:?string,clinical_evolution:?string,notes:?string} $data */
    public function update(int $patientId, int $recordId, array $data, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new MedicalRecordRepository($pdo);

        $current = $repo->findById($clinicId, $recordId);
        if ($current === null || (int)$current['patient_id'] !== $patientId) {
            throw new \RuntimeException('Registro inválido.');
        }

        try {
            $pdo->beginTransaction();

            $versionNo = $repo->nextVersionNo($clinicId, $recordId);
            $snapshotJson = json_encode($current, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($snapshotJson === false) {
                throw new \RuntimeException('Falha ao gerar snapshot.');
            }

            $repo->createVersion($clinicId, $recordId, $versionNo, $snapshotJson, $actorId, $ip);

            $repo->update(
                $clinicId,
                $recordId,
                $data['professional_id'],
                $data['attended_at'],
                $data['procedure_type'],
                $data['clinical_description'],
                $data['clinical_evolution'],
                $data['notes']
            );

            $audit = new AuditLogRepository($pdo);
            $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
            $audit->log(
                $actorId,
                $clinicId,
                'medical_records.update',
                ['medical_record_id' => $recordId, 'patient_id' => $patientId, 'version_no' => $versionNo],
                $ip,
                $roleCodes,
                'medical_record',
                $recordId,
                $userAgent
            );

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
