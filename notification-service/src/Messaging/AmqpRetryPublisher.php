<?php

declare(strict_types=1);

namespace App\Messaging;

use App\Config\RabbitMqConfig;
use App\Config\RetryConfig;
use App\Dto\RetryMessageDto;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

final class AmqpRetryPublisher implements RetryPublisherInterface
{
    public function __construct(
        private readonly RabbitMqConnectionFactory $connections,
        private readonly RabbitMqConfig $config,
        private readonly RetryConfig $retryConfig,
    ) {
    }

    public function scheduleRetry(RetryMessageDto $message): void
    {
        $this->publishToExchange(
            $this->config->retryExchange,
            (string) $message->failedAttempt,
            $message->payload,
            $message->event,
            $message->notificationId,
            $message->failedAttempt + 1,
        );
    }

    public function sendToDlq(RetryMessageDto $message): void
    {
        $this->publishToExchange(
            $this->config->dlxExchange,
            '',
            $message->payload,
            $message->event,
            $message->notificationId,
            $message->failedAttempt,
        );
    }

    public function republish(string $notificationId, string $event, array $payload): void
    {
        $this->publishToExchange($this->config->exchange, $event, $payload, $event, $notificationId, 1);
    }

    private function publishToExchange(
        string $exchange,
        string $routingKey,
        array $payload,
        string $event,
        string $notificationId,
        int $attemptHeader,
    ): void {
        $connection = $this->connections->create();

        try {
            $channel = $connection->channel();
            RetryTopology::declare($channel, $this->config, $this->retryConfig);

            $message = new AMQPMessage(
                (string) json_encode($payload, JSON_THROW_ON_ERROR),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'application_headers' => new AMQPTable([
                        'x-event' => $event,
                        'x-notification-id' => $notificationId,
                        'x-attempt' => $attemptHeader,
                    ]),
                ],
            );

            $channel->basic_publish($message, $exchange, $routingKey);
            $channel->close();
        } finally {
            $connection->close();
        }
    }
}
