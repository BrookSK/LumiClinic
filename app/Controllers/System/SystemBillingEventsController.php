<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\AuditLogRepository;
use App\Repositories\BillingEventRepository;
use App\Repositories\BillingEventQueryRepository;
use App\Services\Queue\QueueService;

final class SystemBillingEventsController extends Controller
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

        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 50);
        $page = max(1, $page);
        $perPage = max(25, min(200, $perPage));
        $offset = ($page - 1) * $perPage;

        $filters = [
            'provider' => trim((string)$request->input('provider', '')),
            'event_type' => trim((string)$request->input('event_type', '')),
            'external_id' => trim((string)$request->input('external_id', '')),
            'processed' => trim((string)$request->input('processed', '')),
            'from' => trim((string)$request->input('from', '')),
            'to' => trim((string)$request->input('to', '')),
        ];

        $rows = (new BillingEventQueryRepository($this->container->get(\PDO::class)))->search($filters, $perPage + 1, $offset);
        $hasNext = count($rows) > $perPage;
        if ($hasNext) {
            $rows = array_slice($rows, 0, $perPage);
        }

        return $this->view('system/billing/events_index', [
            'rows' => $rows,
            'filters' => $filters,
            'page' => $page,
            'per_page' => $perPage,
            'has_next' => $hasNext,
            'ok' => trim((string)$request->input('ok', '')),
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function show(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/sys/billing-events');
        }

        $repo = new BillingEventRepository($this->container->get(\PDO::class));
        $row = $repo->findById($id);
        if ($row === null) {
            return $this->redirect('/sys/billing-events');
        }

        return $this->view('system/billing/events_show', [
            'row' => $row,
            'ok' => trim((string)$request->input('ok', '')),
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function reprocess(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/sys/billing-events');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new BillingEventRepository($pdo);
        $row = $repo->findById($id);
        if ($row === null) {
            return $this->redirect('/sys/billing-events');
        }

        $repo->resetProcessed($id);

        (new QueueService($this->container))->enqueue('billing.process_event', ['billing_event_id' => $id], null, 'default');

        (new AuditLogRepository($pdo))->log(
            null,
            null,
            'sys.billing_events.reprocess',
            ['billing_event_id' => $id],
            $request->ip(),
            null,
            'billing_event',
            $id,
            $request->header('user-agent')
        );

        return $this->redirect('/sys/billing-events/show?id=' . $id . '&ok=' . urlencode('Reprocessamento enfileirado.'));
    }
}
