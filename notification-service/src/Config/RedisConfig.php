<?php

declare(strict_types=1);

namespace App\Config;

final class RedisConfig
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly ?string $password,
        public readonly int $database,
    ) {
    }
}
