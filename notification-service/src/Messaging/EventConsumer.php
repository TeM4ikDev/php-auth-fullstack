<?php

declare(strict_types=1);

namespace App\Messaging;

use App\Config\RabbitMqConfig;
use App\Config\RetryConfig;
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
        private readonly RetryConfig $retryConfig,
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

        RetryTopology::declare($channel, $this->config, $this->retryConfig);

        foreach (self::ROUTING_KEYS as $routingKey) {
            $channel->queue_bind($this->config->queue, $this->config->exchange, $routingKey);
        }

        $channel->basic_qos(0, 1, false);
        $channel->basic_consume($this->config->queue, '', false, false, false, false, $this->handle(...));

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    private function handle(AMQPMessage $message): void
    {
        $payload = json_decode($message->getBody(), true);
        $payload = is_array($payload) ? $payload : [];

        $headers = $message->has('application_headers')
            ? $message->get('application_headers')->getNativeData()
            : [];

        // x-event переопределяет routing key — после dead-letter'а через fanout requeue исходный routing
        // key теряется (сообщение приходит с routing key retry-очереди), поэтому имя события несём в заголовке
        $event = isset($headers['x-event']) ? (string) $headers['x-event'] : $message->getRoutingKey();
        $attempt = isset($headers['x-attempt']) ? (int) $headers['x-attempt'] : 1;
        $notificationId = isset($headers['x-notification-id']) ? (string) $headers['x-notification-id'] : null;

        $handler = match ($event) {
            'user.registered' => $this->userRegistered,
            'order.paid' => $this->orderPaid,
            'order.confirmed' => $this->orderConfirmed,
            'order.cancelled' => $this->orderCancelled,
            default => null,
        };

        $handler?->handle($payload, $attempt, $notificationId);

        // Успех/неуспех уже зафиксирован статусом в Mongo, а retry поставлен в очередь при необходимости
        $message->ack();
    }
}
