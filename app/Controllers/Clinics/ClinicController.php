<?php

declare(strict_types=1);

namespace App\Controllers\Clinics;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Clinics\ClinicService;
use App\Services\Auth\AuthService;

final class ClinicController extends Controller
{
    private function redirectSuperAdminWithoutClinicContext(): ?\App\Core\Http\Response
    {
        $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;
        if (!$isSuperAdmin) {
            return null;
        }

        $auth = new AuthService($this->container);
        if ($auth->clinicId() === null) {
            return $this->redirect('/sys/clinics');
        }

        return null;
    }

    public function edit(Request $request)
    {
        $this->authorize('clinics.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $service = new ClinicService($this->container);
        $clinic = $service->getCurrentClinic();

        return $this->view('clinics/edit', ['clinic' => $clinic]);
    }

    public function update(Request $request)
    {
        $this->authorize('clinics.update');

        $name = trim((string)$request->input('name', ''));
        $tenantKey = trim((string)$request->input('tenant_key', ''));
        if ($name === '') {
            return $this->view('clinics/edit', ['error' => 'Nome é obrigatório.']);
        }

        $service = new ClinicService($this->container);
        $service->updateClinicName($name, $request->ip());

        $service->updateTenantKey($tenantKey, $request->ip());

        return $this->redirect('/clinic');
    }

    public function workingHours(Request $request)
    {
        $this->authorize('clinics.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $service = new ClinicService($this->container);

        return $this->view('clinics/working-hours', [
            'items' => $service->listWorkingHours(),
        ]);
    }

    public function storeWorkingHour(Request $request)
    {
        $this->authorize('clinics.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $weekday = (int)$request->input('weekday', -1);
        $start = trim((string)$request->input('start_time', ''));
        $end = trim((string)$request->input('end_time', ''));

        if ($weekday < 0 || $weekday > 6 || $start === '' || $end === '') {
            $service = new ClinicService($this->container);
            return $this->view('clinics/working-hours', [
                'items' => $service->listWorkingHours(),
                'error' => 'Preencha todos os campos.',
            ]);
        }

        $service = new ClinicService($this->container);
        $service->createWorkingHour($weekday, $start, $end, $request->ip());

        return $this->redirect('/clinic/working-hours');
    }

    public function deleteWorkingHour(Request $request)
    {
        $this->authorize('clinics.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/clinic/working-hours');
        }

        $service = new ClinicService($this->container);
        $service->deleteWorkingHour($id, $request->ip());

        return $this->redirect('/clinic/working-hours');
    }

    public function closedDays(Request $request)
    {
        $this->authorize('clinics.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $service = new ClinicService($this->container);

        return $this->view('clinics/closed-days', [
            'items' => $service->listClosedDays(),
        ]);
    }

    public function storeClosedDay(Request $request)
    {
        $this->authorize('clinics.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $date = trim((string)$request->input('closed_date', ''));
        $reason = trim((string)$request->input('reason', ''));

        if ($date === '') {
            $service = new ClinicService($this->container);
            return $this->view('clinics/closed-days', [
                'items' => $service->listClosedDays(),
                'error' => 'Data é obrigatória.',
            ]);
        }

        $service = new ClinicService($this->container);
        $service->createClosedDay($date, $reason, $request->ip());

        return $this->redirect('/clinic/closed-days');
    }

    public function deleteClosedDay(Request $request)
    {
        $this->authorize('clinics.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/clinic/closed-days');
        }

        $service = new ClinicService($this->container);
        $service->deleteClosedDay($id, $request->ip());

        return $this->redirect('/clinic/closed-days');
    }
}
