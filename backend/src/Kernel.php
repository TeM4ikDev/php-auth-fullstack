<?php

declare(strict_types=1);

namespace App;

use App\Config\EnvLoader;
use App\Container\Container;
use App\Http\ExceptionHandler;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Pipeline;
use App\Http\Request;
use App\Http\Response;
use App\Http\ResponseFactory;
use App\Http\Router;
use Throwable;

final class Kernel
{
    private const GLOBAL_MIDDLEWARE = [
        CorsMiddleware::class,
        RateLimitMiddleware::class,
    ];

    private function __construct(
        private readonly Container $container,
        private readonly Router $router,
    ) {
    }

    public static function boot(string $basePath): self
    {
        EnvLoader::load($basePath . '/.env');

        $container = new Container();
        (require $basePath . '/config/services.php')($container);

        $router = new Router($container);
        $container->instance(Router::class, $router);

        (function () use ($router, $basePath): void {
            require $basePath . '/routes/api.php';
        })();

        return new self($container, $router);
    }

    public function run(): void
    {
        $request = Request::fromGlobals();
        $handler = new ExceptionHandler($this->container->get(ResponseFactory::class));

        try {
            $response = (new Pipeline($this->container))->run(
                $request,
                self::GLOBAL_MIDDLEWARE,
                fn (Request $request): Response => $this->router->dispatch($request),
            );
        } catch (Throwable $e) {
            $response = $handler->render($e);
        }

        $response->send();
    }
}
