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
        private readonly array $attributes = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path = self::normalizePath((string) ($_SERVER['REQUEST_URI'] ?? '/'));
        $rawBody = (string) file_get_contents('php://input');

        return new self($method, $path, self::resolveHeaders(), $rawBody);
    }

    public function withAttribute(string $key, mixed $value): self
    {
        return new self($this->method, $this->path, $this->headers, $this->rawBody, [...$this->attributes, $key => $value]);
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
