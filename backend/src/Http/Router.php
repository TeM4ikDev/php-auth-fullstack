<?php

declare(strict_types=1);

namespace App\Http;

use App\Container\Container;
use App\Http\Exception\MethodNotAllowedException;
use App\Http\Exception\NotFoundException;

final class Router
{
    private array $routes = [];

    public function __construct(private readonly Container $container)
    {
    }

    public function get(string $path, array $handler): Route
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): Route
    {
        return $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): Route
    {
        $route = new Route($method, $path, $handler);
        $this->routes[] = $route;

        return $route;
    }

    public function dispatch(Request $request): Response
    {
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if ($route->path !== $request->path) {
                continue;
            }

            if ($route->method !== $request->method) {
                $allowedMethods[] = $route->method;

                continue;
            }

            return $this->callHandler($route, $request);
        }

        if ($allowedMethods !== []) {
            throw new MethodNotAllowedException(array_values(array_unique($allowedMethods)));
        }

        throw new NotFoundException(sprintf('Route %s %s not found.', $request->method, $request->path));
    }

    private function callHandler(Route $route, Request $request): Response
    {
        return (new Pipeline($this->container))->run(
            $request,
            $route->middlewareStack(),
            function (Request $request) use ($route): Response {
                [$class, $method] = $route->handler;

                return $this->container->get($class)->{$method}($request);
            },
        );
    }
}
