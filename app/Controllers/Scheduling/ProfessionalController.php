<?php

declare(strict_types=1);

namespace App\Controllers\Scheduling;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\AuditLogRepository;
use App\Repositories\AdminUserRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\RoleRepository;
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

        $usersRepo = new AdminUserRepository($this->container->get(\PDO::class));
        $users = $usersRepo->listByClinic($clinicId, 500, 0);

        return $this->view('scheduling/professionals', [
            'items' => $items,
            'users' => $users,
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('professionals.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $specialty = trim((string)$request->input('specialty', ''));
        $linkMode = trim((string)$request->input('link_mode', 'existing'));
        $linkUserId = (int)$request->input('user_id', 0);

        $newUserName = trim((string)$request->input('new_user_name', ''));
        $newUserEmail = trim((string)$request->input('new_user_email', ''));
        $newUserPassword = (string)$request->input('new_user_password', '');
        $allowOnline = (string)$request->input('allow_online_booking', '0') === '1';

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $usersRepo = new AdminUserRepository($pdo);
        $roleRepo = new RoleRepository($pdo);

        $finalUserId = null;
        $professionalName = '';

        if ($linkMode === 'new') {
            if ($newUserName === '' || $newUserEmail === '' || trim($newUserPassword) === '') {
                return $this->redirect('/professionals?error=' . urlencode('Preencha nome, e-mail e senha do usuário.'));
            }
            if (!filter_var($newUserEmail, FILTER_VALIDATE_EMAIL)) {
                return $this->redirect('/professionals?error=' . urlencode('E-mail inválido.'));
            }

            $passwordHash = password_hash($newUserPassword, PASSWORD_BCRYPT);
            if ($passwordHash === false) {
                return $this->redirect('/professionals?error=' . urlencode('Falha ao salvar senha.'));
            }

            $finalUserId = $usersRepo->create($clinicId, $newUserName, $newUserEmail, $passwordHash);

            $roleId = $roleRepo->findIdByCode($clinicId, 'professional');
            if ($roleId !== null && $roleId > 0) {
                $roleRepo->assignRoleToUser($clinicId, $finalUserId, $roleId);
            }

            $professionalName = $newUserName;
        } else {
            if ($linkUserId <= 0) {
                return $this->redirect('/professionals?error=' . urlencode('Selecione um usuário para vincular.'));
            }

            $u = $usersRepo->findById($clinicId, $linkUserId);
            if ($u === null) {
                return $this->redirect('/professionals?error=' . urlencode('Usuário não encontrado.'));
            }

            $finalUserId = $linkUserId;
            $professionalName = trim((string)($u['name'] ?? ''));
            if ($professionalName === '') {
                $professionalName = 'Profissional';
            }
        }

        $repo = new ProfessionalRepository($pdo);

        if ($finalUserId !== null && $finalUserId > 0) {
            $existing = $repo->findByUserId($clinicId, $finalUserId);
            if ($existing !== null) {
                return $this->redirect('/professionals?error=' . urlencode('Este usuário já está vinculado a um profissional.'));
            }
        }

        $id = $repo->create($clinicId, $finalUserId, $professionalName, $specialty, $allowOnline);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'scheduling.professional_create', [
            'professional_id' => $id,
            'user_id' => $finalUserId,
            'name' => $professionalName,
            'specialty' => $specialty,
            'allow_online_booking' => $allowOnline,
        ], $request->ip());

        return $this->redirect('/professionals');
    }

    public function edit(Request $request)
    {
        $this->authorize('professionals.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/professionals');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ProfessionalRepository($this->container->get(\PDO::class));
        $row = $repo->findById($clinicId, $id);
        if ($row === null) {
            return $this->redirect('/professionals?error=' . urlencode('Profissional não encontrado.'));
        }

        return $this->view('scheduling/professionals-edit', [
            'row' => $row,
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('professionals.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $name = trim((string)$request->input('name', ''));
        $specialty = trim((string)$request->input('specialty', ''));
        $allowOnline = (string)$request->input('allow_online_booking', '0') === '1';

        if ($id <= 0 || $name === '') {
            return $this->redirect('/professionals?error=' . urlencode('Preencha o nome.'));
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new ProfessionalRepository($pdo);
        $existing = $repo->findById($clinicId, $id);
        if ($existing === null) {
            return $this->redirect('/professionals?error=' . urlencode('Profissional não encontrado.'));
        }

        $repo->update($clinicId, $id, $name, $specialty === '' ? null : $specialty, $allowOnline);

        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'scheduling.professional_update', [
            'professional_id' => $id,
            'name' => $name,
            'specialty' => $specialty,
            'allow_online_booking' => $allowOnline,
        ], $request->ip());

        return $this->redirect('/professionals');
    }

    public function delete(Request $request)
    {
        $this->authorize('professionals.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/professionals');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new ProfessionalRepository($pdo);
        $existing = $repo->findById($clinicId, $id);
        if ($existing === null) {
            return $this->redirect('/professionals?error=' . urlencode('Profissional não encontrado.'));
        }

        $repo->softDelete($clinicId, $id);

        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'scheduling.professional_delete', [
            'professional_id' => $id,
        ], $request->ip());

        return $this->redirect('/professionals');
    }
}
