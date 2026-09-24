<?php

declare(strict_types=1);

namespace App\Messaging;

use App\Config\RabbitMqConfig;
use App\EventHandler\OrderCancelledHandler;
use App\EventHandler\OrderConfirmedHandler;
use App\EventHandler\OrderPaidHandler;
use App\EventHandler\UserRegisteredHandler;
use PhpAmqpLib\Message\AMQPMessage;

final class EventConsumer
{
    private const ROUTING_KEYS = ['user.registered', 'order.paid', 'order.confirmed', 'order.cancelled'];

    public function __construct(
        private readonly RabbitMqConnectionFactory $connections,
        private readonly RabbitMqConfig $config,
        private readonly UserRegisteredHandler $userRegistered,
        private readonly OrderPaidHandler $orderPaid,
        private readonly OrderConfirmedHandler $orderConfirmed,
        private readonly OrderCancelledHandler $orderCancelled,
    ) {
    }

    public function run(): void
    {
        $connection = $this->connections->create();
        $channel = $connection->channel();

        $channel->exchange_declare($this->config->exchange, 'topic', false, true, false);
        $channel->queue_declare($this->config->queue, false, true, false, false);

        foreach (self::ROUTING_KEYS as $routingKey) {
            $channel->queue_bind($this->config->queue, $this->config->exchange, $routingKey);
        }

        $channel->basic_consume($this->config->queue, '', false, false, false, false, $this->handle(...));

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    private function handle(AMQPMessage $message): void
    {
        $payload = json_decode($message->getBody(), true);

        $handler = match ($message->getRoutingKey()) {
            'user.registered' => $this->userRegistered,
            'order.paid' => $this->orderPaid,
            'order.confirmed' => $this->orderConfirmed,
            'order.cancelled' => $this->orderCancelled,
            default => null,
        };

        $handler?->handle(is_array($payload) ? $payload : []);

        $message->ack();
    }
}
