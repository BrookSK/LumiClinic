<?php

declare(strict_types=1);

namespace App\Controllers\Compliance;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\AdminUserRepository;
use App\Services\Compliance\SecurityIncidentService;
use App\Services\Auth\AuthService;

final class SecurityIncidentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('compliance.incidents.read');

        $svc = new SecurityIncidentService($this->container);
        $items = $svc->list($request->ip(), $request->header('user-agent'));

        $users = [];
        $clinicId = (new AuthService($this->container))->clinicId();
        if ($clinicId !== null) {
            $users = (new AdminUserRepository($this->container->get(\PDO::class)))->listByClinic((int)$clinicId, 400, 0);
        }

        return $this->view('compliance/incidents', [
            'items' => $items,
            'users' => $users,
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('compliance.incidents.create');

        $severity = trim((string)$request->input('severity', 'medium'));
        $title = trim((string)$request->input('title', ''));
        $description = trim((string)$request->input('description', ''));

        try {
            (new SecurityIncidentService($this->container))->create($severity, $title, $description === '' ? null : $description, $request->ip(), $request->header('user-agent'));
            return $this->redirect('/compliance/incidents');
        } catch (\RuntimeException $e) {
            return $this->redirect('/compliance/incidents?error=' . urlencode($e->getMessage()));
        }
    }

    public function update(Request $request)
    {
        $this->authorize('compliance.incidents.update');

        $id = (int)$request->input('id', 0);
        $status = trim((string)$request->input('status', ''));
        $assignedToUserId = (int)$request->input('assigned_to_user_id', 0);
        $corrective = trim((string)$request->input('corrective_action', ''));

        if ($id <= 0) {
            return $this->redirect('/compliance/incidents');
        }

        try {
            (new SecurityIncidentService($this->container))->update(
                $id,
                $status,
                $assignedToUserId > 0 ? $assignedToUserId : null,
                $corrective === '' ? null : $corrective,
                $request->ip(),
                $request->header('user-agent')
            );
            return $this->redirect('/compliance/incidents');
        } catch (\RuntimeException $e) {
            return $this->redirect('/compliance/incidents?error=' . urlencode($e->getMessage()));
        }
    }
}
