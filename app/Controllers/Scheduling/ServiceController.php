<?php

declare(strict_types=1);

namespace App\Controllers\Scheduling;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\AuditLogRepository;
use App\Repositories\ProcedureRepository;
use App\Repositories\ServiceCategoryRepository;
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

        $procRepo = new ProcedureRepository($this->container->get(\PDO::class));
        $procedures = $procRepo->listActiveByClinic($clinicId);

        $catRepo = new ServiceCategoryRepository($this->container->get(\PDO::class));
        $categories = $catRepo->listActiveByClinic($clinicId);

        return $this->view('scheduling/services', ['items' => $items, 'procedures' => $procedures, 'categories' => $categories]);
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
        $bufferBefore = (int)$request->input('buffer_before_minutes', 0);
        $bufferAfter = (int)$request->input('buffer_after_minutes', 0);
        $price = trim((string)$request->input('price_cents', ''));
        $allowSpecific = (string)$request->input('allow_specific_professional', '0') === '1';
        $procedureIdRaw = trim((string)$request->input('procedure_id', ''));
        $categoryIdRaw = trim((string)$request->input('category_id', ''));

        if ($name === '' || $duration <= 0) {
            return $this->redirect('/services');
        }

        $priceCents = null;
        if ($price !== '') {
            $priceCents = max(0, (int)$price);
        }

        $procedureId = null;
        if ($procedureIdRaw !== '') {
            $procedureId = max(1, (int)$procedureIdRaw);
        }

        $categoryId = null;
        if ($categoryIdRaw !== '') {
            $categoryId = max(1, (int)$categoryIdRaw);
        }

        /** @var ?int $procedureId */

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ServiceCatalogRepository($this->container->get(\PDO::class));
        $id = $repo->create($clinicId, $procedureId, $categoryId, $name, $duration, $bufferBefore, $bufferAfter, $priceCents, $allowSpecific);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'scheduling.service_create', [
            'service_id' => $id,
            'procedure_id' => $procedureId,
            'category_id' => $categoryId,
            'name' => $name,
            'duration_minutes' => $duration,
            'buffer_before_minutes' => $bufferBefore,
            'buffer_after_minutes' => $bufferAfter,
            'price_cents' => $priceCents,
            'allow_specific_professional' => $allowSpecific,
        ], $request->ip());

        return $this->redirect('/services');
    }
}
