<?php

declare(strict_types=1);

namespace App\Controllers\Clinics;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Clinics\ClinicService;

final class ClinicController extends Controller
{
    public function edit(Request $request)
    {
        $this->authorize('clinics.read');

        $service = new ClinicService($this->container);
        $clinic = $service->getCurrentClinic();

        return $this->view('clinics/edit', ['clinic' => $clinic]);
    }

    public function update(Request $request)
    {
        $this->authorize('clinics.update');

        $name = trim((string)$request->input('name', ''));
        if ($name === '') {
            return $this->view('clinics/edit', ['error' => 'Nome é obrigatório.']);
        }

        $service = new ClinicService($this->container);
        $service->updateClinicName($name, $request->ip());

        return $this->redirect('/clinic');
    }
}
