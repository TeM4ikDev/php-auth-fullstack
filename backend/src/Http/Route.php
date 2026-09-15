<?php

declare(strict_types=1);

namespace App\Http;

final class Route
{
    private array $middleware = [];

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $handler,
    ) {
    }

    public function middleware(string ...$middleware): self
    {
        foreach ($middleware as $item) {
            $this->middleware[] = $item;
        }

        return $this;
    }

    public function middlewareStack(): array
    {
        return $this->middleware;
    }
}
