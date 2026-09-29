<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\MongoConfig;
use App\Dto\CreateNotificationDto;
use App\Enum\NotificationChannel;
use App\Enum\NotificationStatus;
use App\Repository\MongoNotificationRepository;
use MongoDB\Client as MongoClient;
use PHPUnit\Framework\Attributes\Test;

final class MongoNotificationRepositoryTest extends IntegrationTestCase
{
    private MongoNotificationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        [$host, $port] = self::parseHostPort(self::mongoUri(), 27017);
        self::skipUnlessReachable($host, $port, 'MongoDB');

        $this->repository = new MongoNotificationRepository(new MongoConfig(self::mongoUri(), self::mongoDatabase()));
        $this->purgeCollection();
    }

    protected function tearDown(): void
    {
        $this->purgeCollection();

        parent::tearDown();
    }

    #[Test]
    public function it_creates_and_finds_a_notification(): void
    {
        $created = $this->repository->create($this->dto());
        $found = $this->repository->findById($created->id);

        self::assertNotNull($found);
        self::assertSame('user.registered', $found->event);
        self::assertSame(NotificationStatus::Pending, $found->status);
        self::assertSame(0, $found->attempts);
        self::assertNull($found->lastError);
        self::assertFalse($found->deadLettered);
    }

    #[Test]
    public function marking_sent_increments_attempts_and_sets_sent_at(): void
    {
        $created = $this->repository->create($this->dto());

        $sent = $this->repository->markSent($created->id);

        self::assertSame(NotificationStatus::Sent, $sent->status);
        self::assertSame(1, $sent->attempts);
        self::assertNotNull($sent->sentAt);
    }

    #[Test]
    public function marking_retrying_records_the_error_and_increments_attempts(): void
    {
        $created = $this->repository->create($this->dto());

        $retrying = $this->repository->markRetrying($created->id, 'smtp timeout');

        self::assertSame(NotificationStatus::Retrying, $retrying->status);
        self::assertSame('smtp timeout', $retrying->lastError);
        self::assertSame(1, $retrying->attempts);
    }

    #[Test]
    public function marking_failed_sets_the_dead_lettered_flag(): void
    {
        $created = $this->repository->create($this->dto());

        $failed = $this->repository->markFailed($created->id, 'smtp down', true);

        self::assertSame(NotificationStatus::Failed, $failed->status);
        self::assertTrue($failed->deadLettered);
        self::assertSame('smtp down', $failed->lastError);
    }

    #[Test]
    public function reset_for_replay_clears_attempts_and_error(): void
    {
        $created = $this->repository->create($this->dto());
        $this->repository->markFailed($created->id, 'smtp down', true);

        $reset = $this->repository->resetForReplay($created->id);

        self::assertSame(NotificationStatus::Pending, $reset->status);
        self::assertSame(0, $reset->attempts);
        self::assertNull($reset->lastError);
        self::assertFalse($reset->deadLettered);
    }

    #[Test]
    public function find_all_filters_by_status_and_count_all_ignores_the_filter(): void
    {
        $sent = $this->repository->create($this->dto());
        $this->repository->create($this->dto());
        $this->repository->markSent($sent->id);

        $sentOnly = $this->repository->findAll(10, 0, ['status' => 'sent']);

        self::assertCount(1, $sentOnly);
        self::assertSame($sent->id, $sentOnly[0]->id);
        self::assertSame(2, $this->repository->countAll([]));
    }

    private function dto(): CreateNotificationDto
    {
        return new CreateNotificationDto(
            'user.registered',
            NotificationChannel::Email,
            'artem@example.com',
            ['email' => 'artem@example.com'],
        );
    }

    private function purgeCollection(): void
    {
        (new MongoClient(self::mongoUri()))->selectCollection(self::mongoDatabase(), 'notifications')->deleteMany([]);
    }
}
