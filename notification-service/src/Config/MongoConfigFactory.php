<?php

declare(strict_types=1);

namespace App\Config;

final class MongoConfigFactory
{
    public function __construct(private readonly Config $config)
    {
    }

    public function create(): MongoConfig
    {
        return new MongoConfig(
            $this->config->get('MONGO_URI', 'mongodb://mongo:27017') ?? 'mongodb://mongo:27017',
            $this->config->get('MONGO_DATABASE', 'notifications') ?? 'notifications',
        );
    }
}
