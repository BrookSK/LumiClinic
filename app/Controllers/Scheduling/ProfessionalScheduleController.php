<?php

declare(strict_types=1);

namespace App\Controllers\Scheduling;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\AuditLogRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\ProfessionalScheduleRepository;
use App\Services\Auth\AuthService;

final class ProfessionalScheduleController extends Controller
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

    public function index(Request $request)
    {
        $this->authorize('schedule_rules.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $professionalId = (int)$request->input('professional_id', 0);

        $profRepo = new ProfessionalRepository($this->container->get(\PDO::class));
        $professionals = $profRepo->listActiveByClinic($clinicId);

        $items = [];
        if ($professionalId > 0) {
            $repo = new ProfessionalScheduleRepository($this->container->get(\PDO::class));
            $items = $repo->listByProfessional($clinicId, $professionalId);
        }

        return $this->view('scheduling/schedules', [
            'professionals' => $professionals,
            'professional_id' => $professionalId,
            'items' => $items,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('schedule_rules.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $professionalId = (int)$request->input('professional_id', 0);
        $weekday = (int)$request->input('weekday', -1);
        $start = trim((string)$request->input('start_time', ''));
        $end = trim((string)$request->input('end_time', ''));
        $interval = trim((string)$request->input('interval_minutes', ''));

        if ($professionalId <= 0 || $weekday < 0 || $weekday > 6 || $start === '' || $end === '') {
            return $this->redirect('/schedule-rules?professional_id=' . $professionalId);
        }

        $intervalMinutes = null;
        if ($interval !== '') {
            $intervalMinutes = max(0, (int)$interval);
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ProfessionalScheduleRepository($this->container->get(\PDO::class));
        $id = $repo->create($clinicId, $professionalId, $weekday, $start, $end, $intervalMinutes);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'scheduling.schedule_rule_create', [
            'schedule_id' => $id,
            'professional_id' => $professionalId,
            'weekday' => $weekday,
            'start_time' => $start,
            'end_time' => $end,
            'interval_minutes' => $intervalMinutes,
        ], $request->ip());

        return $this->redirect('/schedule-rules?professional_id=' . $professionalId);
    }
}
