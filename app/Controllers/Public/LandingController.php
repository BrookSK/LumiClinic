<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\SaasPlanRepository;
use App\Services\Auth\AuthService;

final class LandingController extends Controller
{
    public function index(Request $request)
    {
        $auth = new AuthService($this->container);

        // Se já está logado, redireciona para o dashboard
        if ($auth->userId() !== null) {
            return $this->redirect('/dashboard');
        }

        $plans = (new SaasPlanRepository($this->container->get(\PDO::class)))->listActive();

        return $this->view('public/landing', [
            'plans' => $plans,
        ]);
    }
}
