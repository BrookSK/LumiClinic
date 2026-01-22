<?php

declare(strict_types=1);

namespace App\Controllers\Scheduling;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\AppointmentRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\ServiceCatalogRepository;
use App\Services\Auth\AuthService;
use App\Services\Scheduling\AppointmentService;
use App\Services\Scheduling\AvailabilityService;

final class ScheduleController extends Controller
{
    private function redirectSuperAdminWithoutClinicContext(): ?Response
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

    public function index(Request $request)
    {
        $this->authorize('scheduling.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $view = trim((string)$request->input('view', 'day'));
        $date = trim((string)$request->input('date', date('Y-m-d')));
        $professionalId = (int)$request->input('professional_id', 0);
        $created = trim((string)$request->input('created', ''));
        $error = trim((string)$request->input('error', ''));

        if ($view === 'week') {
            $start = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
            if ($start === false) {
                throw new \RuntimeException('Data inválida.');
            }

            $dayOfWeek = (int)$start->format('w');
            $weekStart = $start->modify('-' . $dayOfWeek . ' days');
            $weekEnd = $weekStart->modify('+7 days');

            $apptRepo = new AppointmentRepository($this->container->get(\PDO::class));
            $items = $apptRepo->listByClinicRange(
                $clinicId,
                $weekStart->format('Y-m-d 00:00:00'),
                $weekEnd->format('Y-m-d 00:00:00'),
                $professionalId > 0 ? $professionalId : null
            );

            $profRepo = new ProfessionalRepository($this->container->get(\PDO::class));
            $professionals = $profRepo->listActiveByClinic($clinicId);

            $svcRepo = new ServiceCatalogRepository($this->container->get(\PDO::class));
            $services = $svcRepo->listActiveByClinic($clinicId);

            return $this->view('scheduling/week', [
                'date' => $date,
                'view' => $view,
                'professional_id' => $professionalId,
                'created' => $created,
                'error' => $error,
                'items' => $items,
                'professionals' => $professionals,
                'services' => $services,
                'week_start' => $weekStart->format('Y-m-d'),
            ]);
        }

        $apptRepo = new AppointmentRepository($this->container->get(\PDO::class));
        $items = $apptRepo->listByClinicRange(
            $clinicId,
            $date . ' 00:00:00',
            $date . ' 23:59:59',
            $professionalId > 0 ? $professionalId : null
        );

        $profRepo = new ProfessionalRepository($this->container->get(\PDO::class));
        $professionals = $profRepo->listActiveByClinic($clinicId);

        $svcRepo = new ServiceCatalogRepository($this->container->get(\PDO::class));
        $services = $svcRepo->listActiveByClinic($clinicId);

        return $this->view('scheduling/index', [
            'date' => $date,
            'view' => $view,
            'professional_id' => $professionalId,
            'created' => $created,
            'error' => $error,
            'items' => $items,
            'professionals' => $professionals,
            'services' => $services,
        ]);
    }

    public function available(Request $request)
    {
        $this->authorize('scheduling.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $serviceId = (int)$request->input('service_id', 0);
        $professionalId = (int)$request->input('professional_id', 0);
        $date = trim((string)$request->input('date', ''));
        $excludeAppointmentId = (int)$request->input('exclude_appointment_id', 0);

        if ($serviceId <= 0 || $professionalId <= 0 || $date === '') {
            return Response::raw(json_encode(['slots' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 200, [
                'Content-Type' => 'application/json; charset=UTF-8',
            ]);
        }

        $svc = new AvailabilityService($this->container);
        $slots = $svc->listAvailableSlots(
            $serviceId,
            $date,
            $professionalId,
            null,
            $excludeAppointmentId > 0 ? $excludeAppointmentId : null
        );

        return Response::raw(json_encode(['slots' => $slots], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('scheduling.create');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $serviceId = (int)$request->input('service_id', 0);
        $professionalId = (int)$request->input('professional_id', 0);
        $startAt = trim((string)$request->input('start_at', ''));
        $patientId = (int)$request->input('patient_id', 0);
        $notes = trim((string)$request->input('notes', ''));

        if ($serviceId <= 0 || $professionalId <= 0 || $startAt === '') {
            return $this->redirect('/schedule');
        }

        try {
            $origin = 'reception';
            $id = (new AppointmentService($this->container))->create(
                $serviceId,
                $professionalId,
                $startAt,
                $origin,
                $patientId > 0 ? $patientId : null,
                $notes,
                $request->ip(),
            );

            return $this->redirect('/schedule?created=' . $id);
        } catch (\RuntimeException $e) {
            return $this->redirect('/schedule?error=' . urlencode($e->getMessage()));
        } catch (\Throwable $e) {
            return $this->redirect('/schedule?error=' . urlencode('Erro ao criar agendamento.'));
        }
    }

    public function cancel(Request $request)
    {
        $this->authorize('scheduling.cancel');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/schedule');
        }

        try {
            (new AppointmentService($this->container))->cancel($id, $request->ip());
            return $this->redirect('/schedule');
        } catch (\RuntimeException $e) {
            return $this->redirect('/schedule?error=' . urlencode($e->getMessage()));
        } catch (\Throwable $e) {
            return $this->redirect('/schedule?error=' . urlencode('Erro ao cancelar agendamento.'));
        }
    }

    public function updateStatus(Request $request)
    {
        $status = trim((string)$request->input('status', ''));

        if (in_array($status, ['confirmed', 'completed', 'no_show'], true)) {
            $this->authorize('scheduling.finalize');
        } else {
            $this->authorize('scheduling.update');
        }

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $returnDate = trim((string)$request->input('date', ''));
        $view = trim((string)$request->input('view', 'day'));
        $professionalId = (int)$request->input('professional_id', 0);

        if ($id <= 0) {
            return $this->redirect('/schedule');
        }

        try {
            (new AppointmentService($this->container))->updateStatus($id, $status, $request->ip());
            $q = [];
            if ($returnDate !== '') { $q[] = 'date=' . urlencode($returnDate); }
            if ($view !== '') { $q[] = 'view=' . urlencode($view); }
            if ($professionalId > 0) { $q[] = 'professional_id=' . $professionalId; }
            return $this->redirect('/schedule' . ($q ? ('?' . implode('&', $q)) : ''));
        } catch (\RuntimeException $e) {
            return $this->redirect('/schedule?error=' . urlencode($e->getMessage()));
        } catch (\Throwable $e) {
            return $this->redirect('/schedule?error=' . urlencode('Erro ao atualizar status.'));
        }
    }

    public function reschedule(Request $request)
    {
        $this->authorize('scheduling.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/schedule');
        }

        $repo = new AppointmentRepository($this->container->get(\PDO::class));
        $appointment = $repo->findById($clinicId, $id);
        if ($appointment === null) {
            return $this->redirect('/schedule?error=' . urlencode('Agendamento inválido.'));
        }

        $profRepo = new ProfessionalRepository($this->container->get(\PDO::class));
        $professionals = $profRepo->listActiveByClinic($clinicId);

        $svcRepo = new ServiceCatalogRepository($this->container->get(\PDO::class));
        $services = $svcRepo->listActiveByClinic($clinicId);

        return $this->view('scheduling/reschedule', [
            'appointment' => $appointment,
            'professionals' => $professionals,
            'services' => $services,
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function rescheduleSubmit(Request $request)
    {
        $this->authorize('scheduling.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $serviceId = (int)$request->input('service_id', 0);
        $professionalId = (int)$request->input('professional_id', 0);
        $startAt = trim((string)$request->input('start_at', ''));

        if ($id <= 0 || $serviceId <= 0 || $professionalId <= 0 || $startAt === '') {
            return $this->redirect('/schedule');
        }

        try {
            (new AppointmentService($this->container))->reschedule($id, $serviceId, $professionalId, $startAt, $request->ip());
            return $this->redirect('/schedule?created=' . $id);
        } catch (\RuntimeException $e) {
            return $this->redirect('/schedule/reschedule?id=' . $id . '&error=' . urlencode($e->getMessage()));
        } catch (\Throwable $e) {
            return $this->redirect('/schedule/reschedule?id=' . $id . '&error=' . urlencode('Erro ao reagendar.'));
        }
    }
}
