<?php

declare(strict_types=1);

namespace App\Controllers\Scheduling;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\AuditLogRepository;
use App\Repositories\ProfessionalRepository;
use App\Services\Auth\AuthService;

final class ProfessionalController extends Controller
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
        $this->authorize('professionals.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ProfessionalRepository($this->container->get(\PDO::class));
        $items = $repo->listActiveByClinic($clinicId);

        return $this->view('scheduling/professionals', ['items' => $items]);
    }

    public function create(Request $request)
    {
        $this->authorize('professionals.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $name = trim((string)$request->input('name', ''));
        $specialty = trim((string)$request->input('specialty', ''));
        $linkUserId = (int)$request->input('user_id', 0);
        $allowOnline = (string)$request->input('allow_online_booking', '0') === '1';

        if ($name === '') {
            return $this->redirect('/professionals');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ProfessionalRepository($this->container->get(\PDO::class));
        $id = $repo->create($clinicId, ($linkUserId > 0 ? $linkUserId : null), $name, $specialty, $allowOnline);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'scheduling.professional_create', [
            'professional_id' => $id,
            'user_id' => ($linkUserId > 0 ? $linkUserId : null),
            'name' => $name,
            'specialty' => $specialty,
            'allow_online_booking' => $allowOnline,
        ], $request->ip());

        return $this->redirect('/professionals');
    }
}
