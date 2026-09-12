<?php

declare(strict_types=1);

namespace App\Config;

use RuntimeException;

final class Config
{
    private static $env;

    public static function get(string $key, string $default = ''): string
    {
        $value = getenv($key);

        if ($value !== false && $value !== '') {
            return $value;
        }

        $env = self::env();

        return (isset($env[$key]) && $env[$key] !== '') ? $env[$key] : $default;
    }

    public static function database(): array
    {
        $url = self::get('DATABASE_URL');

        if ($url !== '') {
            return self::parseDatabaseUrl($url);
        }

        // Имена совпадают с POSTGRES_* из compose.yaml — один и тот же .env
        // задаёт пароль и для контейнера, и для локального composer serve,
        // без отдельной копии в DATABASE_URL.
        return [
            'dsn' => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                self::get('DB_HOST', 'localhost'),
                self::get('DB_PORT', '55432'),
                self::get('POSTGRES_DB', 'php_auth')
            ),
            'user' => self::get('POSTGRES_USER', 'postgres'),
            'password' => self::get('POSTGRES_PASSWORD', ''),
        ];
    }

    public static function jwtSecret(): string
    {
        return self::get('JWT_SECRET', 'dev-secret-change-me');
    }

    public static function jwtTtl(): int
    {
        return (int) self::get('JWT_TTL', '86400');
    }

    private static function parseDatabaseUrl(string $url): array
    {
        $parts = parse_url($url);

        if ($parts === false || !isset($parts['host'])) {
            throw new RuntimeException('DATABASE_URL не разобрать: ожидается postgresql://user:pass@host:port/db');
        }

        $scheme = strtolower($parts['scheme'] ?? 'pgsql');
        $driver = in_array($scheme, ['postgres', 'postgresql', 'pgsql'], true) ? 'pgsql' : $scheme;

        $database = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
        $port = (string) ($parts['port'] ?? ($driver === 'pgsql' ? 5432 : 3306));

        $dsn = sprintf('%s:host=%s;port=%s;dbname=%s', $driver, $parts['host'], $port, $database);

        if ($driver === 'mysql') {
            $dsn .= ';charset=utf8mb4';
        }

        parse_str($parts['query'] ?? '', $query);

        if ($driver === 'pgsql' && isset($query['sslmode']) && is_string($query['sslmode'])) {
            $dsn .= ';sslmode=' . $query['sslmode'];
        }

        return [
            'dsn' => $dsn,
            'user' => rawurldecode($parts['user'] ?? ''),
            'password' => rawurldecode($parts['pass'] ?? ''),
        ];
    }

    private static function env(): array
    {
        if (self::$env !== null) {
            return self::$env;
        }

        self::$env = [];
        // На уровень выше backend/ — .env один на весь репозиторий,
        // тот же файл читает docker compose для подстановки ${...}.
        $path = \dirname(__DIR__, 3) . '/.env';

        if (!is_readable($path)) {
            return self::$env;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $value = trim($value);

            if (strlen($value) > 1 && ($value[0] === '"' || $value[0] === "'") && $value[0] === substr($value, -1)) {
                $value = substr($value, 1, -1);
            }

            self::$env[trim($key)] = $value;
        }

        return self::$env;
    }
}
