<?php

declare(strict_types=1);

namespace App\Config;

use RuntimeException;

final class Config
{
    public function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return ($value === false || $value === '' || $value === null) ? $default : (string) $value;
    }

    public function getInt(string $key, int $default): int
    {
        $value = $this->get($key);

        return $value === null ? $default : (int) $value;
    }

    public function require(string $key): string
    {
        $value = $this->get($key);

        if ($value === null) {
            throw new RuntimeException("Environment variable [{$key}] is required but not set.");
        }

        return $value;
    }
}
