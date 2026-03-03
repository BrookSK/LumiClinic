<?php

declare(strict_types=1);

namespace App\Services\MedicalRecords;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\MedicalRecordTemplateFieldRepository;
use App\Repositories\MedicalRecordTemplateRepository;
use App\Services\Auth\AuthService;

final class MedicalRecordTemplateService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function listTemplates(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        return (new MedicalRecordTemplateRepository($this->container->get(\PDO::class)))->listByClinic($clinicId);
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
        $tplRepo = new MedicalRecordTemplateRepository($pdo);
        $tpl = $tplRepo->findById($clinicId, $templateId);
        if ($tpl === null) {
            throw new \RuntimeException('Template inválido.');
        }

        $fieldsRepo = new MedicalRecordTemplateFieldRepository($pdo);
        return ['template' => $tpl, 'fields' => $fieldsRepo->listByTemplate($clinicId, $templateId)];
    }

    /** @param list<array{field_key:string,label:string,field_type:string,required?:int,options:?array<int,string>,sort_order?:int}> $fields */
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

            $tplRepo = new MedicalRecordTemplateRepository($pdo);
            $templateId = $tplRepo->create($clinicId, $name);

            $fieldRepo = new MedicalRecordTemplateFieldRepository($pdo);
            $normalized = $this->normalizeFields($fields);
            if ($normalized !== []) {
                $fieldRepo->insertMany($clinicId, $templateId, $normalized);
            }

            $audit = new AuditLogRepository($pdo);
            $audit->log($actorId, $clinicId, 'medical_record_templates.create', ['template_id' => $templateId], $ip);

            $pdo->commit();
            return $templateId;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @param list<array{field_key:string,label:string,field_type:string,required?:int,options:?array<int,string>,sort_order?:int}> $fields */
    public function updateTemplate(int $templateId, string $name, string $status, array $fields, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if (!in_array($status, ['active', 'disabled'], true)) {
            $status = 'active';
        }

        $pdo = $this->container->get(\PDO::class);

        try {
            $pdo->beginTransaction();

            $tplRepo = new MedicalRecordTemplateRepository($pdo);
            if ($tplRepo->findById($clinicId, $templateId) === null) {
                throw new \RuntimeException('Template inválido.');
            }

            $tplRepo->update($clinicId, $templateId, $name, $status);

            $fieldRepo = new MedicalRecordTemplateFieldRepository($pdo);
            $fieldRepo->softDeleteByTemplate($clinicId, $templateId);

            $normalized = $this->normalizeFields($fields);
            if ($normalized !== []) {
                $fieldRepo->insertMany($clinicId, $templateId, $normalized);
            }

            $audit = new AuditLogRepository($pdo);
            $audit->log($actorId, $clinicId, 'medical_record_templates.update', ['template_id' => $templateId], $ip);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @param list<array{field_key:string,label:string,field_type:string,required?:int,options:?array<int,string>,sort_order?:int}> $fields */
    private function normalizeFields(array $fields): array
    {
        $out = [];
        $i = 0;
        foreach ($fields as $f) {
            $key = trim((string)($f['field_key'] ?? ''));
            $label = trim((string)($f['label'] ?? ''));
            $type = trim((string)($f['field_type'] ?? ''));
            $required = isset($f['required']) ? (int)$f['required'] : 0;

            if ($key === '' || $label === '') {
                continue;
            }
            if (!in_array($type, ['text', 'textarea', 'checkbox', 'select', 'number', 'date'], true)) {
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
                'required' => ($required ? 1 : 0),
                'options_json' => $optionsJson,
                'sort_order' => $sort,
            ];
            $i++;
        }

        return $out;
    }
}
