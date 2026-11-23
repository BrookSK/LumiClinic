<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Billing\PilotProgramService;
use App\Services\System\SystemClinicService;

final class SystemClinicController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
            throw new \RuntimeException('Acesso negado.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemClinicService($this->container);

        $q = trim((string)$request->input('q', ''));

        return $this->view('system/clinics/index', [
            'items' => $service->listClinics($q),
            'q' => $q,
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function impersonate(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/sys/clinics');
        }

        try {
            (new AuthService($this->container))->impersonateClinicOwner($id, $request->ip(), $request->header('user-agent'));
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'Acesso negado.') {
                throw $e;
            }
            return $this->redirect('/sys/clinics?error=' . urlencode($e->getMessage()));
        }

        return $this->redirect('/');
    }

    public function create(Request $request)
    {
        $this->ensureSuperAdmin();

        return $this->view('system/clinics/create');
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin();

        $clinicName = trim((string)$request->input('clinic_name', ''));
        $tenantKey = trim((string)$request->input('tenant_key', ''));
        $primaryDomain = trim((string)$request->input('primary_domain', ''));

        $ownerName = trim((string)$request->input('owner_name', ''));
        $ownerEmail = strtolower(trim((string)$request->input('owner_email', '')));
        $ownerPassword = (string)$request->input('owner_password', '');

        if ($clinicName === '' || $ownerName === '' || $ownerEmail === '' || $ownerPassword === '') {
            return $this->view('system/clinics/create', ['error' => 'Preencha todos os campos obrigatórios.']);
        }

        if (!filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->view('system/clinics/create', ['error' => 'E-mail inválido.']);
        }

        if (strlen($ownerPassword) < 8) {
            return $this->view('system/clinics/create', ['error' => 'Senha deve ter pelo menos 8 caracteres.']);
        }

        // Clinic contact fields
        $clinicContactFields = [
            'contact_email' => (string)$request->input('clinic_email', ''),
            'contact_phone' => (string)$request->input('clinic_phone', ''),
            'contact_whatsapp' => (string)$request->input('clinic_whatsapp', ''),
            'contact_website' => (string)$request->input('clinic_website', ''),
            'contact_instagram' => (string)$request->input('clinic_instagram', ''),
            'contact_facebook' => (string)$request->input('clinic_facebook', ''),
            'address_street' => (string)$request->input('clinic_street', ''),
            'address_number' => (string)$request->input('clinic_number', ''),
            'address_complement' => (string)$request->input('clinic_complement', ''),
            'address_neighborhood' => (string)$request->input('clinic_neighborhood', ''),
            'address_city' => (string)$request->input('clinic_city', ''),
            'address_state' => (string)$request->input('clinic_state', ''),
            'address_zip' => preg_replace('/\D+/', '', (string)$request->input('clinic_zip', '')),
        ];

        // Owner/contratante fields
        $ownerFields = [
            'owner_name' => $ownerName,
            'owner_phone' => preg_replace('/\D+/', '', (string)$request->input('owner_phone', '')),
            'owner_doc_type' => (string)$request->input('owner_doc_type', 'cpf'),
            'owner_postal_code' => preg_replace('/\D+/', '', (string)$request->input('owner_postal_code', '')),
            'owner_street' => (string)$request->input('owner_street', ''),
            'owner_number' => (string)$request->input('owner_number', ''),
            'owner_complement' => (string)$request->input('owner_complement', ''),
            'owner_neighborhood' => (string)$request->input('owner_neighborhood', ''),
            'owner_city' => (string)$request->input('owner_city', ''),
            'owner_state' => (string)$request->input('owner_state', ''),
        ];

        $cnpj = preg_replace('/\D+/', '', (string)$request->input('owner_doc_number', ''));

        try {
            $service = new SystemClinicService($this->container);
            $service->createClinicWithOwner(
                $clinicName,
                ($tenantKey === '' ? null : $tenantKey),
                ($primaryDomain === '' ? null : $primaryDomain),
                $ownerName,
                $ownerEmail,
                $ownerPassword,
                $request->ip(),
                $cnpj !== '' ? $cnpj : null,
                $ownerFields,
                $clinicContactFields
            );

            return $this->redirect('/sys/clinics');
        } catch (\RuntimeException $e) {
            return $this->view('system/clinics/create', ['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $this->view('system/clinics/create', ['error' => 'Falha ao criar clínica.']);
        }
    }

    public function edit(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/sys/clinics');
        }

        $notices = [
            'enrolled' => 'Clínica marcada como piloto. O desconto já vale; se ela já tem assinatura no Asaas, o valor cobrado foi atualizado.',
            'unenrolled' => 'Clínica removida do programa piloto. Se ela tem assinatura no Asaas, o valor cheio voltou a ser cobrado.',
            'gateway' => 'Programa piloto salvo, mas não foi possível atualizar o valor no Asaas agora. O cron do servidor tenta de novo automaticamente.',
        ];
        $notice = $notices[(string)$request->input('pilot_notice', '')] ?? null;

        return $this->renderEdit($id, null, $notice);
    }

    private function renderEdit(int $id, ?string $error, ?string $notice = null)
    {
        $clinic = (new SystemClinicService($this->container))->getClinic($id);
        if ($clinic === null) {
            return $this->redirect('/sys/clinics');
        }

        return $this->view('system/clinics/edit', [
            'clinic' => $clinic,
            'pilot' => (new PilotProgramService($this->container))->adminInfo($id),
            'error' => $error,
            'notice' => $notice,
        ]);
    }

    public function update(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/sys/clinics');
        }

        $name = trim((string)$request->input('name', ''));
        $tenantKey = trim((string)$request->input('tenant_key', ''));
        $primaryDomain = trim((string)$request->input('primary_domain', ''));
        $cnpj = trim((string)$request->input('cnpj', ''));

        $ownerFields = [
            'owner_name' => (string)$request->input('owner_name', ''),
            'owner_phone' => preg_replace('/\D+/', '', (string)$request->input('owner_phone', '')),
            'owner_doc_type' => (string)$request->input('owner_doc_type', 'cpf'),
            'owner_postal_code' => preg_replace('/\D+/', '', (string)$request->input('owner_postal_code', '')),
            'owner_street' => (string)$request->input('owner_street', ''),
            'owner_number' => (string)$request->input('owner_number', ''),
            'owner_complement' => (string)$request->input('owner_complement', ''),
            'owner_neighborhood' => (string)$request->input('owner_neighborhood', ''),
            'owner_city' => (string)$request->input('owner_city', ''),
            'owner_state' => (string)$request->input('owner_state', ''),
        ];

        $clinicContactFields = [
            'contact_email' => (string)$request->input('clinic_email', ''),
            'contact_phone' => (string)$request->input('clinic_phone', ''),
            'contact_whatsapp' => (string)$request->input('clinic_whatsapp', ''),
            'contact_website' => (string)$request->input('clinic_website', ''),
            'contact_instagram' => (string)$request->input('clinic_instagram', ''),
            'contact_facebook' => (string)$request->input('clinic_facebook', ''),
            'address_street' => (string)$request->input('clinic_street', ''),
            'address_number' => (string)$request->input('clinic_number', ''),
            'address_complement' => (string)$request->input('clinic_complement', ''),
            'address_neighborhood' => (string)$request->input('clinic_neighborhood', ''),
            'address_city' => (string)$request->input('clinic_city', ''),
            'address_state' => (string)$request->input('clinic_state', ''),
            'address_zip' => preg_replace('/\D+/', '', (string)$request->input('clinic_zip', '')),
        ];

        if ($name === '') {
            return $this->renderEdit($id, 'Nome é obrigatório.');
        }

        try {
            (new SystemClinicService($this->container))->updateClinic(
                $id,
                $name,
                ($tenantKey === '' ? null : $tenantKey),
                ($primaryDomain === '' ? null : $primaryDomain),
                $request->ip(),
                ($cnpj === '' ? null : $cnpj),
                $ownerFields,
                $clinicContactFields
            );
        } catch (\RuntimeException $e) {
            return $this->renderEdit($id, $e->getMessage());
        }

        // Programa piloto (o campo só vem no formulário quando o banco já tem as colunas)
        if ((string)$request->input('pilot_field', '') === '1') {
            $isPilot = (string)$request->input('is_pilot', '0') === '1';
            try {
                $res = (new PilotProgramService($this->container))->setPilot($id, $isPilot, $request->ip());
            } catch (\RuntimeException $e) {
                return $this->renderEdit($id, 'Os dados da clínica foram salvos, mas a marcação de piloto não: ' . $e->getMessage());
            }

            if ($res['changed']) {
                $notice = !$res['gateway_ok'] ? 'gateway' : ($isPilot ? 'enrolled' : 'unenrolled');
                return $this->redirect('/sys/clinics/edit?id=' . $id . '&pilot_notice=' . $notice);
            }
        }

        return $this->redirect('/sys/clinics/edit?id=' . $id);
    }

    public function setStatus(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        $status = (string)$request->input('status', '');
        if ($id <= 0) {
            return $this->redirect('/sys/clinics');
        }

        try {
            (new SystemClinicService($this->container))->setStatus($id, $status, $request->ip());
        } catch (\RuntimeException $e) {
            return $this->redirect('/sys/clinics?error=' . urlencode($e->getMessage()));
        }

        return $this->redirect('/sys/clinics');
    }

    public function delete(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/sys/clinics');
        }

        (new SystemClinicService($this->container))->deleteClinic($id, $request->ip());
        return $this->redirect('/sys/clinics');
    }
}
