<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;

final class Router
{
    /** @var array<string, array<string, array{class-string, string}>> */
    private array $routes = [];

    public function __construct(private readonly Container $container) {}

    public function get(string $path, array $handler): void
    {
        $this->map('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->map('POST', $path, $handler);
    }

    private function map(string $method, string $path, array $handler): void
    {
        $path = rtrim($path, '/') ?: '/';
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            return Response::html('Not Found', 404);
        }

        [$class, $action] = $handler;

        $controller = new $class($this->container);

        if (!method_exists($controller, $action)) {
            return Response::html('Not Found', 404);
        }

        return $controller->{$action}($request);
    }
}
