<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
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
        ]);
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

        $service = new SystemClinicService($this->container);
        $clinic = $service->getClinic($id);
        if ($clinic === null) {
            return $this->redirect('/sys/clinics');
        }

        return $this->view('system/clinics/edit', [
            'clinic' => $clinic,
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
            $service = new SystemClinicService($this->container);
            $clinic = $service->getClinic($id);
            return $this->view('system/clinics/edit', [
                'clinic' => $clinic,
                'error' => 'Nome é obrigatório.',
            ]);
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
            return $this->redirect('/sys/clinics/edit?id=' . $id);
        } catch (\RuntimeException $e) {
            $service = new SystemClinicService($this->container);
            $clinic = $service->getClinic($id);
            return $this->view('system/clinics/edit', [
                'clinic' => $clinic,
                'error' => $e->getMessage(),
            ]);
        }
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
