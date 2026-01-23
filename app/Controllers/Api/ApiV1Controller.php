<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\AppointmentRepository;

final class ApiV1Controller extends Controller
{
    private function apiContext(): array
    {
        $clinicId = $this->container->has('api.patient_clinic_id') ? $this->container->get('api.patient_clinic_id') : null;
        $patientId = $this->container->has('api.patient_id') ? $this->container->get('api.patient_id') : null;

        return [
            'clinic_id' => is_int($clinicId) ? $clinicId : null,
            'patient_id' => is_int($patientId) ? $patientId : null,
        ];
    }

    public function me(Request $request): Response
    {
        $ctx = $this->apiContext();
        if ($ctx['clinic_id'] === null || $ctx['patient_id'] === null) {
            return Response::json(['error' => 'unauthorized'], 401);
        }

        return Response::json([
            'clinic_id' => $ctx['clinic_id'],
            'patient_id' => $ctx['patient_id'],
        ]);
    }

    public function upcomingAppointments(Request $request): Response
    {
        $ctx = $this->apiContext();
        if ($ctx['clinic_id'] === null || $ctx['patient_id'] === null) {
            return Response::json(['error' => 'unauthorized'], 401);
        }

        $repo = new AppointmentRepository($this->container->get(\PDO::class));
        $items = $repo->listUpcomingByPatient($ctx['clinic_id'], $ctx['patient_id'], 20);

        return Response::json(['items' => $items]);
    }
}
