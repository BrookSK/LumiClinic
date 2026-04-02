<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewareInterface;

final class SessionMiddleware implements MiddlewareInterface
{
    /** @param array{name:string,secure:bool,httponly:bool,samesite:string,path?:string,name_patient?:string} $config */
    public function __construct(private readonly array $config) {}

    public function handle(Request $request, callable $next): Response
    {
        $path = $request->path();
        $isPortal = str_starts_with($path, '/portal');
        $sessionName = $this->config['name'];
        if ($isPortal && isset($this->config['name_patient']) && is_string($this->config['name_patient']) && $this->config['name_patient'] !== '') {
            $sessionName = $this->config['name_patient'];
        }

        $cookiePath = '/';
        if (isset($this->config['path']) && is_string($this->config['path']) && $this->config['path'] !== '') {
            $cookiePath = $this->config['path'];
        }

        $active = session_status() === PHP_SESSION_ACTIVE;
        if ($active && session_name() !== $sessionName) {
            session_write_close();
            $active = false;
        }

        if (!$active) {
            $lifetime = 86400; // 24 horas
            ini_set('session.gc_maxlifetime', (string)$lifetime);

            session_name($sessionName);
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => $cookiePath,
                'secure' => $this->config['secure'],
                'httponly' => $this->config['httponly'],
                'samesite' => $this->config['samesite'],
            ]);
            session_start();
        }

        return $next($request);
    }
}
