<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\UserRepository;
use App\Services\Auth\AuthService;

final class MeController extends Controller
{
    public function index(Request $request)
    {
        $auth = new AuthService($this->container);
        $userId = $auth->userId();
        if ($userId === null) {
            return $this->redirect('/login');
        }

        $pdo = $this->container->get(\PDO::class);
        $user = (new UserRepository($pdo))->findById($userId);

        return $this->view('account/me', [
            'user' => $user,
            'error' => null,
            'success' => trim((string)$request->input('ok', '')) !== ''
                ? (trim((string)$request->input('ok', '')) === 'pwd' ? 'Senha alterada com sucesso.' : 'Perfil atualizado com sucesso.')
                : null,
        ]);
    }

    public function update(Request $request)
    {
        $auth = new AuthService($this->container);
        $userId = $auth->userId();
        if ($userId === null) {
            return $this->redirect('/login');
        }

        $name = trim((string)$request->input('name', ''));
        $email = trim((string)$request->input('email', ''));

        $pdo = $this->container->get(\PDO::class);
        $users = new UserRepository($pdo);

        if ($name === '' || $email === '') {
            return $this->view('account/me', [
                'user' => $users->findById($userId),
                'error' => 'Nome e e-mail são obrigatórios.',
                'success' => null,
            ]);
        }

        $users->updateSelfProfile($userId, $name, $email);

        // Save billing profile fields
        $users->updateBillingProfile($userId, [
            'phone' => preg_replace('/\D+/', '', (string)$request->input('phone', '')),
            'doc_type' => (string)$request->input('doc_type', 'cpf'),
            'doc_number' => preg_replace('/\D+/', '', (string)$request->input('doc_number', '')),
            'postal_code' => preg_replace('/\D+/', '', (string)$request->input('postal_code', '')),
            'address_street' => (string)$request->input('address_street', ''),
            'address_number' => (string)$request->input('address_number', ''),
            'address_complement' => (string)$request->input('address_complement', ''),
            'address_neighborhood' => (string)$request->input('address_neighborhood', ''),
            'address_city' => (string)$request->input('address_city', ''),
            'address_state' => (string)$request->input('address_state', ''),
        ]);

        return $this->redirect('/me?ok=1');
    }

    public function changePassword(Request $request)
    {
        $auth = new AuthService($this->container);
        $userId = $auth->userId();
        if ($userId === null) {
            return $this->redirect('/login');
        }

        $current = (string)$request->input('current_password', '');
        $newPass = (string)$request->input('new_password', '');
        $confirm = (string)$request->input('confirm_password', '');

        $pdo = $this->container->get(\PDO::class);
        $users = new UserRepository($pdo);
        $user = $users->findById($userId);

        if ($user === null) {
            return $this->redirect('/login');
        }

        if ($current === '' || $newPass === '' || $confirm === '') {
            return $this->view('account/me', [
                'user' => $user,
                'error' => 'Preencha todos os campos de senha.',
                'success' => null,
            ]);
        }

        if (!password_verify($current, (string)($user['password_hash'] ?? ''))) {
            return $this->view('account/me', [
                'user' => $user,
                'error' => 'Senha atual incorreta.',
                'success' => null,
            ]);
        }

        if (strlen($newPass) < 8) {
            return $this->view('account/me', [
                'user' => $user,
                'error' => 'A nova senha deve ter pelo menos 8 caracteres.',
                'success' => null,
            ]);
        }

        if ($newPass !== $confirm) {
            return $this->view('account/me', [
                'user' => $user,
                'error' => 'A confirmação de senha não confere.',
                'success' => null,
            ]);
        }

        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        if ($hash === false) {
            return $this->view('account/me', [
                'user' => $user,
                'error' => 'Falha ao gerar hash de senha.',
                'success' => null,
            ]);
        }

        $users->updatePasswordById($userId, $hash);

        return $this->redirect('/me?ok=pwd');
    }
}
