<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;

final class CsrfMiddleware implements MiddlewareInterface
{
    /** @param array{enabled:bool,token_key:string} $config */
    public function __construct(private readonly array $config) {}

    public function handle(Request $request, callable $next): Response
    {
        if (!$this->config['enabled']) {
            return $next($request);
        }

        $path = $request->path();
        if (str_starts_with($path, '/api')) {
            return $next($request);
        }

        if (str_starts_with($path, '/webhooks')) {
            return $next($request);
        }

        if (!isset($_SESSION[$this->config['token_key']])) {
            $_SESSION[$this->config['token_key']] = bin2hex(random_bytes(32));
        }

        if ($request->method() === 'POST') {
            $token = (string)$request->input($this->config['token_key'], '');
            if (!hash_equals((string)$_SESSION[$this->config['token_key']], $token)) {
                return Response::html('CSRF token inválido', 419);
            }
        }

        return $next($request);
    }
}
