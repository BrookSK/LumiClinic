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
        $this->authorize('scheduling.finalize');

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
        $prof = $profRepo->findByUserId($clinicId, $userId);
        if ($prof === null) {
            throw new \RuntimeException('Profissional não vinculado ao usuário.');
        }

        $professionalId = (int)($prof['id'] ?? 0);
        if ($professionalId <= 0) {
            throw new \RuntimeException('Profissional inválido.');
        }

        $repo = new AppointmentRepository($pdo);
        $items = $repo->listCheckedInQueueForProfessional($clinicId, $professionalId, 100);

        return $this->view('scheduling/professional_queue', [
            'items' => $items,
        ]);
    }
}
