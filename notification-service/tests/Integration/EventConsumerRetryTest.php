<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\MongoConfig;
use App\Config\RabbitMqConfig;
use App\Config\RetryConfig;
use App\Dto\CreateNotificationDto;
use App\Enum\NotificationChannel;
use App\Enum\NotificationStatus;
use App\Messaging\AmqpRetryPublisher;
use App\Messaging\RabbitMqConnectionFactory;
use App\Messaging\RetryTopology;
use App\Repository\MongoNotificationRepository;
use App\Service\MailerInterface;
use App\Service\NotificationProcessor;
use App\Service\NotificationService;
use App\Service\RetryPolicy;
use MongoDB\Client as MongoClient;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Throwable;


final class EventConsumerRetryTest extends IntegrationTestCase
{
    private const QUEUE_PREFIX = 'test_notification';

    private RabbitMqConfig $rabbitConfig;

    private RetryConfig $retryConfig;

    private RabbitMqConnectionFactory $connections;

    private MongoNotificationRepository $notifications;

    protected function setUp(): void
    {
        parent::setUp();

        self::skipUnlessReachable(self::rabbitMqHost(), self::rabbitMqPort(), 'RabbitMQ');

        [$mongoHost, $mongoPort] = self::parseHostPort(self::mongoUri(), 27017);
        self::skipUnlessReachable($mongoHost, $mongoPort, 'MongoDB');

        $this->rabbitConfig = new RabbitMqConfig(
            self::rabbitMqHost(),
            self::rabbitMqPort(),
            self::rabbitMqUser(),
            self::rabbitMqPassword(),
            self::QUEUE_PREFIX . '_events_exchange',
            self::QUEUE_PREFIX . '_events',
            self::QUEUE_PREFIX . '_retry',
            self::QUEUE_PREFIX . '_requeue',
            self::QUEUE_PREFIX . '_dlx',
            self::QUEUE_PREFIX . '_events.dlq',
        );
        // Короткие TTL — тест не должен ждать реальные production-задержки (5с/25с/125с)
        $this->retryConfig = new RetryConfig(2, [200, 200]);
        $this->connections = new RabbitMqConnectionFactory($this->rabbitConfig);
        $this->notifications = new MongoNotificationRepository(new MongoConfig(self::mongoUri(), self::mongoDatabase()));

        $this->purgeMongo();
        $this->purgeRabbitMq();
    }

    protected function tearDown(): void
    {
        $this->purgeMongo();
        $this->purgeRabbitMq();

        parent::tearDown();
    }

    #[Test]
    public function a_permanently_failing_send_progresses_through_retries_into_the_dlq(): void
    {
        $failingMailer = new class implements MailerInterface {
            public function send(string $to, string $subject, string $body): void
            {
                throw new RuntimeException('smtp down (test double)');
            }
        };

        $processor = new NotificationProcessor(
            $this->notifications,
            new NotificationService($failingMailer),
            new RetryPolicy($this->retryConfig),
            new AmqpRetryPublisher($this->connections, $this->rabbitConfig, $this->retryConfig),
        );

        $dto = new CreateNotificationDto(
            'user.registered',
            NotificationChannel::Email,
            'artem@example.com',
            ['email' => 'artem@example.com'],
        );

        $connection = $this->connections->create();
        $channel = $connection->channel();
        RetryTopology::declare($channel, $this->rabbitConfig, $this->retryConfig);

        try {
            // Попытка 1 — падает, уходит в retry.1 (TTL 200мс)
            $processor->process($dto, 1, null);

            $notificationId = $this->notifications->findAll(10, 0, [])[0]->id;
            self::assertSame(NotificationStatus::Retrying, $this->notifications->findById($notificationId)->status);

            $redelivered = $this->waitForMessage($channel, $this->rabbitConfig->queue, 2_000);
            self::assertNotNull($redelivered, 'Ожидалось, что сообщение вернётся в основную очередь после TTL retry.1.');

            $headers = $redelivered->get('application_headers')->getNativeData();
            self::assertSame(2, $headers['x-attempt']);
            $redelivered->ack();

            // Попытка 2 (последняя разрешённая) — снова падает, уходит в retry.2
            $processor->process($dto, (int) $headers['x-attempt'], (string) $headers['x-notification-id']);
            self::assertSame(NotificationStatus::Retrying, $this->notifications->findById($notificationId)->status);

            $redelivered2 = $this->waitForMessage($channel, $this->rabbitConfig->queue, 2_000);
            self::assertNotNull($redelivered2, 'Ожидалось, что сообщение вернётся в основную очередь после TTL retry.2.');

            $headers2 = $redelivered2->get('application_headers')->getNativeData();
            self::assertSame(3, $headers2['x-attempt']);
            $redelivered2->ack();

            // Попытка 3 — попытки исчерпаны (maxAttempts=2), уходит в DLQ
            $processor->process($dto, (int) $headers2['x-attempt'], (string) $headers2['x-notification-id']);

            $final = $this->notifications->findById($notificationId);
            self::assertSame(NotificationStatus::Failed, $final->status);
            self::assertTrue($final->deadLettered);
            self::assertSame(3, $final->attempts);

            $dlqMessage = $this->waitForMessage($channel, $this->rabbitConfig->dlqQueue, 1_000);
            self::assertNotNull($dlqMessage, 'Ожидалось, что сообщение окажется в DLQ.');
            $dlqMessage->ack();
        } finally {
            $channel->close();
            $connection->close();
        }
    }

    private function waitForMessage(AMQPChannel $channel, string $queue, int $timeoutMs): ?AMQPMessage
    {
        $deadline = microtime(true) + ($timeoutMs / 1000);

        while (microtime(true) < $deadline) {
            $message = $channel->basic_get($queue);

            if ($message !== null) {
                return $message;
            }

            usleep(50_000);
        }

        return null;
    }

    private function purgeMongo(): void
    {
        (new MongoClient(self::mongoUri()))->selectCollection(self::mongoDatabase(), 'notifications')->deleteMany([]);
    }

    private function purgeRabbitMq(): void
    {
        try {
            $connection = $this->connections->create();
            $channel = $connection->channel();

            foreach ([
                $this->rabbitConfig->queue,
                $this->rabbitConfig->dlqQueue,
                $this->rabbitConfig->retryQueue(1),
                $this->rabbitConfig->retryQueue(2),
            ] as $queue) {
                try {
                    $channel->queue_purge($queue);
                } catch (Throwable) {
                    // Очередь могла быть ещё не объявлена в этом прогоне — не страшно
                }
            }

            $channel->close();
            $connection->close();
        } catch (Throwable) {
            // Брокер недоступен на teardown — не критично, следующий прогон почистит сам
        }
    }
}
