<?php

declare(strict_types=1);

namespace App\Messaging;

use App\Config\RabbitMqConfig;
use App\Config\RetryConfig;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Объявление retry/DLQ-топологии — вызывается и консьюмером, и паблишером ретраев,
 * поэтому вынесено в одно место, чтобы оба всегда объявляли одни и те же exchange/очереди.
 */
final class RetryTopology
{
    public static function declare(AMQPChannel $channel, RabbitMqConfig $config, RetryConfig $retryConfig): void
    {
        $channel->exchange_declare($config->exchange, 'topic', false, true, false);
        $channel->queue_declare($config->queue, false, true, false, false);

        // Fanout, в который RabbitMQ кладёт сообщение после истечения TTL retry-очереди — оттуда оно
        // возвращается в основную очередь и обрабатывается заново, как обычное событие
        $channel->exchange_declare($config->requeueExchange, 'fanout', false, true, false);
        $channel->queue_bind($config->queue, $config->requeueExchange);

        // Отдельная очередь на каждую попытку — свой TTL, общий dead-letter-exchange = requeue
        $channel->exchange_declare($config->retryExchange, 'direct', false, true, false);

        foreach ($retryConfig->delaysMs as $index => $delayMs) {
            $attempt = $index + 1;

            $channel->queue_declare($config->retryQueue($attempt), false, true, false, false, false, new AMQPTable([
                'x-message-ttl' => $delayMs,
                'x-dead-letter-exchange' => $config->requeueExchange,
            ]));

            $channel->queue_bind($config->retryQueue($attempt), $config->retryExchange, (string) $attempt);
        }

        $channel->exchange_declare($config->dlxExchange, 'fanout', false, true, false);
        $channel->queue_declare($config->dlqQueue, false, true, false, false);
        $channel->queue_bind($config->dlqQueue, $config->dlxExchange);
    }
}
