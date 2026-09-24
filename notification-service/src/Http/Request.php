<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $headers,
        private readonly string $rawBody,
        private readonly array $query = [],
        private readonly array $attributes = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = self::normalizePath($uri);
        $rawBody = (string) file_get_contents('php://input');

        return new self($method, $path, self::resolveHeaders(), $rawBody, self::resolveQuery($uri));
    }

    /** Фабрика для тестов: позволяет собрать запрос без суперглобалов. */
    public static function create(
        string $method,
        string $path,
        array $headers = [],
        string $rawBody = '',
        array $query = [],
        array $attributes = [],
    ): self {
        return new self(strtoupper($method), self::normalizePath($path), $headers, $rawBody, $query, $attributes);
    }

    public function withAttribute(string $key, mixed $value): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->headers,
            $this->rawBody,
            $this->query,
            [...$this->attributes, $key => $value],
        );
    }

    public function query(string $key, ?string $default = null): ?string
    {
        $value = $this->query[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : $default;
    }

    public function routeParam(string $key): ?string
    {
        $params = $this->attributes['routeParams'] ?? [];

        return is_array($params) && isset($params[$key]) ? (string) $params[$key] : null;
    }

    public function attribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function json(): array
    {
        $decoded = json_decode($this->rawBody, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('authorization', '');

        if ($header !== null && preg_match('/^Bearer\s+(.+)$/i', $header, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    public function ip(): string
    {
        return isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
    }

    private static function resolveQuery(string $uri): array
    {
        $queryString = parse_url($uri, PHP_URL_QUERY);

        if (!is_string($queryString) || $queryString === '') {
            return [];
        }

        parse_str($queryString, $query);

        return $query;
    }

    private static function normalizePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? rawurldecode($path) : '/';
        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }

    private static function resolveHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        if (!isset($headers['authorization']) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $headers[strtolower((string) $name)] = (string) $value;
            }
        }

        return $headers;
    }
}
