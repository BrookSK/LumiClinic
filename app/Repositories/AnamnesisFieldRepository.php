<?php

declare(strict_types=1);

namespace App\Repositories;

final class AnamnesisFieldRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listByTemplate(int $clinicId, int $templateId): array
    {
        $sql = "
            SELECT id, clinic_id, template_id, field_key, label, field_type, options_json, sort_order
            FROM anamnesis_template_fields
            WHERE clinic_id = :clinic_id
              AND template_id = :template_id
              AND deleted_at IS NULL
            ORDER BY sort_order ASC, id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'template_id' => $templateId,
        ]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    public function softDeleteByTemplate(int $clinicId, int $templateId): void
    {
        $sql = "
            UPDATE anamnesis_template_fields
            SET deleted_at = NOW()
            WHERE clinic_id = :clinic_id
              AND template_id = :template_id
              AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'template_id' => $templateId,
        ]);
    }

    /** @param list<array{field_key:string,label:string,field_type:string,options_json:?string,sort_order:int}> $fields */
    public function insertMany(int $clinicId, int $templateId, array $fields): void
    {
        $sql = "
            INSERT INTO anamnesis_template_fields (
                clinic_id, template_id,
                field_key, label, field_type, options_json, sort_order,
                created_at
            )
            VALUES (
                :clinic_id, :template_id,
                :field_key, :label, :field_type, :options_json, :sort_order,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($fields as $f) {
            $stmt->execute([
                'clinic_id' => $clinicId,
                'template_id' => $templateId,
                'field_key' => $f['field_key'],
                'label' => $f['label'],
                'field_type' => $f['field_type'],
                'options_json' => $f['options_json'],
                'sort_order' => $f['sort_order'],
            ]);
        }
    }
}
