<?php

declare(strict_types=1);

namespace App\Messaging;

use App\Config\RabbitMqConfig;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class AmqpEventPublisher implements EventPublisherInterface
{
    public function __construct(private readonly RabbitMqConfig $config)
    {
    }

    public function publish(string $routingKey, array $payload): void
    {
        $connection = new AMQPStreamConnection(
            $this->config->host,
            $this->config->port,
            $this->config->user,
            $this->config->password,
        );

        try {
            $channel = $connection->channel();


            $channel->exchange_declare($this->config->exchange, 'topic', false, true, false);

            $message = new AMQPMessage(
                (string) json_encode($payload, JSON_THROW_ON_ERROR),
                ['content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT],
            );

            $channel->basic_publish($message, $this->config->exchange, $routingKey);
            $channel->close();
        } finally {
            $connection->close();
        }
    }
}
