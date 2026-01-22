<?php

declare(strict_types=1);

namespace App\Controllers\Scheduling;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\AuditLogRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\SchedulingBlockRepository;
use App\Services\Auth\AuthService;

final class BlockController extends Controller
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
        $this->authorize('blocks.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $profRepo = new ProfessionalRepository($this->container->get(\PDO::class));
        $professionals = $profRepo->listActiveByClinic($clinicId);

        return $this->view('scheduling/blocks', ['professionals' => $professionals]);
    }

    public function create(Request $request)
    {
        $this->authorize('blocks.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $professionalId = (int)$request->input('professional_id', 0);
        $startAt = trim((string)$request->input('start_at', ''));
        $endAt = trim((string)$request->input('end_at', ''));
        $reason = trim((string)$request->input('reason', ''));
        $type = trim((string)$request->input('type', 'manual'));

        if ($startAt === '' || $endAt === '') {
            return $this->redirect('/blocks');
        }

        $typeAllowed = ['manual', 'holiday', 'maintenance'];
        if (!in_array($type, $typeAllowed, true)) {
            $type = 'manual';
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pid = $professionalId > 0 ? $professionalId : null;

        $repo = new SchedulingBlockRepository($this->container->get(\PDO::class));
        $id = $repo->create($clinicId, $pid, $startAt, $endAt, $reason, $type, $userId);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'scheduling.block_create', [
            'block_id' => $id,
            'professional_id' => $pid,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'reason' => $reason,
            'type' => $type,
        ], $request->ip());

        return $this->redirect('/blocks');
    }
}
