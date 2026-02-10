<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\SystemErrorLogRepository;

final class SystemErrorLogController extends Controller
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
        $statusCode = null;
        if ($status !== '' && ctype_digit($status)) {
            $statusCode = (int)$status;
            if ($statusCode <= 0) {
                $statusCode = null;
            }
        }

        $repo = new SystemErrorLogRepository($this->container->get(\PDO::class));
        $items = $repo->listLatest(300, 0, $statusCode);

        return $this->view('system/error_logs/index', [
            'items' => $items,
            'status' => $statusCode,
        ]);
    }

    public function details(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/sys/error-logs');
        }

        $repo = new SystemErrorLogRepository($this->container->get(\PDO::class));
        $row = $repo->findById($id);
        if ($row === null) {
            return $this->redirect('/sys/error-logs');
        }

        return $this->view('system/error_logs/view', [
            'item' => $row,
        ]);
    }
}
