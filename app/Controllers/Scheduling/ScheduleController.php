<?php

declare(strict_types=1);

namespace App\Controllers\Scheduling;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\AppointmentRepository;
use App\Repositories\AppointmentLogRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\ServiceCatalogRepository;
use App\Repositories\ServiceMaterialDefaultRepository;
use App\Services\Finance\FinancialService;
use App\Services\Auth\AuthService;
use App\Services\Scheduling\AppointmentService;
use App\Services\Scheduling\AvailabilityService;
use App\Services\Stock\StockService;

final class ScheduleController extends Controller
{
    private function isProfessionalRole(): bool
    {
        $roles = $_SESSION['role_codes'] ?? [];
        return is_array($roles) && in_array('professional', $roles, true);
    }

    public function completeMaterials(Request $request)
    {
        $this->authorize('scheduling.finalize');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/schedule');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($this->isProfessionalRole()) {
            $ownProfessionalId = $this->forceProfessionalIdForCurrentUser($clinicId);
            $repo = new AppointmentRepository($this->container->get(\PDO::class));
            $appointment = $repo->findById($clinicId, $id);
            if ($appointment === null) {
                return $this->redirect('/schedule?error=' . urlencode('Agendamento inválido.'));
            }
            if ((int)$appointment['professional_id'] !== $ownProfessionalId) {
                throw new \RuntimeException('Acesso negado.');
            }
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new AppointmentRepository($pdo);
        $appointment = $repo->findById($clinicId, $id);
        if ($appointment === null) {
            return $this->redirect('/schedule?error=' . urlencode('Agendamento inválido.'));
        }

        $svcRepo = new ServiceCatalogRepository($pdo);
        $service = $svcRepo->findById($clinicId, (int)$appointment['service_id']);
        if ($service === null) {
            return $this->redirect('/schedule?error=' . urlencode('Serviço inválido.'));
        }

        $defaultsRepo = new ServiceMaterialDefaultRepository($pdo);
        $defaults = $defaultsRepo->listDetailedByService($clinicId, (int)$appointment['service_id']);

        return $this->view('scheduling/complete_materials', [
            'appointment' => $appointment,
            'service' => $service,
            'defaults' => $defaults,
            'date' => trim((string)$request->input('date', '')),
            'view' => trim((string)$request->input('view', 'day')),
            'professional_id' => (int)$request->input('professional_id', 0),
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function completeMaterialsSubmit(Request $request)
    {
        $this->authorize('scheduling.finalize');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $returnDate = trim((string)$request->input('date', ''));
        $view = trim((string)$request->input('view', 'day'));
        $professionalId = (int)$request->input('professional_id', 0);
        $note = trim((string)$request->input('note', ''));
        $qty = $request->input('qty', []);

        if ($id <= 0) {
            return $this->redirect('/schedule');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($this->isProfessionalRole()) {
            $ownProfessionalId = $this->forceProfessionalIdForCurrentUser($clinicId);
            $repo = new AppointmentRepository($this->container->get(\PDO::class));
            $appointment = $repo->findById($clinicId, $id);
            if ($appointment === null) {
                return $this->redirect('/schedule?error=' . urlencode('Agendamento inválido.'));
            }
            if ((int)$appointment['professional_id'] !== $ownProfessionalId) {
                throw new \RuntimeException('Acesso negado.');
            }
            $professionalId = $ownProfessionalId;
        }

        try {
            $pdo = $this->container->get(\PDO::class);
            $repo = new AppointmentRepository($pdo);
            $appointment = $repo->findById($clinicId, $id);
            if ($appointment === null) {
                throw new \RuntimeException('Agendamento inválido.');
            }

            $stock = new StockService($this->container);

            $result = ['movement_ids' => [], 'total_cost' => 0.0];
            if (is_array($qty) && $qty !== []) {
                /** @var array<int,string> $qty */
                $result = $stock->consumeForAppointmentAdjusted($id, (int)$appointment['service_id'], $qty, $note, $request->ip());
            } else {
                $result = $stock->consumeForAppointmentAdjusted($id, (int)$appointment['service_id'], [], $note, $request->ip());
            }

            if ((float)$result['total_cost'] > 0) {
                (new FinancialService($this->container))->createEntry(
                    'out',
                    date('Y-m-d'),
                    number_format((float)$result['total_cost'], 2, '.', ''),
                    null,
                    null,
                    'Custo de materiais (sessão #' . (int)$id . ') - ' . $note,
                    $request->ip()
                );
            }

            (new AppointmentService($this->container))->updateStatus($id, 'completed', $request->ip());

            $q = [];
            if ($returnDate !== '') { $q[] = 'date=' . urlencode($returnDate); }
            if ($view !== '') { $q[] = 'view=' . urlencode($view); }
            if ($professionalId > 0) { $q[] = 'professional_id=' . $professionalId; }
            return $this->redirect('/schedule' . ($q ? ('?' . implode('&', $q)) : ''));
        } catch (\RuntimeException $e) {
            return $this->redirect('/schedule/complete-materials?id=' . (int)$id . '&error=' . urlencode($e->getMessage()));
        } catch (\Throwable $e) {
            return $this->redirect('/schedule/complete-materials?id=' . (int)$id . '&error=' . urlencode('Erro ao finalizar sessão.'));
        }
    }

    private function forceProfessionalIdForCurrentUser(int $clinicId): int
    {
        $auth = new AuthService($this->container);
        $userId = $auth->userId();
        if ($userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ProfessionalRepository($this->container->get(\PDO::class));
        $prof = $repo->findByUserId($clinicId, $userId);
        if ($prof === null) {
            throw new \RuntimeException('Profissional não vinculado ao usuário.');
        }

        return (int)$prof['id'];
    }

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
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 100);
        $created = trim((string)$request->input('created', ''));
        $error = trim((string)$request->input('error', ''));

        $isProfessional = $this->isProfessionalRole();
        if ($isProfessional) {
            $professionalId = $this->forceProfessionalIdForCurrentUser($clinicId);
        }

        if ($view === 'month') {
            $start = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
            if ($start === false) {
                throw new \RuntimeException('Data inválida.');
            }

            $monthStart = $start->modify('first day of this month');
            $monthEnd = $monthStart->modify('first day of next month');

            $apptRepo = new AppointmentRepository($this->container->get(\PDO::class));
            $items = $apptRepo->listByClinicRange(
                $clinicId,
                $monthStart->format('Y-m-d 00:00:00'),
                $monthEnd->format('Y-m-d 00:00:00'),
                $professionalId > 0 ? $professionalId : null
            );

            $profRepo = new ProfessionalRepository($this->container->get(\PDO::class));
            $professionals = $profRepo->listActiveByClinic($clinicId);

            $svcRepo = new ServiceCatalogRepository($this->container->get(\PDO::class));
            $services = $svcRepo->listActiveByClinic($clinicId);

            return $this->view('scheduling/month', [
                'date' => $date,
                'view' => $view,
                'professional_id' => $professionalId,
                'is_professional' => $isProfessional,
                'created' => $created,
                'error' => $error,
                'items' => $items,
                'professionals' => $professionals,
                'services' => $services,
                'month_start' => $monthStart->format('Y-m-d'),
            ]);
        }

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
                'is_professional' => $isProfessional,
                'created' => $created,
                'error' => $error,
                'items' => $items,
                'professionals' => $professionals,
                'services' => $services,
                'week_start' => $weekStart->format('Y-m-d'),
            ]);
        }

        $page = max(1, $page);
        $perPage = max(25, min(200, $perPage));
        $offset = ($page - 1) * $perPage;

        $apptRepo = new AppointmentRepository($this->container->get(\PDO::class));
        $items = $apptRepo->listByClinicRange(
            $clinicId,
            $date . ' 00:00:00',
            $date . ' 23:59:59',
            $professionalId > 0 ? $professionalId : null,
            $perPage + 1,
            $offset
        );

        $hasNext = count($items) > $perPage;
        if ($hasNext) {
            $items = array_slice($items, 0, $perPage);
        }

        $profRepo = new ProfessionalRepository($this->container->get(\PDO::class));
        $professionals = $profRepo->listActiveByClinic($clinicId);

        $svcRepo = new ServiceCatalogRepository($this->container->get(\PDO::class));
        $services = $svcRepo->listActiveByClinic($clinicId);

        return $this->view('scheduling/index', [
            'date' => $date,
            'view' => $view,
            'professional_id' => $professionalId,
            'is_professional' => $isProfessional,
            'created' => $created,
            'error' => $error,
            'items' => $items,
            'professionals' => $professionals,
            'services' => $services,
            'page' => $page,
            'per_page' => $perPage,
            'has_next' => $hasNext,
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

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($this->isProfessionalRole()) {
            $professionalId = $this->forceProfessionalIdForCurrentUser($clinicId);
        }

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

        if ($this->isProfessionalRole()) {
            $auth = new AuthService($this->container);
            $clinicId = $auth->clinicId();
            if ($clinicId === null) {
                throw new \RuntimeException('Contexto inválido.');
            }

            $ownProfessionalId = $this->forceProfessionalIdForCurrentUser($clinicId);
            $repo = new AppointmentRepository($this->container->get(\PDO::class));
            $appointment = $repo->findById($clinicId, $id);
            if ($appointment === null) {
                return $this->redirect('/schedule?error=' . urlencode('Agendamento inválido.'));
            }

            if ((int)$appointment['professional_id'] !== $ownProfessionalId) {
                throw new \RuntimeException('Acesso negado.');
            }
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

        if (in_array($status, ['confirmed', 'in_progress', 'completed', 'no_show'], true)) {
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

        if ($this->isProfessionalRole()) {
            $auth = new AuthService($this->container);
            $clinicId = $auth->clinicId();
            if ($clinicId === null) {
                throw new \RuntimeException('Contexto inválido.');
            }

            $ownProfessionalId = $this->forceProfessionalIdForCurrentUser($clinicId);
            $repo = new AppointmentRepository($this->container->get(\PDO::class));
            $appointment = $repo->findById($clinicId, $id);
            if ($appointment === null) {
                return $this->redirect('/schedule?error=' . urlencode('Agendamento inválido.'));
            }

            if ((int)$appointment['professional_id'] !== $ownProfessionalId) {
                throw new \RuntimeException('Acesso negado.');
            }

            $professionalId = $ownProfessionalId;
        }

        if ($status === 'completed') {
            $auth = new AuthService($this->container);
            $clinicId = $auth->clinicId();
            if ($clinicId === null) {
                throw new \RuntimeException('Contexto inválido.');
            }

            $repo = new AppointmentRepository($this->container->get(\PDO::class));
            $appointment = $repo->findById($clinicId, $id);
            if ($appointment === null) {
                return $this->redirect('/schedule?error=' . urlencode('Agendamento inválido.'));
            }

            if ((string)$appointment['status'] !== 'in_progress') {
                return $this->redirect('/schedule?error=' . urlencode('Somente atendimentos em andamento podem ser concluídos.'));
            }

            $q = [];
            $q[] = 'id=' . (int)$id;
            if ($returnDate !== '') { $q[] = 'date=' . urlencode($returnDate); }
            if ($view !== '') { $q[] = 'view=' . urlencode($view); }
            if ($professionalId > 0) { $q[] = 'professional_id=' . $professionalId; }
            return $this->redirect('/schedule/complete-materials?' . implode('&', $q));
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

        if ($this->isProfessionalRole()) {
            $ownProfessionalId = $this->forceProfessionalIdForCurrentUser($clinicId);
            if ((int)$appointment['professional_id'] !== $ownProfessionalId) {
                throw new \RuntimeException('Acesso negado.');
            }
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

        if ($this->isProfessionalRole()) {
            $auth = new AuthService($this->container);
            $clinicId = $auth->clinicId();
            if ($clinicId === null) {
                throw new \RuntimeException('Contexto inválido.');
            }

            $ownProfessionalId = $this->forceProfessionalIdForCurrentUser($clinicId);
            $repo = new AppointmentRepository($this->container->get(\PDO::class));
            $appointment = $repo->findById($clinicId, $id);
            if ($appointment === null) {
                return $this->redirect('/schedule?error=' . urlencode('Agendamento inválido.'));
            }

            if ((int)$appointment['professional_id'] !== $ownProfessionalId) {
                throw new \RuntimeException('Acesso negado.');
            }

            $professionalId = $ownProfessionalId;
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

    public function ops(Request $request)
    {
        $this->authorize('scheduling.ops');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $date = trim((string)$request->input('date', date('Y-m-d')));
        $repo = new AppointmentRepository($this->container->get(\PDO::class));

        $professionalId = null;
        if ($this->isProfessionalRole()) {
            $professionalId = $this->forceProfessionalIdForCurrentUser($clinicId);
        }

        $items = $repo->listByClinicRange($clinicId, $date . ' 00:00:00', $date . ' 23:59:59', $professionalId);

        $counts = [
            'scheduled' => 0,
            'confirmed' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'no_show' => 0,
            'total' => 0,
        ];

        foreach ($items as $it) {
            $counts['total']++;
            $st = (string)($it['status'] ?? '');
            if (isset($counts[$st])) {
                $counts[$st]++;
            }
        }

        return $this->view('scheduling/ops', [
            'date' => $date,
            'counts' => $counts,
        ]);
    }

    public function logs(Request $request)
    {
        $this->authorize('scheduling.logs');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $appointmentId = (int)$request->input('appointment_id', 0);
        if ($appointmentId <= 0) {
            return $this->redirect('/schedule');
        }

        $repo = new AppointmentRepository($this->container->get(\PDO::class));
        $appointment = $repo->findById($clinicId, $appointmentId);
        if ($appointment === null) {
            return $this->redirect('/schedule?error=' . urlencode('Agendamento inválido.'));
        }

        if ($this->isProfessionalRole()) {
            $ownProfessionalId = $this->forceProfessionalIdForCurrentUser($clinicId);
            if ((int)$appointment['professional_id'] !== $ownProfessionalId) {
                throw new \RuntimeException('Acesso negado.');
            }
        }

        $logsRepo = new AppointmentLogRepository($this->container->get(\PDO::class));
        $logs = $logsRepo->listByAppointment($clinicId, $appointmentId, 200);

        return $this->view('scheduling/logs', [
            'appointment' => $appointment,
            'logs' => $logs,
        ]);
    }
}
