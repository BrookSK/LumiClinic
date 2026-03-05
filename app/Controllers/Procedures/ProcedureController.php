<?php

declare(strict_types=1);

namespace App\Controllers\Procedures;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\ProcedureProtocolRepository;
use App\Repositories\ProcedureProtocolStepRepository;
use App\Repositories\ProcedureRepository;
use App\Services\Auth\AuthService;

final class ProcedureController extends Controller
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
        $this->authorize('procedures.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $repo = new ProcedureRepository($pdo);
        $rows = $repo->listActiveByClinic($clinicId);

        $avgByProcedure = $repo->avgRealDurationMinutesByProcedure($clinicId, array_map(static fn ($r) => (int)$r['id'], $rows));

        return $this->view('procedures/index', [
            'items' => $rows,
            'avg_duration_by_procedure' => $avgByProcedure,
            'error' => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('procedures.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $name = trim((string)$request->input('name', ''));
        $contra = trim((string)$request->input('contraindications', ''));
        $pre = trim((string)$request->input('pre_guidelines', ''));
        $post = trim((string)$request->input('post_guidelines', ''));

        if ($name === '') {
            return $this->redirect('/procedures?error=' . urlencode('Informe o nome.'));
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ProcedureRepository($this->container->get(\PDO::class));
        $id = $repo->create(
            $clinicId,
            $name,
            ($contra === '' ? null : $contra),
            ($pre === '' ? null : $pre),
            ($post === '' ? null : $post)
        );

        return $this->redirect('/procedures/edit?id=' . $id . '&success=' . urlencode('Criado.'));
    }

    public function edit(Request $request)
    {
        $this->authorize('procedures.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/procedures');
        }

        $pdo = $this->container->get(\PDO::class);

        $repo = new ProcedureRepository($pdo);
        $procedure = $repo->findById($clinicId, $id);
        if ($procedure === null) {
            return $this->redirect('/procedures');
        }

        $protocolRepo = new ProcedureProtocolRepository($pdo);
        $stepRepo = new ProcedureProtocolStepRepository($pdo);

        $protocols = $protocolRepo->listByProcedure($clinicId, $id);
        $stepsByProtocol = [];
        foreach ($protocols as $p) {
            $pid = (int)$p['id'];
            $stepsByProtocol[(string)$pid] = $stepRepo->listByProtocol($clinicId, $pid);
        }

        $avg = $repo->avgRealDurationMinutesByProcedure($clinicId, [$id]);
        $avgMin = $avg[(string)$id] ?? null;

        return $this->view('procedures/edit', [
            'procedure' => $procedure,
            'avg_real_duration_minutes' => $avgMin,
            'protocols' => $protocols,
            'steps_by_protocol' => $stepsByProtocol,
            'error' => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
            'csrf' => $_SESSION['_csrf'] ?? '',
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('procedures.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/procedures');
        }

        $name = trim((string)$request->input('name', ''));
        $contra = trim((string)$request->input('contraindications', ''));
        $pre = trim((string)$request->input('pre_guidelines', ''));
        $post = trim((string)$request->input('post_guidelines', ''));
        $status = trim((string)$request->input('status', 'active'));

        if ($name === '') {
            return $this->redirect('/procedures/edit?id=' . $id . '&error=' . urlencode('Informe o nome.'));
        }

        $repo = new ProcedureRepository($this->container->get(\PDO::class));
        $repo->update(
            $clinicId,
            $id,
            $name,
            ($contra === '' ? null : $contra),
            ($pre === '' ? null : $pre),
            ($post === '' ? null : $post),
            $status
        );

        return $this->redirect('/procedures/edit?id=' . $id . '&success=' . urlencode('Salvo.'));
    }

    public function protocolCreate(Request $request)
    {
        $this->authorize('procedures.manage');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $procedureId = (int)$request->input('procedure_id', 0);
        $name = trim((string)$request->input('name', ''));
        $notes = trim((string)$request->input('notes', ''));
        $sortOrder = (int)$request->input('sort_order', 0);

        if ($procedureId <= 0 || $name === '') {
            return $this->redirect('/procedures');
        }

        $repo = new ProcedureRepository($this->container->get(\PDO::class));
        if ($repo->findById($clinicId, $procedureId) === null) {
            return $this->redirect('/procedures');
        }

        $protocolRepo = new ProcedureProtocolRepository($this->container->get(\PDO::class));
        $protocolRepo->create($clinicId, $procedureId, $name, ($notes === '' ? null : $notes), $sortOrder);

        return $this->redirect('/procedures/edit?id=' . $procedureId . '&success=' . urlencode('Protocolo criado.'));
    }

    public function protocolUpdate(Request $request)
    {
        $this->authorize('procedures.manage');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $procedureId = (int)$request->input('procedure_id', 0);
        $id = (int)$request->input('id', 0);
        $name = trim((string)$request->input('name', ''));
        $notes = trim((string)$request->input('notes', ''));
        $sortOrder = (int)$request->input('sort_order', 0);
        $status = trim((string)$request->input('status', 'active'));

        if ($procedureId <= 0 || $id <= 0 || $name === '') {
            return $this->redirect('/procedures');
        }

        $protocolRepo = new ProcedureProtocolRepository($this->container->get(\PDO::class));
        $protocol = $protocolRepo->findById($clinicId, $id);
        if ($protocol === null || (int)$protocol['procedure_id'] !== $procedureId) {
            return $this->redirect('/procedures/edit?id=' . $procedureId . '&error=' . urlencode('Protocolo inválido.'));
        }

        $protocolRepo->update($clinicId, $id, $name, ($notes === '' ? null : $notes), $sortOrder, $status);

        return $this->redirect('/procedures/edit?id=' . $procedureId . '&success=' . urlencode('Protocolo salvo.'));
    }

    public function protocolDelete(Request $request)
    {
        $this->authorize('procedures.manage');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $procedureId = (int)$request->input('procedure_id', 0);
        $id = (int)$request->input('id', 0);

        if ($procedureId <= 0 || $id <= 0) {
            return $this->redirect('/procedures');
        }

        $protocolRepo = new ProcedureProtocolRepository($this->container->get(\PDO::class));
        $protocol = $protocolRepo->findById($clinicId, $id);
        if ($protocol === null || (int)$protocol['procedure_id'] !== $procedureId) {
            return $this->redirect('/procedures/edit?id=' . $procedureId);
        }

        $protocolRepo->softDelete($clinicId, $id);

        return $this->redirect('/procedures/edit?id=' . $procedureId . '&success=' . urlencode('Protocolo removido.'));
    }

    public function stepCreate(Request $request)
    {
        $this->authorize('procedures.manage');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $procedureId = (int)$request->input('procedure_id', 0);
        $protocolId = (int)$request->input('protocol_id', 0);
        $title = trim((string)$request->input('title', ''));
        $durationMinutesRaw = trim((string)$request->input('duration_minutes', ''));
        $notes = trim((string)$request->input('notes', ''));
        $sortOrder = (int)$request->input('sort_order', 0);

        if ($procedureId <= 0 || $protocolId <= 0 || $title === '') {
            return $this->redirect('/procedures');
        }

        $protocolRepo = new ProcedureProtocolRepository($this->container->get(\PDO::class));
        $protocol = $protocolRepo->findById($clinicId, $protocolId);
        if ($protocol === null || (int)$protocol['procedure_id'] !== $procedureId) {
            return $this->redirect('/procedures/edit?id=' . $procedureId . '&error=' . urlencode('Protocolo inválido.'));
        }

        $durationMinutes = null;
        if ($durationMinutesRaw !== '') {
            $durationMinutes = max(0, (int)$durationMinutesRaw);
        }

        $repo = new ProcedureProtocolStepRepository($this->container->get(\PDO::class));
        $repo->create($clinicId, $protocolId, $title, $durationMinutes, ($notes === '' ? null : $notes), $sortOrder);

        return $this->redirect('/procedures/edit?id=' . $procedureId . '&success=' . urlencode('Etapa criada.'));
    }

    public function stepUpdate(Request $request)
    {
        $this->authorize('procedures.manage');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $procedureId = (int)$request->input('procedure_id', 0);
        $id = (int)$request->input('id', 0);
        $protocolId = (int)$request->input('protocol_id', 0);
        $title = trim((string)$request->input('title', ''));
        $durationMinutesRaw = trim((string)$request->input('duration_minutes', ''));
        $notes = trim((string)$request->input('notes', ''));
        $sortOrder = (int)$request->input('sort_order', 0);

        if ($procedureId <= 0 || $id <= 0 || $protocolId <= 0 || $title === '') {
            return $this->redirect('/procedures');
        }

        $protocolRepo = new ProcedureProtocolRepository($this->container->get(\PDO::class));
        $protocol = $protocolRepo->findById($clinicId, $protocolId);
        if ($protocol === null || (int)$protocol['procedure_id'] !== $procedureId) {
            return $this->redirect('/procedures/edit?id=' . $procedureId . '&error=' . urlencode('Protocolo inválido.'));
        }

        $repo = new ProcedureProtocolStepRepository($this->container->get(\PDO::class));
        $step = $repo->findById($clinicId, $id);
        if ($step === null || (int)$step['protocol_id'] !== $protocolId) {
            return $this->redirect('/procedures/edit?id=' . $procedureId . '&error=' . urlencode('Etapa inválida.'));
        }

        $durationMinutes = null;
        if ($durationMinutesRaw !== '') {
            $durationMinutes = max(0, (int)$durationMinutesRaw);
        }

        $repo->update($clinicId, $id, $title, $durationMinutes, ($notes === '' ? null : $notes), $sortOrder);

        return $this->redirect('/procedures/edit?id=' . $procedureId . '&success=' . urlencode('Etapa salva.'));
    }

    public function stepDelete(Request $request)
    {
        $this->authorize('procedures.manage');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $procedureId = (int)$request->input('procedure_id', 0);
        $id = (int)$request->input('id', 0);
        $protocolId = (int)$request->input('protocol_id', 0);

        if ($procedureId <= 0 || $id <= 0 || $protocolId <= 0) {
            return $this->redirect('/procedures');
        }

        $repo = new ProcedureProtocolStepRepository($this->container->get(\PDO::class));
        $step = $repo->findById($clinicId, $id);
        if ($step === null || (int)$step['protocol_id'] !== $protocolId) {
            return $this->redirect('/procedures/edit?id=' . $procedureId);
        }

        $repo->softDelete($clinicId, $id);

        return $this->redirect('/procedures/edit?id=' . $procedureId . '&success=' . urlencode('Etapa removida.'));
    }
}
