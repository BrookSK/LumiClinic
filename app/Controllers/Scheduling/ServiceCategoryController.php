<?php

declare(strict_types=1);

namespace App\Controllers\Scheduling;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\AuditLogRepository;
use App\Repositories\ServiceCategoryRepository;
use App\Services\Auth\AuthService;

final class ServiceCategoryController extends Controller
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

        $repo = new ServiceCategoryRepository($this->container->get(\PDO::class));
        $items = $repo->listActiveByClinic($clinicId);

        return $this->view('scheduling/service_categories', [
            'items' => $items,
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

        $name = trim((string)$request->input('name', ''));
        if ($name === '') {
            return $this->redirect('/services/categories');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        try {
            $repo = new ServiceCategoryRepository($this->container->get(\PDO::class));
            if ($repo->existsActiveByClinicAndName($clinicId, $name)) {
                throw new \RuntimeException('Categoria já existe.');
            }

            $id = $repo->create($clinicId, $name);

            (new AuditLogRepository($this->container->get(\PDO::class)))->log($userId, $clinicId, 'scheduling.service_category_create', [
                'service_category_id' => $id,
                'name' => $name,
            ], $request->ip());

            return $this->redirect('/services/categories');
        } catch (\RuntimeException $e) {
            return $this->redirect('/services/categories?error=' . urlencode($e->getMessage()));
        }
    }

    public function delete(Request $request)
    {
        $this->authorize('services.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/services/categories');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        try {
            $repo = new ServiceCategoryRepository($this->container->get(\PDO::class));
            $repo->softDelete($clinicId, $id);

            (new AuditLogRepository($this->container->get(\PDO::class)))->log($userId, $clinicId, 'scheduling.service_category_delete', [
                'service_category_id' => $id,
            ], $request->ip());

            return $this->redirect('/services/categories');
        } catch (\RuntimeException $e) {
            return $this->redirect('/services/categories?error=' . urlencode($e->getMessage()));
        }
    }
}
