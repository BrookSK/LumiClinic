<?php

declare(strict_types=1);

namespace App\Controllers\Scheduling;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\MaterialRepository;
use App\Repositories\ServiceCatalogRepository;
use App\Repositories\ServiceMaterialDefaultRepository;
use App\Services\Auth\AuthService;

final class ServiceMaterialsController extends Controller
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

        $serviceId = (int)$request->input('service_id', 0);

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $svcRepo = new ServiceCatalogRepository($pdo);
        $service = $serviceId > 0 ? $svcRepo->findById($clinicId, $serviceId) : null;
        if ($service === null) {
            return $this->redirect('/services');
        }

        $matRepo = new MaterialRepository($pdo);
        $materials = $matRepo->listByClinic($clinicId);

        $defaultsRepo = new ServiceMaterialDefaultRepository($pdo);
        $defaults = $defaultsRepo->listDetailedByService($clinicId, $serviceId);

        return $this->view('scheduling/service_materials', [
            'service' => $service,
            'materials' => $materials,
            'defaults' => $defaults,
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('services.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $serviceId = (int)$request->input('service_id', 0);
        $materialId = (int)$request->input('material_id', 0);
        $qty = trim((string)$request->input('quantity_per_session', ''));

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($serviceId <= 0 || $materialId <= 0 || $qty === '') {
            return $this->redirect('/services/materials?service_id=' . (int)$serviceId);
        }

        try {
            $pdo = $this->container->get(\PDO::class);

            $svcRepo = new ServiceCatalogRepository($pdo);
            if ($svcRepo->findById($clinicId, $serviceId) === null) {
                throw new \RuntimeException('Serviço inválido.');
            }

            $matRepo = new MaterialRepository($pdo);
            if ($matRepo->findById($clinicId, $materialId) === null) {
                throw new \RuntimeException('Material inválido.');
            }

            $repo = new ServiceMaterialDefaultRepository($pdo);
            $repo->upsert($clinicId, $serviceId, $materialId, $qty);

            return $this->redirect('/services/materials?service_id=' . (int)$serviceId);
        } catch (\RuntimeException $e) {
            return $this->redirect('/services/materials?service_id=' . (int)$serviceId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function delete(Request $request)
    {
        $this->authorize('services.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $serviceId = (int)$request->input('service_id', 0);
        $id = (int)$request->input('id', 0);

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($serviceId <= 0 || $id <= 0) {
            return $this->redirect('/services');
        }

        $repo = new ServiceMaterialDefaultRepository($this->container->get(\PDO::class));
        $repo->softDelete($clinicId, $id);

        return $this->redirect('/services/materials?service_id=' . (int)$serviceId);
    }
}
