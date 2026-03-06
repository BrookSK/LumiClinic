<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\GoogleCalendarSyncLogQueryRepository;
use App\Services\Auth\AuthService;
use App\Services\Queue\QueueService;

final class GoogleCalendarLogsController extends Controller
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
        $this->authorize('settings.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 50);
        $page = max(1, $page);
        $perPage = max(25, min(200, $perPage));
        $offset = ($page - 1) * $perPage;

        $filters = [
            'status' => trim((string)$request->input('status', '')),
            'action' => trim((string)$request->input('action', '')),
            'appointment_id' => trim((string)$request->input('appointment_id', '')),
            'user_id' => trim((string)$request->input('user_id', '')),
            'from' => trim((string)$request->input('from', '')),
            'to' => trim((string)$request->input('to', '')),
        ];

        $repo = new GoogleCalendarSyncLogQueryRepository($this->container->get(\PDO::class));
        $rows = $repo->listByClinic($clinicId, $filters, $perPage + 1, $offset);
        $hasNext = count($rows) > $perPage;
        if ($hasNext) {
            $rows = array_slice($rows, 0, $perPage);
        }

        return $this->view('settings/google_calendar_logs', [
            'rows' => $rows,
            'filters' => $filters,
            'page' => $page,
            'per_page' => $perPage,
            'has_next' => $hasNext,
            'ok' => trim((string)$request->input('ok', '')),
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function forceSync(Request $request)
    {
        $this->authorize('settings.update');

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
            return $this->redirect('/settings/google-calendar/logs?error=' . urlencode('appointment_id inválido.'));
        }

        (new QueueService($this->container))->enqueue(
            'gcal.sync_appointment',
            ['appointment_id' => $appointmentId, 'manual' => 1],
            $clinicId,
            'integrations',
            (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
            10
        );

        return $this->redirect('/settings/google-calendar/logs?ok=' . urlencode('Sync enfileirado para o agendamento #' . $appointmentId));
    }
}
