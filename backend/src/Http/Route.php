<?php

declare(strict_types=1);

namespace App\Http;

final class Route
{
    private const PLACEHOLDER_PATTERN = '/\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\\\}/';

    private array $middleware = [];

    private readonly ?string $compiledPattern;

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $handler,
    ) {
        $this->compiledPattern = $this->compile($path);
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

    /**
     * @return array<string, string>|null null — путь не совпал; пустой массив — совпал без параметров.
     */
    public function match(string $path): ?array
    {
        if ($this->compiledPattern === null) {
            return $this->path === $path ? [] : null;
        }

        if (preg_match($this->compiledPattern, $path, $matches) !== 1) {
            return null;
        }

        return array_filter($matches, static fn (string|int $key): bool => is_string($key), ARRAY_FILTER_USE_KEY);
    }

    private function compile(string $path): ?string
    {
        if (!str_contains($path, '{')) {
            return null;
        }

        // preg_quote экранирует фигурные скобки, поэтому в шаблоне ищем уже экранированный вид
        $pattern = preg_replace_callback(
            self::PLACEHOLDER_PATTERN,
            static fn (array $m): string => '(?P<' . $m[1] . '>[^/]+)',
            preg_quote($path, '#'),
        );

        return '#^' . $pattern . '$#';
    }
}
