<?php

declare(strict_types=1);

namespace App\Config;

use RuntimeException;

final class RedisConfigFactory
{
    public function __construct(private readonly Config $config)
    {
    }

    public function create(): RedisConfig
    {
        $url = $this->config->get('REDIS_URL');

        if ($url !== null) {
            return $this->fromUrl($url);
        }

        return new RedisConfig(
            $this->config->get('REDIS_HOST', 'redis') ?? 'redis',
            $this->config->getInt('REDIS_PORT', 6379),
            $this->config->get('REDIS_PASSWORD'),
            $this->config->getInt('REDIS_DB', 0),
        );
    }

    private function fromUrl(string $url): RedisConfig
    {
        $parts = parse_url($url);

        if ($parts === false || !isset($parts['host'])) {
            throw new RuntimeException("REDIS_URL is malformed: [{$url}].");
        }

        $database = isset($parts['path']) ? (int) ltrim($parts['path'], '/') : 0;

        return new RedisConfig(
            $parts['host'],
            $parts['port'] ?? 6379,
            isset($parts['pass']) ? rawurldecode($parts['pass']) : null,
            $database,
        );
    }
}
