<?php

declare(strict_types=1);

namespace App\Config;

final class AppConfig
{
    public function __construct(
        public readonly array $corsAllowedOrigins,
        public readonly int $rateLimitWindowSeconds,
        public readonly int $rateLimitDefaultLimit,
        public readonly int $rateLimitStrictLimit,
        public readonly array $rateLimitStrictPaths,
    ) {
    }

    public static function fromConfig(Config $config): self
    {
        $origins = $config->get('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://127.0.0.1:5173') ?? '';

        return new self(
            array_values(array_filter(array_map('trim', explode(',', $origins)))),
            60,
            60,
            5,
            ['/api/auth/login', '/api/auth/register'],
        );
    }
}
