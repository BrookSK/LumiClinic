<?php

declare(strict_types=1);

namespace App\Controllers\Whatsapp;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\WhatsappMessageLogRepository;
use App\Services\Auth\AuthService;
use App\Services\Queue\QueueService;
use App\Services\Whatsapp\WhatsappMessageLogService;

final class WhatsappLogController extends Controller
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

        $service = new WhatsappMessageLogService($this->container);

        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 50);
        $page = max(1, $page);
        $perPage = max(25, min(200, $perPage));
        $offset = ($page - 1) * $perPage;

        $filters = [
            'status' => trim((string)$request->input('status', '')),
            'template_code' => trim((string)$request->input('template_code', '')),
            'from' => trim((string)$request->input('from', '')),
            'to' => trim((string)$request->input('to', '')),
            'appointment_id' => trim((string)$request->input('appointment_id', '')),
            'patient_id' => trim((string)$request->input('patient_id', '')),
        ];

        return $this->view('whatsapp-logs/index', [
            'items' => (function () use ($service, $filters, $perPage, $offset) {
                $rows = $service->list($filters, $perPage + 1, $offset);
                $hasNext = count($rows) > $perPage;
                if ($hasNext) {
                    $rows = array_slice($rows, 0, $perPage);
                }
                return ['rows' => $rows, 'has_next' => $hasNext];
            })(),
            'filters' => $filters,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    public function show(Request $request)
    {
        $this->authorize('settings.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/whatsapp-logs');
        }

        try {
            $row = (new WhatsappMessageLogService($this->container))->get($id);
        } catch (\Throwable $e) {
            return $this->redirect('/whatsapp-logs');
        }

        return $this->view('whatsapp-logs/show', [
            'log' => $row,
        ]);
    }

    public function retrySend(Request $request)
    {
        $this->authorize('settings.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/whatsapp-logs');
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/whatsapp-logs');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new WhatsappMessageLogRepository($pdo);
        $log = $repo->findById($clinicId, $id);
        if ($log === null) {
            return $this->redirect('/whatsapp-logs');
        }

        $appointmentId = (int)($log['appointment_id'] ?? 0);
        $templateCode = trim((string)($log['template_code'] ?? ''));
        if ($appointmentId <= 0 || $templateCode === '') {
            return $this->redirect('/whatsapp-logs/show?id=' . $id);
        }

        $repo->resetToPendingForRetry($clinicId, $id);

        (new QueueService($this->container))->enqueue(
            'whatsapp.send_reminder',
            ['appointment_id' => $appointmentId, 'template_code' => $templateCode, 'log_id' => $id],
            $clinicId,
            'notifications',
            (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
            10
        );

        return $this->redirect('/whatsapp-logs/show?id=' . $id . '&resent=1');
    }

    public function forceReconcile(Request $request)
    {
        $this->authorize('settings.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/whatsapp-logs');
        }

        (new QueueService($this->container))->enqueue(
            'whatsapp.reminders.reconcile',
            ['manual' => 1],
            $clinicId,
            'notifications',
            (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
            10
        );

        return $this->redirect('/whatsapp-logs?reconcile=1');
    }
}
