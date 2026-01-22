<?php

declare(strict_types=1);

namespace App\Controllers\Scheduling;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\AuditLogRepository;
use App\Repositories\ServiceCatalogRepository;
use App\Services\Auth\AuthService;

final class ServiceController extends Controller
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
        $this->authorize('services.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ServiceCatalogRepository($this->container->get(\PDO::class));
        $items = $repo->listActiveByClinic($clinicId);

        return $this->view('scheduling/services', ['items' => $items]);
    }

    public function create(Request $request)
    {
        $this->authorize('services.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $name = trim((string)$request->input('name', ''));
        $duration = (int)$request->input('duration_minutes', 0);
        $price = trim((string)$request->input('price_cents', ''));
        $allowSpecific = (string)$request->input('allow_specific_professional', '0') === '1';

        if ($name === '' || $duration <= 0) {
            return $this->redirect('/services');
        }

        $priceCents = null;
        if ($price !== '') {
            $priceCents = max(0, (int)$price);
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ServiceCatalogRepository($this->container->get(\PDO::class));
        $id = $repo->create($clinicId, $name, $duration, $priceCents, $allowSpecific);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'scheduling.service_create', [
            'service_id' => $id,
            'name' => $name,
            'duration_minutes' => $duration,
            'price_cents' => $priceCents,
            'allow_specific_professional' => $allowSpecific,
        ], $request->ip());

        return $this->redirect('/services');
    }
}
