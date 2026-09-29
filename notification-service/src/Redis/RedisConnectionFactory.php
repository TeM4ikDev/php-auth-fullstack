<?php

declare(strict_types=1);

namespace App\Redis;

use App\Config\RedisConfig;
use Predis\Client;

final class RedisConnectionFactory
{
    public function __construct(private readonly RedisConfig $config)
    {
    }

    public function create(): Client
    {
        return new Client([
            'host' => $this->config->host,
            'port' => $this->config->port,
            'password' => $this->config->password,
            'database' => $this->config->database,
        ]);
    }
}
