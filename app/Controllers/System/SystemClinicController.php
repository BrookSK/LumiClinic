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
        $ownerEmail = trim((string)$request->input('owner_email', ''));
        $ownerPassword = (string)$request->input('owner_password', '');

        if ($clinicName === '' || $ownerName === '' || $ownerEmail === '' || $ownerPassword === '') {
            return $this->view('system/clinics/create', ['error' => 'Preencha todos os campos obrigatórios.']);
        }

        $service = new SystemClinicService($this->container);
        $service->createClinicWithOwner(
            $clinicName,
            ($tenantKey === '' ? null : $tenantKey),
            ($primaryDomain === '' ? null : $primaryDomain),
            $ownerName,
            $ownerEmail,
            $ownerPassword,
            $request->ip()
        );

        return $this->redirect('/sys/clinics');
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
                $request->ip()
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
