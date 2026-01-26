<?php

declare(strict_types=1);

namespace App\Controllers\Private;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;

final class PrivateTutorialController extends Controller
{
    private function expectedPassword(): string
    {
        $config = $this->container->get('config');
        $private = is_array($config) && isset($config['private']) && is_array($config['private']) ? $config['private'] : [];
        return (string)($private['tutorial_password'] ?? '');
    }

    private function isAuthorized(): bool
    {
        return isset($_SESSION['private_tutorial_ok']) && (int)$_SESSION['private_tutorial_ok'] === 1;
    }

    private function authorizeOrShowLogin(Request $request, string $targetPath): ?Response
    {
        if ($this->isAuthorized()) {
            return null;
        }

        $error = '';

        if ($request->method() === 'POST') {
            $password = (string)$request->input('password', '');
            if ($password !== '' && hash_equals($this->expectedPassword(), $password)) {
                $_SESSION['private_tutorial_ok'] = 1;
                return Response::redirect($targetPath);
            }

            $error = 'Senha inválida.';
        }

        return $this->view('private/tutorial_login', [
            'error' => $error,
            'target' => $targetPath,
        ]);
    }

    public function platform(Request $request)
    {
        $login = $this->authorizeOrShowLogin($request, '/private/tutorial/platform');
        if ($login !== null) {
            return $login;
        }

        return $this->view('private/tutorial_platform');
    }

    public function clinic(Request $request)
    {
        $login = $this->authorizeOrShowLogin($request, '/private/tutorial/clinic');
        if ($login !== null) {
            return $login;
        }

        return $this->view('private/tutorial_clinic');
    }
}
