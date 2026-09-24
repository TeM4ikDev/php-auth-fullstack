<?php

declare(strict_types=1);

namespace App\Config;

final class AppConfig
{
    public function __construct(
        public readonly array $corsAllowedOrigins,
    ) {
    }

    public static function fromConfig(Config $config): self
    {
        $origins = $config->get('CORS_ALLOWED_ORIGINS', 'http://localhost:8081,http://localhost:8082') ?? '';

        return new self(
            array_values(array_filter(array_map('trim', explode(',', $origins)))),
        );
    }
}
