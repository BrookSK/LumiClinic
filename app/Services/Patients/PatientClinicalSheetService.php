<?php

declare(strict_types=1);

namespace App\Services\Patients;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientAllergyRepository;
use App\Repositories\PatientClinicalAlertRepository;
use App\Repositories\PatientConditionRepository;
use App\Repositories\PatientRepository;
use App\Services\Auth\AuthService;
use App\Services\Compliance\SensitiveDataAuditService;

final class PatientClinicalSheetService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{patient:array<string,mixed>,allergies:list<array<string,mixed>>,conditions:list<array<string,mixed>>,alerts:list<array<string,mixed>>} */
    public function view(int $patientId, string $ip, ?string $userAgent = null): array
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

        $allergies = (new PatientAllergyRepository($pdo))->listByPatient($clinicId, $patientId, 200);
        $conditions = (new PatientConditionRepository($pdo))->listByPatient($clinicId, $patientId, 200);
        $alerts = (new PatientClinicalAlertRepository($pdo))->listByPatient($clinicId, $patientId, 200);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'patients.clinical_sheet.view', ['patient_id' => $patientId], $ip, $roleCodes, 'patient', $patientId, $userAgent);

        (new SensitiveDataAuditService($this->container))->access(
            'sensitive.access',
            'patient',
            $patientId,
            ['module' => 'patients', 'action' => 'clinical_sheet.view', 'patient_id' => $patientId],
            $ip,
            $userAgent
        );

        return [
            'patient' => $patient,
            'allergies' => $allergies,
            'conditions' => $conditions,
            'alerts' => $alerts,
        ];
    }

    public function createAllergy(
        int $patientId,
        string $type,
        string $triggerName,
        ?string $reaction,
        ?string $severity,
        ?string $notes,
        string $ip,
        ?string $userAgent = null
    ): int {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $type = strtolower(trim($type));
        if (!in_array($type, ['allergy', 'contraindication'], true)) {
            $type = 'allergy';
        }

        $triggerName = trim($triggerName);
        if ($triggerName === '') {
            throw new \RuntimeException('Item é obrigatório.');
        }

        $pdo = $this->container->get(\PDO::class);
        $patients = new PatientRepository($pdo);
        if ($patients->findById($clinicId, $patientId) === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $id = (new PatientAllergyRepository($pdo))->create(
            $clinicId,
            $patientId,
            $type,
            $triggerName,
            $reaction,
            $severity,
            $notes,
            $actorId
        );

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'patients.clinical_sheet.allergies.create', ['patient_id' => $patientId, 'id' => $id, 'type' => $type], $ip, $roleCodes, 'patient_allergy', $id, $userAgent);

        return $id;
    }

    public function deleteAllergy(int $id, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($id <= 0) {
            return;
        }

        $pdo = $this->container->get(\PDO::class);
        (new PatientAllergyRepository($pdo))->softDelete($clinicId, $id);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'patients.clinical_sheet.allergies.delete', ['id' => $id], $ip, $roleCodes, 'patient_allergy', $id, $userAgent);
    }

    public function createCondition(
        int $patientId,
        string $conditionName,
        string $status,
        ?string $onsetDate,
        ?string $notes,
        string $ip,
        ?string $userAgent = null
    ): int {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $conditionName = trim($conditionName);
        if ($conditionName === '') {
            throw new \RuntimeException('Condição é obrigatória.');
        }

        $status = strtolower(trim($status));
        if (!in_array($status, ['active', 'inactive', 'resolved'], true)) {
            $status = 'active';
        }

        $pdo = $this->container->get(\PDO::class);
        $patients = new PatientRepository($pdo);
        if ($patients->findById($clinicId, $patientId) === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $id = (new PatientConditionRepository($pdo))->create(
            $clinicId,
            $patientId,
            $conditionName,
            $status,
            $onsetDate,
            $notes,
            $actorId
        );

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'patients.clinical_sheet.conditions.create', ['patient_id' => $patientId, 'id' => $id, 'status' => $status], $ip, $roleCodes, 'patient_condition', $id, $userAgent);

        return $id;
    }

    public function deleteCondition(int $id, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($id <= 0) {
            return;
        }

        $pdo = $this->container->get(\PDO::class);
        (new PatientConditionRepository($pdo))->softDelete($clinicId, $id);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'patients.clinical_sheet.conditions.delete', ['id' => $id], $ip, $roleCodes, 'patient_condition', $id, $userAgent);
    }

    public function createAlert(
        int $patientId,
        string $title,
        ?string $details,
        string $severity,
        int $active,
        string $ip,
        ?string $userAgent = null
    ): int {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $title = trim($title);
        if ($title === '') {
            throw new \RuntimeException('Título é obrigatório.');
        }

        $severity = strtolower(trim($severity));
        if (!in_array($severity, ['info', 'warning', 'critical'], true)) {
            $severity = 'warning';
        }

        $active = $active === 1 ? 1 : 0;

        $pdo = $this->container->get(\PDO::class);
        $patients = new PatientRepository($pdo);
        if ($patients->findById($clinicId, $patientId) === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $id = (new PatientClinicalAlertRepository($pdo))->create(
            $clinicId,
            $patientId,
            $title,
            $details,
            $severity,
            $active,
            $actorId
        );

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'patients.clinical_sheet.alerts.create', ['patient_id' => $patientId, 'id' => $id, 'severity' => $severity, 'active' => $active], $ip, $roleCodes, 'patient_clinical_alert', $id, $userAgent);

        return $id;
    }

    public function deleteAlert(int $id, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($id <= 0) {
            return;
        }

        $pdo = $this->container->get(\PDO::class);
        (new PatientClinicalAlertRepository($pdo))->softDelete($clinicId, $id);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'patients.clinical_sheet.alerts.delete', ['id' => $id], $ip, $roleCodes, 'patient_clinical_alert', $id, $userAgent);
    }

    public function resolveAlert(int $id, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($id <= 0) {
            return;
        }

        $pdo = $this->container->get(\PDO::class);
        (new PatientClinicalAlertRepository($pdo))->resolve($clinicId, $id, $actorId);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'patients.clinical_sheet.alerts.resolve', ['id' => $id], $ip, $roleCodes, 'patient_clinical_alert', $id, $userAgent);
    }
}
