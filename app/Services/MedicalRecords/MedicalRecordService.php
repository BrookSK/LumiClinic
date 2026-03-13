<?php

declare(strict_types=1);

namespace App\Services\MedicalRecords;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\MedicalImageRepository;
use App\Repositories\MedicalRecordRepository;
use App\Repositories\MedicalRecordTemplateFieldRepository;
use App\Repositories\MedicalRecordTemplateRepository;
use App\Repositories\PatientAllergyRepository;
use App\Repositories\PatientClinicalAlertRepository;
use App\Repositories\PatientConditionRepository;
use App\Repositories\PatientRepository;
use App\Repositories\ProfessionalRepository;
use App\Services\Auth\AuthService;
use App\Services\Compliance\SensitiveDataAuditService;

final class MedicalRecordService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{patient:array<string,mixed>,records:list<array<string,mixed>>,alerts:list<array<string,mixed>>,allergies:list<array<string,mixed>>,conditions:list<array<string,mixed>>,images:list<array<string,mixed>>,image_pairs:list<array<string,mixed>>} */
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

        $alerts = (new PatientClinicalAlertRepository($pdo))->listByPatient($clinicId, $patientId, 200);
        $allergies = (new PatientAllergyRepository($pdo))->listByPatient($clinicId, $patientId, 200);
        $conditions = (new PatientConditionRepository($pdo))->listByPatient($clinicId, $patientId, 200);

        $imgRepo = new MedicalImageRepository($pdo);
        $images = $imgRepo->listByPatient($clinicId, $patientId, 20);
        $imagePairs = $imgRepo->listComparisonPairsByPatient($clinicId, $patientId, 20);

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

        (new SensitiveDataAuditService($this->container))->access(
            'sensitive.access',
            'patient',
            $patientId,
            ['module' => 'medical_records', 'action' => 'view_timeline', 'patient_id' => $patientId],
            $ip,
            $userAgent
        );

        return [
            'patient' => $patient,
            'records' => $records,
            'alerts' => $alerts,
            'allergies' => $allergies,
            'conditions' => $conditions,
            'images' => $images,
            'image_pairs' => $imagePairs,
        ];
    }

    /**
     * @param array{template_id?:?int,professional_id?:?int,date_from?:?string,date_to?:?string} $filters
     * @return array{patient:array<string,mixed>,records:list<array<string,mixed>>,alerts:list<array<string,mixed>>,allergies:list<array<string,mixed>>,conditions:list<array<string,mixed>>,images:list<array<string,mixed>>,image_pairs:list<array<string,mixed>>}
     */
    public function timelineFiltered(int $patientId, array $filters, string $ip, ?string $userAgent = null): array
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
        $records = $repo->listByPatientFiltered($clinicId, $patientId, $filters, 200);

        $alerts = (new PatientClinicalAlertRepository($pdo))->listByPatient($clinicId, $patientId, 200);
        $allergies = (new PatientAllergyRepository($pdo))->listByPatient($clinicId, $patientId, 200);
        $conditions = (new PatientConditionRepository($pdo))->listByPatient($clinicId, $patientId, 200);

        $imgRepo = new MedicalImageRepository($pdo);
        $images = $imgRepo->listByPatient($clinicId, $patientId, 20);
        $imagePairs = $imgRepo->listComparisonPairsByPatient($clinicId, $patientId, 20);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log(
            $actorId,
            $clinicId,
            'medical_records.view',
            ['patient_id' => $patientId, 'filters' => $filters],
            $ip,
            $roleCodes,
            'patient',
            $patientId,
            $userAgent
        );

        (new SensitiveDataAuditService($this->container))->access(
            'sensitive.access',
            'patient',
            $patientId,
            ['module' => 'medical_records', 'action' => 'view_timeline_filtered', 'patient_id' => $patientId, 'filters' => $filters],
            $ip,
            $userAgent
        );

        return [
            'patient' => $patient,
            'records' => $records,
            'alerts' => $alerts,
            'allergies' => $allergies,
            'conditions' => $conditions,
            'images' => $images,
            'image_pairs' => $imagePairs,
        ];
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

    /** @param array{professional_id:?int,attended_at:string,procedure_type:?string,template_id?:?int,fields?:array<string,mixed>,clinical_description:?string,clinical_evolution:?string,notes:?string} $data */
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

        $templateId = array_key_exists('template_id', $data) && is_int($data['template_id']) && $data['template_id'] > 0 ? $data['template_id'] : null;
        $fields = array_key_exists('fields', $data) && is_array($data['fields']) ? $data['fields'] : [];

        $tplNameSnapshot = null;
        $tplUpdatedAtSnapshot = null;
        $tplFieldsSnapshotJson = null;
        $fieldsJson = null;

        if ($templateId !== null) {
            $tplRepo = new MedicalRecordTemplateRepository($pdo);
            $tpl = $tplRepo->findById($clinicId, $templateId);
            if ($tpl === null) {
                throw new \RuntimeException('Template inválido.');
            }

            $fieldRepo = new MedicalRecordTemplateFieldRepository($pdo);
            $tplFields = $fieldRepo->listByTemplate($clinicId, $templateId);

            $this->validateRequiredFields($tplFields, $fields);

            $tplNameSnapshot = isset($tpl['name']) ? (string)$tpl['name'] : null;
            $tplUpdatedAtSnapshot = isset($tpl['updated_at']) && $tpl['updated_at'] !== null ? (string)$tpl['updated_at'] : null;
            $tplFieldsSnapshotJson = json_encode($tplFields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($tplFieldsSnapshotJson === false) {
                $tplFieldsSnapshotJson = null;
            }

            $fieldsJson = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($fieldsJson === false) {
                throw new \RuntimeException('Falha ao salvar campos.');
            }
        }

        $repo = new MedicalRecordRepository($pdo);
        $id = $repo->create(
            $clinicId,
            $patientId,
            $data['professional_id'],
            $data['attended_at'],
            $data['procedure_type'],
            $templateId,
            $tplNameSnapshot,
            $tplUpdatedAtSnapshot,
            $tplFieldsSnapshotJson,
            $fieldsJson,
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

    /** @param array{professional_id:?int,attended_at:string,procedure_type:?string,template_id?:?int,fields?:array<string,mixed>,clinical_description:?string,clinical_evolution:?string,notes:?string} $data */
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

        $templateId = array_key_exists('template_id', $data) && is_int($data['template_id']) && $data['template_id'] > 0 ? $data['template_id'] : null;
        $fields = array_key_exists('fields', $data) && is_array($data['fields']) ? $data['fields'] : [];

        $tplNameSnapshot = null;
        $tplUpdatedAtSnapshot = null;
        $tplFieldsSnapshotJson = null;
        $fieldsJson = null;

        if ($templateId !== null) {
            $tplRepo = new MedicalRecordTemplateRepository($pdo);
            $tpl = $tplRepo->findById($clinicId, $templateId);
            if ($tpl === null) {
                throw new \RuntimeException('Template inválido.');
            }

            $fieldRepo = new MedicalRecordTemplateFieldRepository($pdo);
            $tplFields = $fieldRepo->listByTemplate($clinicId, $templateId);

            $this->validateRequiredFields($tplFields, $fields);

            $tplNameSnapshot = isset($tpl['name']) ? (string)$tpl['name'] : null;
            $tplUpdatedAtSnapshot = isset($tpl['updated_at']) && $tpl['updated_at'] !== null ? (string)$tpl['updated_at'] : null;
            $tplFieldsSnapshotJson = json_encode($tplFields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($tplFieldsSnapshotJson === false) {
                $tplFieldsSnapshotJson = null;
            }

            $fieldsJson = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($fieldsJson === false) {
                throw new \RuntimeException('Falha ao salvar campos.');
            }
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
                $templateId,
                $tplNameSnapshot,
                $tplUpdatedAtSnapshot,
                $tplFieldsSnapshotJson,
                $fieldsJson,
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

    /**
     * @param list<array<string,mixed>> $templateFields
     * @param array<string,mixed> $values
     */
    private function validateRequiredFields(array $templateFields, array $values): void
    {
        foreach ($templateFields as $f) {
            $required = (int)($f['required'] ?? 0) === 1;
            if (!$required) {
                continue;
            }

            $key = trim((string)($f['field_key'] ?? ''));
            $label = trim((string)($f['label'] ?? $key));
            if ($key === '') {
                continue;
            }

            if (!array_key_exists($key, $values)) {
                throw new \RuntimeException('Campo obrigatório: ' . ($label !== '' ? $label : $key));
            }

            $v = $values[$key];
            if (is_string($v) && trim($v) === '') {
                throw new \RuntimeException('Campo obrigatório: ' . ($label !== '' ? $label : $key));
            }
            if (is_array($v) && $v === []) {
                throw new \RuntimeException('Campo obrigatório: ' . ($label !== '' ? $label : $key));
            }
        }
    }
}
