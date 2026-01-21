<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;

final class MiddlewarePipeline
{
    /** @var list<MiddlewareInterface> */
    private array $middlewares;

    /** @param list<MiddlewareInterface> $middlewares */
    public function __construct(array $middlewares)
    {
        $this->middlewares = $middlewares;
    }

    /** @param callable(Request):Response $last */
    public function handle(Request $request, callable $last): Response
    {
        $next = $last;

        foreach (array_reverse($this->middlewares) as $middleware) {
            $currentNext = $next;
            $next = fn (Request $request) => $middleware->handle($request, $currentNext);
        }

        return $next($request);
    }
}
