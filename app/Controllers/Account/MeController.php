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
            'success' => null,
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
            $user = $users->findById($userId);
            return $this->view('account/me', [
                'user' => $user,
                'error' => 'Preencha os campos obrigatórios.',
                'success' => null,
            ]);
        }

        $users->updateSelfProfile($userId, $name, $email);

        $user = $users->findById($userId);

        return $this->view('account/me', [
            'user' => $user,
            'error' => null,
            'success' => 'Perfil atualizado com sucesso.',
        ]);
    }
}
