<?php

declare(strict_types=1);

namespace App\Services\Anamnesis;

use App\Core\Container\Container;
use App\Repositories\AnamnesisFieldRepository;
use App\Repositories\AnamnesisResponseRepository;
use App\Repositories\AnamnesisTemplateRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\DataVersionRepository;
use App\Repositories\PatientRepository;
use App\Repositories\ProfessionalRepository;
use App\Services\Auth\AuthService;
use App\Services\Compliance\DataExportService;
use App\Services\Compliance\SensitiveDataAuditService;

final class AnamnesisService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string, mixed>> */
    public function listTemplates(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new AnamnesisTemplateRepository($this->container->get(\PDO::class));
        return $repo->listActiveByClinic($clinicId);
    }

    /** @return array{template:array<string,mixed>,fields:list<array<string,mixed>>} */
    public function getTemplateWithFields(int $templateId): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $templates = new AnamnesisTemplateRepository($pdo);
        $tpl = $templates->findById($clinicId, $templateId);
        if ($tpl === null) {
            throw new \RuntimeException('Template inválido.');
        }

        $fields = new AnamnesisFieldRepository($pdo);
        return ['template' => $tpl, 'fields' => $fields->listByTemplate($clinicId, $templateId)];
    }

    /** @param list<array{field_key:string,label:string,field_type:string,options:?array<int,string>,sort_order?:int}> $fields */
    public function createTemplate(string $name, array $fields, string $ip): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        try {
            $pdo->beginTransaction();

            $tplRepo = new AnamnesisTemplateRepository($pdo);
            $templateId = $tplRepo->create($clinicId, $name);

            $fieldRepo = new AnamnesisFieldRepository($pdo);
            $normalized = $this->normalizeFields($fields);
            if ($normalized !== []) {
                $fieldRepo->insertMany($clinicId, $templateId, $normalized);
            }

            $audit = new AuditLogRepository($pdo);
            $audit->log($actorId, $clinicId, 'anamnesis.template_create', [
                'template_id' => $templateId,
            ], $ip);

            $pdo->commit();
            return $templateId;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @param list<array{field_key:string,label:string,field_type:string,options:?array<int,string>,sort_order?:int}> $fields */
    public function updateTemplate(int $templateId, string $name, string $status, array $fields, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        try {
            $pdo->beginTransaction();

            $tplRepo = new AnamnesisTemplateRepository($pdo);
            if ($tplRepo->findById($clinicId, $templateId) === null) {
                throw new \RuntimeException('Template inválido.');
            }

            $tplRepo->update($clinicId, $templateId, $name, $status);

            $fieldRepo = new AnamnesisFieldRepository($pdo);
            $fieldRepo->softDeleteByTemplate($clinicId, $templateId);

            $normalized = $this->normalizeFields($fields);
            if ($normalized !== []) {
                $fieldRepo->insertMany($clinicId, $templateId, $normalized);
            }

            $audit = new AuditLogRepository($pdo);
            $audit->log($actorId, $clinicId, 'anamnesis.template_update', [
                'template_id' => $templateId,
            ], $ip);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return array{patient:array<string,mixed>,templates:list<array<string,mixed>>,responses:list<array<string,mixed>>,professionals:list<array<string,mixed>>} */
    public function listForPatient(int $patientId, string $ip, ?string $userAgent = null): array
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

        $templates = new AnamnesisTemplateRepository($pdo);
        $responses = new AnamnesisResponseRepository($pdo);
        $profRepo = new ProfessionalRepository($pdo);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'anamnesis.view', ['patient_id' => $patientId], $ip, $roleCodes, 'patient', $patientId, $userAgent);

        (new SensitiveDataAuditService($this->container))->access(
            'sensitive.access',
            'patient',
            $patientId,
            ['module' => 'anamnesis', 'action' => 'list', 'patient_id' => $patientId],
            $ip,
            $userAgent
        );

        return [
            'patient' => $patient,
            'templates' => $templates->listActiveByClinic($clinicId),
            'responses' => $responses->listByPatient($clinicId, $patientId, 100),
            'professionals' => $profRepo->listActiveByClinic($clinicId),
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

    /** @param array<string,mixed> $answers */
    public function submit(int $patientId, int $templateId, ?int $professionalId, array $answers, string $ip, ?string $userAgent = null): int
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

        $tplRepo = new AnamnesisTemplateRepository($pdo);
        $tpl = $tplRepo->findById($clinicId, $templateId);
        if ($tpl === null) {
            throw new \RuntimeException('Template inválido.');
        }

        $fieldRepo = new AnamnesisFieldRepository($pdo);
        $fields = $fieldRepo->listByTemplate($clinicId, $templateId);

        $answersJson = json_encode($answers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($answersJson === false) {
            throw new \RuntimeException('Falha ao salvar respostas.');
        }

        $fieldsSnapshotJson = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($fieldsSnapshotJson === false) {
            $fieldsSnapshotJson = null;
        }

        $repo = new AnamnesisResponseRepository($pdo);
        $id = $repo->create(
            $clinicId,
            $patientId,
            $templateId,
            isset($tpl['name']) ? (string)$tpl['name'] : null,
            isset($tpl['updated_at']) && $tpl['updated_at'] !== null ? (string)$tpl['updated_at'] : null,
            $fieldsSnapshotJson,
            $professionalId,
            $answersJson,
            $actorId
        );

        (new DataVersionRepository($pdo))->record(
            $clinicId,
            'anamnesis_response',
            $id,
            'create',
            [
                'patient_id' => $patientId,
                'template_id' => $templateId,
                'professional_id' => $professionalId,
                'template_snapshot' => [
                    'name' => isset($tpl['name']) ? (string)$tpl['name'] : null,
                    'updated_at' => $tpl['updated_at'] ?? null,
                ],
                'fields_snapshot' => $fields,
            ],
            $actorId,
            $ip,
            $userAgent
        );

        $audit = new AuditLogRepository($pdo);
        $audit->log($actorId, $clinicId, 'anamnesis.fill', [
            'anamnesis_response_id' => $id,
            'patient_id' => $patientId,
            'template_id' => $templateId,
        ], $ip);

        return $id;
    }

    /** @return array{patient:array<string,mixed>,template:array<string,mixed>,fields:list<array<string,mixed>>,response:array<string,mixed>,answers:array<string,mixed>} */
    public function getExportData(int $responseId, string $ip, ?string $userAgent = null): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $repo = new AnamnesisResponseRepository($pdo);
        $response = $repo->findById($clinicId, $responseId);
        if ($response === null) {
            throw new \RuntimeException('Registro inválido.');
        }

        $patientId = (int)($response['patient_id'] ?? 0);
        $templateId = (int)($response['template_id'] ?? 0);
        if ($patientId <= 0 || $templateId <= 0) {
            throw new \RuntimeException('Registro inválido.');
        }

        $patients = new PatientRepository($pdo);
        $patient = $patients->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $tplRepo = new AnamnesisTemplateRepository($pdo);
        $tpl = $tplRepo->findById($clinicId, $templateId);
        if ($tpl === null) {
            $tpl = ['id' => $templateId, 'name' => null, 'status' => null, 'created_at' => null, 'updated_at' => null];
        }

        $fields = [];
        $fieldsRaw = (string)($response['fields_snapshot_json'] ?? '');
        if ($fieldsRaw !== '') {
            $decoded = json_decode($fieldsRaw, true);
            if (is_array($decoded)) {
                $fields = $decoded;
            }
        }
        if ($fields === []) {
            $fieldRepo = new AnamnesisFieldRepository($pdo);
            $fields = $fieldRepo->listByTemplate($clinicId, $templateId);
        }

        $answers = [];
        $raw = (string)($response['answers_json'] ?? '');
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $answers = $decoded;
            }
        }

        $audit = new AuditLogRepository($pdo);
        $audit->log($actorId, $clinicId, 'anamnesis.export', [
            'anamnesis_response_id' => $responseId,
            'patient_id' => $patientId,
            'template_id' => $templateId,
        ], $ip, null, 'patient', $patientId, $userAgent);

        (new DataExportService($this->container))->record(
            'anamnesis.export',
            'patient',
            $patientId,
            'html',
            null,
            [
                'anamnesis_response_id' => $responseId,
                'template_id' => $templateId,
            ],
            $ip,
            $userAgent
        );

        return [
            'patient' => $patient,
            'template' => $tpl,
            'fields' => $fields,
            'response' => $response,
            'answers' => $answers,
        ];
    }

    /** @param list<array{field_key:string,label:string,field_type:string,options:?array<int,string>,sort_order?:int}> $fields */
    private function normalizeFields(array $fields): array
    {
        $out = [];
        $i = 0;
        foreach ($fields as $f) {
            $key = trim((string)($f['field_key'] ?? ''));
            $label = trim((string)($f['label'] ?? ''));
            $type = trim((string)($f['field_type'] ?? ''));
            if ($key === '' || $label === '') {
                continue;
            }
            if (!in_array($type, ['text', 'textarea', 'checkbox', 'select'], true)) {
                $type = 'text';
            }

            $optionsJson = null;
            if ($type === 'select') {
                $opts = $f['options'] ?? [];
                if (is_array($opts) && $opts !== []) {
                    $optionsJson = json_encode(array_values($opts), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }

            $sort = isset($f['sort_order']) ? (int)$f['sort_order'] : $i;
            $out[] = [
                'field_key' => $key,
                'label' => $label,
                'field_type' => $type,
                'options_json' => $optionsJson,
                'sort_order' => $sort,
            ];
            $i++;
        }

        return $out;
    }
}
