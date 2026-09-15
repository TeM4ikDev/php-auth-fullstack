<?php

declare(strict_types=1);

namespace App\Config;

use RuntimeException;

final class DatabaseConfigFactory
{
    public function __construct(private readonly Config $config)
    {
    }

    public function create(): DatabaseConfig
    {
        $url = $this->config->get('DATABASE_URL');

            //->config->get(''

        if ($url !== null) {
            return $this->fromUrl($url);
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $this->config->get('DB_HOST', 'localhost'),
            $this->config->get('DB_PORT', '55432'),
            $this->config->get('POSTGRES_DB', 'php_auth'),
        );

        return new DatabaseConfig(
            $dsn,
            $this->config->get('POSTGRES_USER', 'postgres') ?? 'postgres',
            $this->config->get('POSTGRES_PASSWORD', '') ?? '',
        );
    }

    private function fromUrl(string $url): DatabaseConfig
    {
        $parts = parse_url($url);

        if ($parts === false || !isset($parts['host'])) {
            throw new RuntimeException("DATABASE_URL is malformed: [{$url}].");
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

        return new DatabaseConfig(
            $dsn,
            rawurldecode($parts['user'] ?? ''),
            rawurldecode($parts['pass'] ?? ''),
        );
    }
}
