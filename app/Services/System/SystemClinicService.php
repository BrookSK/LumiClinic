<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\SystemClinicRepository;

final class SystemClinicService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string, mixed>> */
    public function listClinics(string $q = ''): array
    {
        $repo = new SystemClinicRepository($this->container->get(\PDO::class));
        $q = trim($q);
        if ($q === '') {
            return $repo->listAll();
        }
        return $repo->search($q, 250);
    }

    public function createClinicWithOwner(
        string $clinicName,
        ?string $tenantKey,
        ?string $primaryDomain,
        string $ownerName,
        string $ownerEmail,
        string $ownerPassword,
        string $ip,
        ?string $cnpj = null,
        array $ownerFields = [],
        array $clinicContactFields = []
    ): void {
        $pdo = $this->container->get(\PDO::class);
        $pdo->beginTransaction();

        try {
            $repo = new SystemClinicRepository($pdo);

            $clinicId = $repo->createClinic($clinicName, $tenantKey);
            $repo->createClinicDefaults($clinicId);

            // Save CNPJ and owner fields on clinic
            $sets = [];
            $params = ['cid' => $clinicId];
            if ($cnpj !== null && $cnpj !== '') {
                $sets[] = 'cnpj = :cnpj';
                $params['cnpj'] = $cnpj;
            }
            $ownerMap = ['owner_name','owner_phone','owner_doc_type','owner_postal_code','owner_street','owner_number','owner_complement','owner_neighborhood','owner_city','owner_state'];
            foreach ($ownerMap as $col) {
                if (array_key_exists($col, $ownerFields) && trim((string)$ownerFields[$col]) !== '') {
                    $sets[] = "$col = :$col";
                    $params[$col] = trim((string)$ownerFields[$col]);
                }
            }
            if (!empty($sets)) {
                $pdo->prepare('UPDATE clinics SET ' . implode(', ', $sets) . ' WHERE id = :cid')->execute($params);
            }

            // Save clinic contact fields
            $contactMap = ['contact_email','contact_phone','contact_whatsapp','contact_address','contact_website','contact_instagram','contact_facebook','address_street','address_number','address_complement','address_neighborhood','address_city','address_state','address_zip'];
            $cSets = [];
            $cParams = ['ccid' => $clinicId];
            foreach ($contactMap as $col) {
                if (array_key_exists($col, $clinicContactFields) && trim((string)$clinicContactFields[$col]) !== '') {
                    $cSets[] = "$col = :$col";
                    $cParams[$col] = trim((string)$clinicContactFields[$col]);
                }
            }
            if (!empty($cSets)) {
                $pdo->prepare('UPDATE clinics SET ' . implode(', ', $cSets) . ' WHERE id = :ccid')->execute($cParams);
            }

            $primaryDomain = $primaryDomain !== null ? strtolower(trim($primaryDomain)) : null;
            if ($primaryDomain === '') {
                $primaryDomain = null;
            }
            if ($primaryDomain !== null) {
                $repo->createPrimaryDomain($clinicId, $primaryDomain);
            }

            $ownerPasswordHash = password_hash($ownerPassword, PASSWORD_BCRYPT);
            if ($ownerPasswordHash === false) {
                throw new \RuntimeException('Falha ao gerar hash de senha.');
            }

            $ownerUserId = $repo->createOwnerUser($clinicId, $ownerName, $ownerEmail, $ownerPasswordHash);
            $roleOwnerId = $repo->seedRbacAndReturnOwnerRoleId($clinicId);
            $repo->assignRole($clinicId, $ownerUserId, $roleOwnerId);

            // Save billing profile on the owner user
            $userBillingFields = [];
            if (!empty($ownerFields['owner_phone'])) $userBillingFields['phone'] = $ownerFields['owner_phone'];
            if (!empty($ownerFields['owner_doc_type'])) $userBillingFields['doc_type'] = $ownerFields['owner_doc_type'];
            if ($cnpj !== null && $cnpj !== '') $userBillingFields['doc_number'] = $cnpj;
            if (!empty($ownerFields['owner_postal_code'])) $userBillingFields['postal_code'] = $ownerFields['owner_postal_code'];
            if (!empty($ownerFields['owner_street'])) $userBillingFields['address_street'] = $ownerFields['owner_street'];
            if (!empty($ownerFields['owner_number'])) $userBillingFields['address_number'] = $ownerFields['owner_number'];
            if (!empty($ownerFields['owner_complement'])) $userBillingFields['address_complement'] = $ownerFields['owner_complement'];
            if (!empty($ownerFields['owner_neighborhood'])) $userBillingFields['address_neighborhood'] = $ownerFields['owner_neighborhood'];
            if (!empty($ownerFields['owner_city'])) $userBillingFields['address_city'] = $ownerFields['owner_city'];
            if (!empty($ownerFields['owner_state'])) $userBillingFields['address_state'] = $ownerFields['owner_state'];
            if (!empty($userBillingFields)) {
                (new \App\Repositories\UserRepository($pdo))->updateBillingProfile($ownerUserId, $userBillingFields);
            }

            $audit = new AuditLogRepository($pdo);
            $audit->log((int)($_SESSION['user_id'] ?? null), null, 'system.clinics.create', ['clinic_id' => $clinicId, 'tenant_key' => $tenantKey, 'primary_domain' => $primaryDomain], $ip);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @return array{clinic_id:int,owner_user_id:int} */
    public function createClinicWithOwnerAndReturnIds(
        string $clinicName,
        ?string $tenantKey,
        ?string $primaryDomain,
        string $ownerName,
        string $ownerEmail,
        string $ownerPassword,
        string $ip,
        ?string $cnpj = null,
        array $ownerFields = [],
        array $clinicContactFields = []
    ): array {
        $pdo = $this->container->get(\PDO::class);
        $pdo->beginTransaction();

        try {
            $repo = new SystemClinicRepository($pdo);

            $clinicId = $repo->createClinic($clinicName, $tenantKey);
            $repo->createClinicDefaults($clinicId);

            // Save CNPJ and owner fields on clinic
            $sets = [];
            $params = ['cid' => $clinicId];
            if ($cnpj !== null && $cnpj !== '') {
                $sets[] = 'cnpj = :cnpj';
                $params['cnpj'] = $cnpj;
            }
            $ownerMap = ['owner_name','owner_phone','owner_doc_type','owner_postal_code','owner_street','owner_number','owner_complement','owner_neighborhood','owner_city','owner_state'];
            foreach ($ownerMap as $col) {
                if (array_key_exists($col, $ownerFields) && trim((string)$ownerFields[$col]) !== '') {
                    $sets[] = "$col = :$col";
                    $params[$col] = trim((string)$ownerFields[$col]);
                }
            }
            if (!empty($sets)) {
                $pdo->prepare('UPDATE clinics SET ' . implode(', ', $sets) . ' WHERE id = :cid')->execute($params);
            }

            // Save clinic contact fields
            $contactMap = ['contact_email','contact_phone','contact_whatsapp','contact_address','contact_website','contact_instagram','contact_facebook','address_street','address_number','address_complement','address_neighborhood','address_city','address_state','address_zip'];
            $cSets = [];
            $cParams = ['ccid' => $clinicId];
            foreach ($contactMap as $col) {
                if (array_key_exists($col, $clinicContactFields) && trim((string)$clinicContactFields[$col]) !== '') {
                    $cSets[] = "$col = :$col";
                    $cParams[$col] = trim((string)$clinicContactFields[$col]);
                }
            }
            if (!empty($cSets)) {
                $pdo->prepare('UPDATE clinics SET ' . implode(', ', $cSets) . ' WHERE id = :ccid')->execute($cParams);
            }

            $primaryDomain = $primaryDomain !== null ? strtolower(trim($primaryDomain)) : null;
            if ($primaryDomain === '') {
                $primaryDomain = null;
            }
            if ($primaryDomain !== null) {
                $repo->createPrimaryDomain($clinicId, $primaryDomain);
            }

            $ownerPasswordHash = password_hash($ownerPassword, PASSWORD_BCRYPT);
            if ($ownerPasswordHash === false) {
                throw new \RuntimeException('Falha ao gerar hash de senha.');
            }

            $ownerUserId = $repo->createOwnerUser($clinicId, $ownerName, $ownerEmail, $ownerPasswordHash);
            $roleOwnerId = $repo->seedRbacAndReturnOwnerRoleId($clinicId);
            $repo->assignRole($clinicId, $ownerUserId, $roleOwnerId);

            // Save billing profile on the owner user
            $userBillingFields = [];
            if (!empty($ownerFields['owner_phone'])) $userBillingFields['phone'] = $ownerFields['owner_phone'];
            if (!empty($ownerFields['owner_doc_type'])) $userBillingFields['doc_type'] = $ownerFields['owner_doc_type'];
            if ($cnpj !== null && $cnpj !== '') $userBillingFields['doc_number'] = $cnpj;
            if (!empty($userBillingFields)) {
                (new \App\Repositories\UserRepository($pdo))->updateBillingProfile($ownerUserId, $userBillingFields);
            }

            $audit = new AuditLogRepository($pdo);
            $audit->log(null, null, 'public.clinic_signup.create', ['clinic_id' => $clinicId, 'tenant_key' => $tenantKey, 'primary_domain' => $primaryDomain], $ip);

            $pdo->commit();

            return ['clinic_id' => $clinicId, 'owner_user_id' => $ownerUserId];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @return array<string,mixed>|null */
    public function getClinic(int $clinicId): ?array
    {
        return (new SystemClinicRepository($this->container->get(\PDO::class)))->findById($clinicId);
    }

    public function updateClinic(int $clinicId, string $name, ?string $tenantKey, ?string $primaryDomain, string $ip, ?string $cnpj = null, array $ownerFields = [], array $clinicContactFields = []): void
    {
        $pdo = $this->container->get(\PDO::class);
        $pdo->beginTransaction();

        try {
            $repo = new SystemClinicRepository($pdo);
            $repo->updateClinic($clinicId, $name, $tenantKey);

            // Update CNPJ and owner fields
            $sets = [];
            $params = ['id' => $clinicId];

            if ($cnpj !== null) {
                $cnpjClean = preg_replace('/\D+/', '', $cnpj);
                $sets[] = 'cnpj = :cnpj';
                $params['cnpj'] = $cnpjClean !== '' ? $cnpjClean : null;
            }

            $ownerMap = [
                'owner_name' => 'owner_name',
                'owner_phone' => 'owner_phone',
                'owner_doc_type' => 'owner_doc_type',
                'owner_postal_code' => 'owner_postal_code',
                'owner_street' => 'owner_street',
                'owner_number' => 'owner_number',
                'owner_complement' => 'owner_complement',
                'owner_neighborhood' => 'owner_neighborhood',
                'owner_city' => 'owner_city',
                'owner_state' => 'owner_state',
            ];

            foreach ($ownerMap as $field => $col) {
                if (array_key_exists($field, $ownerFields)) {
                    $val = trim((string)$ownerFields[$field]);
                    $sets[] = "$col = :$col";
                    $params[$col] = $val !== '' ? $val : null;
                }
            }

            if (!empty($sets)) {
                $sql = 'UPDATE clinics SET ' . implode(', ', $sets) . ' WHERE id = :id';
                $pdo->prepare($sql)->execute($params);
            }

            // Update clinic contact fields
            if (!empty($clinicContactFields)) {
                $contactMap = [
                    'contact_email' => 'contact_email',
                    'contact_phone' => 'contact_phone',
                    'contact_whatsapp' => 'contact_whatsapp',
                    'contact_address' => 'contact_address',
                    'contact_website' => 'contact_website',
                    'contact_instagram' => 'contact_instagram',
                    'contact_facebook' => 'contact_facebook',
                    'address_street' => 'address_street',
                    'address_number' => 'address_number',
                    'address_complement' => 'address_complement',
                    'address_neighborhood' => 'address_neighborhood',
                    'address_city' => 'address_city',
                    'address_state' => 'address_state',
                    'address_zip' => 'address_zip',
                ];
                $cSets = [];
                $cParams = ['cid' => $clinicId];
                foreach ($contactMap as $field => $col) {
                    if (array_key_exists($field, $clinicContactFields)) {
                        $val = trim((string)$clinicContactFields[$field]);
                        $cSets[] = "$col = :$col";
                        $cParams[$col] = $val !== '' ? $val : null;
                    }
                }
                if (!empty($cSets)) {
                    $sql = 'UPDATE clinics SET ' . implode(', ', $cSets) . ' WHERE id = :cid';
                    $pdo->prepare($sql)->execute($cParams);
                }
            }

            $primaryDomain = $primaryDomain !== null ? strtolower(trim($primaryDomain)) : null;
            if ($primaryDomain === '') {
                $primaryDomain = null;
            }
            if ($primaryDomain !== null) {
                $repo->updatePrimaryDomain($clinicId, $primaryDomain);
            }

            // Sync contratante data to the owner user so /me shows correct data
            if (!empty($ownerFields)) {
                $ownerStmt = $pdo->prepare("
                    SELECT u.id FROM users u
                    JOIN user_roles ur ON ur.user_id = u.id AND ur.clinic_id = u.clinic_id
                    JOIN roles r ON r.id = ur.role_id AND r.clinic_id = u.clinic_id AND r.code = 'owner'
                    WHERE u.clinic_id = ? AND u.deleted_at IS NULL
                    ORDER BY u.id LIMIT 1
                ");
                $ownerStmt->execute([$clinicId]);
                $ownerRow = $ownerStmt->fetch(\PDO::FETCH_ASSOC);
                if ($ownerRow) {
                    $userBilling = [];
                    if (!empty($ownerFields['owner_phone'])) $userBilling['phone'] = $ownerFields['owner_phone'];
                    if (!empty($ownerFields['owner_doc_type'])) $userBilling['doc_type'] = $ownerFields['owner_doc_type'];
                    if ($cnpj !== null && preg_replace('/\D+/', '', $cnpj) !== '') $userBilling['doc_number'] = preg_replace('/\D+/', '', $cnpj);
                    if (!empty($ownerFields['owner_postal_code'])) $userBilling['postal_code'] = $ownerFields['owner_postal_code'];
                    if (!empty($ownerFields['owner_street'])) $userBilling['address_street'] = $ownerFields['owner_street'];
                    if (!empty($ownerFields['owner_number'])) $userBilling['address_number'] = $ownerFields['owner_number'];
                    if (!empty($ownerFields['owner_complement'])) $userBilling['address_complement'] = $ownerFields['owner_complement'];
                    if (!empty($ownerFields['owner_neighborhood'])) $userBilling['address_neighborhood'] = $ownerFields['owner_neighborhood'];
                    if (!empty($ownerFields['owner_city'])) $userBilling['address_city'] = $ownerFields['owner_city'];
                    if (!empty($ownerFields['owner_state'])) $userBilling['address_state'] = $ownerFields['owner_state'];
                    if (!empty($ownerFields['owner_name'])) {
                        $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([trim($ownerFields['owner_name']), (int)$ownerRow['id']]);
                    }
                    if (!empty($userBilling)) {
                        (new \App\Repositories\UserRepository($pdo))->updateBillingProfile((int)$ownerRow['id'], $userBilling);
                    }
                }
            }

            (new AuditLogRepository($pdo))->log((int)($_SESSION['user_id'] ?? null), null, 'system.clinics.update', [
                'clinic_id' => $clinicId,
                'tenant_key' => $tenantKey,
                'primary_domain' => $primaryDomain,
            ], $ip);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function setStatus(int $clinicId, string $status, string $ip): void
    {
        $allowed = ['active', 'inactive'];
        if (!in_array($status, $allowed, true)) {
            throw new \RuntimeException('Status inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        (new SystemClinicRepository($pdo))->setStatus($clinicId, $status);

        (new AuditLogRepository($pdo))->log((int)($_SESSION['user_id'] ?? null), null, 'system.clinics.set_status', [
            'clinic_id' => $clinicId,
            'status' => $status,
        ], $ip);
    }

    public function deleteClinic(int $clinicId, string $ip): void
    {
        $pdo = $this->container->get(\PDO::class);

        // 1. Cancel gateway subscription if exists
        try {
            $gwService = new \App\Services\Billing\BillingGatewayService($this->container);
            $gwService->cancelGatewaySubscription($clinicId);
        } catch (\Throwable $ignore) {
            // Best effort - continue even if gateway cancel fails
        }

        // 2. Delete all clinic data (order matters due to FK constraints)
        $pdo->beginTransaction();
        try {
            // Disable FK checks to allow deletion in any order
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

            $tables = [
                // Billing & subscriptions
                'billing_events' => 'clinic_id',
                'clinic_subscriptions' => 'clinic_id',
                // Marketing
                'marketing_automation_logs' => 'clinic_id',
                'marketing_automation_campaigns' => 'clinic_id',
                'marketing_automation_segments' => 'clinic_id',
                'marketing_calendar_entries' => 'clinic_id',
                // Stock
                'stock_inventory_items' => 'clinic_id',
                'stock_inventories' => 'clinic_id',
                'service_material_defaults' => 'clinic_id',
                'stock_movements' => 'clinic_id',
                'materials' => 'clinic_id',
                // Finance
                'accounts_payable_installments' => 'clinic_id',
                'accounts_payable' => 'clinic_id',
                'financial_entry_logs' => 'clinic_id',
                'financial_entries' => 'clinic_id',
                'payments' => 'clinic_id',
                'sale_items' => 'clinic_id',
                'sale_logs' => 'clinic_id',
                'sales' => 'clinic_id',
                'cost_centers' => 'clinic_id',
                // Patient procedures & packages
                'appointment_package_sessions' => 'clinic_id',
                'patient_procedures' => 'clinic_id',
                'patient_packages' => 'clinic_id',
                'patient_subscriptions' => 'clinic_id',
                // Appointments
                'appointment_materials_used' => 'clinic_id',
                'appointment_anamnesis_requests' => 'clinic_id',
                'appointment_confirmation_tokens' => 'clinic_id',
                'appointment_logs' => 'clinic_id',
                'consultations' => 'clinic_id',
                'appointments' => 'clinic_id',
                'scheduling_blocks' => 'clinic_id',
                'professional_schedules' => 'clinic_id',
                // Medical
                'medical_record_audio_notes' => 'clinic_id',
                'consultation_attachments' => 'clinic_id',
                'medical_images' => 'clinic_id',
                'medical_records' => 'clinic_id',
                'prescriptions' => 'clinic_id',
                // Anamnesis
                'anamnesis_response_snapshots' => 'clinic_id',
                'anamnesis_responses' => 'clinic_id',
                'anamnesis_template_fields' => 'clinic_id',
                'anamnesis_templates' => 'clinic_id',
                // Consent & legal
                'consent_acceptances' => 'clinic_id',
                'consent_terms' => 'clinic_id',
                'legal_signatures' => 'clinic_id',
                'legal_document_versions' => 'clinic_id',
                'legal_documents' => 'clinic_id',
                // Patient data
                'patient_clinical_sheet_alerts' => 'clinic_id',
                'patient_clinical_sheet_conditions' => 'clinic_id',
                'patient_clinical_sheet_allergies' => 'clinic_id',
                'patient_clinical_sheets' => 'clinic_id',
                'patient_documents' => 'clinic_id',
                'patient_events' => 'clinic_id',
                'patient_notifications' => 'clinic_id',
                'patient_web_push_subscriptions' => 'clinic_id',
                'patient_api_tokens' => 'clinic_id',
                'patient_content' => 'clinic_id',
                'patient_portal_access' => 'clinic_id',
                'patient_profile_change_requests' => 'clinic_id',
                'patient_lgpd_requests' => 'clinic_id',
                'lgpd_data_exports' => 'clinic_id',
                'patients' => 'clinic_id',
                // Services & procedures
                'procedure_protocol_steps' => 'clinic_id',
                'procedure_protocols' => 'clinic_id',
                'procedures' => 'clinic_id',
                'service_categories' => 'clinic_id',
                'services' => 'clinic_id',
                // Professionals
                'professionals' => 'clinic_id',
                // RBAC
                'user_permissions_override' => 'clinic_id',
                'user_roles' => 'clinic_id',
                'role_permissions' => 'clinic_id',
                'permissions' => 'clinic_id',
                'roles' => 'clinic_id',
                // Users
                'users' => 'clinic_id',
                // Compliance
                'compliance_controls' => 'clinic_id',
                'compliance_policies' => 'clinic_id',
                'security_incidents' => 'clinic_id',
                // Settings & config
                'whatsapp_message_logs' => 'clinic_id',
                'whatsapp_templates' => 'clinic_id',
                'clinic_google_calendar_oauth' => 'clinic_id',
                'clinic_terminology' => 'clinic_id',
                'clinic_settings' => 'clinic_id',
                'clinic_domains' => 'clinic_id',
                // Audit & logs
                'audit_logs' => 'clinic_id',
                // Import logs
                'clinicorp_import_logs' => 'clinic_id',
                // BI
                'bi_snapshots' => 'clinic_id',
                // The clinic itself
                'clinics' => 'id',
            ];

            foreach ($tables as $table => $col) {
                try {
                    $pdo->exec("DELETE FROM `$table` WHERE `$col` = $clinicId");
                } catch (\Throwable $ignore) {
                    // Table may not exist yet, skip
                }
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new \RuntimeException('Falha ao excluir clínica: ' . $e->getMessage());
        }
    }
}
