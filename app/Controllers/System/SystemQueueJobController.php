<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\System\SystemQueueJobService;

final class SystemQueueJobController extends Controller
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

        $status = trim((string)$request->input('status', ''));
        if ($status === '') {
            $status = null;
        }

        $service = new SystemQueueJobService($this->container);

        return $this->view('system/queue_jobs/index', [
            'items' => $service->listJobs($status),
            'status' => $status,
        ]);
    }

    public function retry(Request $request)
    {
        $this->ensureSuperAdmin();

        $jobId = (int)$request->input('job_id', 0);
        if ($jobId <= 0) {
            return $this->redirect('/sys/queue-jobs');
        }

        $service = new SystemQueueJobService($this->container);
        $service->retryDead($jobId);

        $status = trim((string)$request->input('status', ''));
        if ($status !== '') {
            return $this->redirect('/sys/queue-jobs?status=' . urlencode($status));
        }

        return $this->redirect('/sys/queue-jobs');
    }

    public function enqueueTest(Request $request)
    {
        $this->ensureSuperAdmin();

        $jobType = trim((string)$request->input('job_type', ''));
        if (!in_array($jobType, ['test.noop', 'test.throw'], true)) {
            return $this->redirect('/sys/queue-jobs');
        }

        $service = new SystemQueueJobService($this->container);
        $service->enqueueTest($jobType);

        $status = trim((string)$request->input('status', ''));
        if ($status !== '') {
            return $this->redirect('/sys/queue-jobs?status=' . urlencode($status));
        }

        return $this->redirect('/sys/queue-jobs');
    }
}
