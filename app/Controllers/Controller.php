<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container\Container;
use App\Core\Http\Response;
use App\Core\View\View;
use App\Services\Authorization\AuthorizationService;

abstract class Controller
{
    public function __construct(protected readonly Container $container) {}

    /** @param array<string, mixed> $data */
    protected function view(string $view, array $data = []): Response
    {
        return Response::html(View::render($view, $data));
    }

    protected function redirect(string $to): Response
    {
        return Response::redirect($to);
    }

    protected function authorize(string $permissionCode): void
    {
        $authz = new AuthorizationService($this->container);

        if (!$authz->check($permissionCode)) {
            throw new \RuntimeException('Acesso negado.');
        }
    }
}
