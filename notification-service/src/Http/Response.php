<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    private array $headers;

    public function __construct(
        private readonly string $content,
        private readonly int $status = 200,
        array $headers = [],
    ) {
        $this->headers = $headers;
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return new self($content, $status, $headers + ['Content-Type' => 'application/json; charset=utf-8']);
    }

    public static function noContent(int $status = 204): self
    {
        return new self('', $status);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value, true);
            }
        }

        if ($this->status !== 204 && $this->status !== 304) {
            echo $this->content;
        }
    }
}
