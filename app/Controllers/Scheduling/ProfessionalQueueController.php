<?php

declare(strict_types=1);

namespace App\Controllers\Scheduling;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\AppointmentRepository;
use App\Repositories\ProfessionalRepository;
use App\Services\Auth\AuthService;

final class ProfessionalQueueController extends Controller
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
        $this->authorize('scheduling.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $profRepo = new ProfessionalRepository($pdo);

        // Verificar se o usuário logado é um profissional
        $prof = $profRepo->findByUserId($clinicId, $userId);

        // Roles do usuário
        $roleCodes = $_SESSION['role_codes'] ?? [];
        $isOwnerOrAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1
            || (is_array($roleCodes) && (in_array('owner', $roleCodes, true) || in_array('admin', $roleCodes, true)));

        $repo = new AppointmentRepository($pdo);

        if ($prof !== null && !$isOwnerOrAdmin) {
            // Profissional: vê só a fila dele
            $professionalId = (int)($prof['id'] ?? 0);
            $items = $repo->listCheckedInQueueForProfessional($clinicId, $professionalId, 100);
            $viewingAll = false;
            $profName = (string)($prof['name'] ?? '');
        } else {
            // Owner/Admin: vê todos
            $items = $repo->listCheckedInQueueForProfessional($clinicId, 0, 200);
            $viewingAll = true;
            $profName = '';
        }

        return $this->view('scheduling/professional_queue', [
            'items'       => $items,
            'viewing_all' => $viewingAll,
            'prof_name'   => $profName,
        ]);
    }
}
