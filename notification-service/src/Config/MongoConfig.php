<?php

declare(strict_types=1);

namespace App\Config;

final class MongoConfig
{
    public function __construct(
        public readonly string $uri,
        public readonly string $database,
    ) {
    }
}
