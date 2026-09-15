<?php

declare(strict_types=1);

namespace App\Config;

final class JwtConfig
{
    public function __construct(
        public readonly string $secret,
        public readonly int $ttlSeconds,
        public readonly string $algorithm = 'HS256',
    ) {
    }

    public static function fromConfig(Config $config): self
    {
        return new self($config->require('JWT_SECRET'), $config->getInt('JWT_TTL', 86400));
    }
}
