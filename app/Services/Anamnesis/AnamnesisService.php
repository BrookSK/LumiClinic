<?php

declare(strict_types=1);

namespace App\Services\Anamnesis;

use App\Core\Container\Container;
use App\Repositories\AnamnesisFieldRepository;
use App\Repositories\AnamnesisResponseRepository;
use App\Repositories\AnamnesisTemplateRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientRepository;
use App\Repositories\ProfessionalRepository;
use App\Services\Auth\AuthService;

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
    public function listForPatient(int $patientId, string $ip): array
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
        $audit->log($actorId, $clinicId, 'anamnesis.view', ['patient_id' => $patientId], $ip);

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
    public function submit(int $patientId, int $templateId, ?int $professionalId, array $answers, string $ip): int
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
        if ($tplRepo->findById($clinicId, $templateId) === null) {
            throw new \RuntimeException('Template inválido.');
        }

        $answersJson = json_encode($answers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($answersJson === false) {
            throw new \RuntimeException('Falha ao salvar respostas.');
        }

        $repo = new AnamnesisResponseRepository($pdo);
        $id = $repo->create($clinicId, $patientId, $templateId, $professionalId, $answersJson, $actorId);

        $audit = new AuditLogRepository($pdo);
        $audit->log($actorId, $clinicId, 'anamnesis.fill', [
            'anamnesis_response_id' => $id,
            'patient_id' => $patientId,
            'template_id' => $templateId,
        ], $ip);

        return $id;
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
