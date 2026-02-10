<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Middleware\MiddlewarePipeline;
use App\Core\Routing\Router;
use App\Core\View\View;
use App\Middleware\AuthMiddleware;
use App\Middleware\ClinicContextMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\ApiPatientAuthMiddleware;
use App\Middleware\PatientAuthMiddleware;
use App\Middleware\PortalPlanEnforcementMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use App\Middleware\SessionMiddleware;
use App\Middleware\BillingEnforcementMiddleware;
use App\Middleware\PerformanceMonitoringMiddleware;
use App\Services\System\SystemErrorLogService;

final class App
{
    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
        private readonly MiddlewarePipeline $pipeline,
    ) {}

    public function container(): Container
    {
        return $this->container;
    }

    public static function bootstrap(): self
    {
        $container = new Container();

        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $dbConfig = require dirname(__DIR__, 2) . '/config/database.php';

        $container->set('config', fn () => $config);
        $container->set('db.config', fn () => $dbConfig);

        $container->set(\PDO::class, function () use ($dbConfig) {
            $pdo = new \PDO(
                $dbConfig['dsn'],
                $dbConfig['username'],
                $dbConfig['password'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]
            );

            return $pdo;
        });

        $router = new Router($container);
        require dirname(__DIR__, 2) . '/routes/web.php';

        $pipeline = new MiddlewarePipeline([
            new SecurityHeadersMiddleware(),
            new PerformanceMonitoringMiddleware($container),
            new SessionMiddleware($config['session']),
            new RateLimitMiddleware($container),
            new CsrfMiddleware($config['csrf']),
            new AuthMiddleware($container),
            new PortalPlanEnforcementMiddleware($container),
            new PatientAuthMiddleware($container),
            new ApiPatientAuthMiddleware($container),
            new ClinicContextMiddleware($container),
            new BillingEnforcementMiddleware($container),
        ]);

        return new self($container, $router, $pipeline);
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->pipeline->handle($request, fn (Request $request) => $this->router->dispatch($request));
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'Acesso negado.') {
                (new SystemErrorLogService($this->container))->logHttpError(
                    $request,
                    403,
                    'access_denied',
                    'Acesso negado',
                    $e
                );
                return Response::html(View::render('errors/403', ['title' => 'Acesso negado']), 403);
            }

            (new SystemErrorLogService($this->container))->logHttpError(
                $request,
                500,
                'runtime_exception',
                $e->getMessage(),
                $e
            );

            return Response::html(View::render('errors/500', ['title' => 'Algo deu errado']), 500);
        } catch (\Throwable $e) {
            (new SystemErrorLogService($this->container))->logHttpError(
                $request,
                500,
                'exception',
                $e->getMessage(),
                $e
            );

            return Response::html(View::render('errors/500', ['title' => 'Algo deu errado']), 500);
        }
    }
}
