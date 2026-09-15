<?php

declare(strict_types=1);

namespace App\Config;

final class DatabaseConfig
{
    public function __construct(
        public readonly string $dsn,
        public readonly string $username,
        public readonly string $password,
    ) {
    }
}
