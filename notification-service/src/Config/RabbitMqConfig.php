<?php

declare(strict_types=1);

namespace App\Config;

final class RabbitMqConfig
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $user,
        public readonly string $password,
        public readonly string $exchange,
        public readonly string $queue,
    ) {
    }

    /** exchange/имя совпадают с publisher-конфигом в auth-service — это один и тот же топик. */
    public static function fromConfig(Config $config): self
    {
        return new self(
            $config->get('RABBITMQ_HOST', 'rabbitmq'),
            $config->getInt('RABBITMQ_PORT', 5672),
            $config->get('RABBITMQ_USER', 'guest'),
            $config->get('RABBITMQ_PASSWORD', 'guest'),
            'app_events',
            'notification_service_events',
        );
    }
}
