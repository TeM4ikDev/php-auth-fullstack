<?php

declare(strict_types=1);

namespace App\Http;

use App\Container\Container;
use Closure;

final class Pipeline
{
    public function __construct(private readonly Container $container)
    {
    }

    public function run(Request $request, array $middleware, Closure $destination): Response
    {
        $chain = array_reduce(
            array_reverse($middleware),
            function (Closure $next, string $middlewareClass): Closure {
                return fn (Request $request): Response => $this->container
                    ->get($middlewareClass)
                    ->handle($request, $next);
            },
            $destination,
        );

        return $chain($request);
    }
}
