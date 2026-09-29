<?php

declare(strict_types=1);

namespace Tests\Unit\Resource;

use App\Dto\NotificationDto;
use App\Dto\NotificationListDto;
use App\Enum\NotificationChannel;
use App\Enum\NotificationStatus;
use App\Resource\NotificationResource;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NotificationResourceTest extends TestCase
{
    #[Test]
    public function it_exposes_the_retry_related_fields(): void
    {
        $notification = new NotificationDto(
            '507f1f77bcf86cd799439011',
            'user.registered',
            NotificationChannel::Email,
            'test@example.com',
            ['email' => 'test@example.com'],
            NotificationStatus::Failed,
            4,
            '2026-01-01T00:00:00+00:00',
            '2026-01-01T00:05:00+00:00',
            null,
            'SMTP connection failed',
            true,
        );

        $resource = NotificationResource::fromDto($notification);

        self::assertSame('failed', $resource['status']);
        self::assertSame(4, $resource['attempts']);
        self::assertSame('SMTP connection failed', $resource['lastError']);
        self::assertTrue($resource['deadLettered']);
    }

    #[Test]
    public function collection_wraps_items_with_the_total_count(): void
    {
        $notification = new NotificationDto(
            '507f1f77bcf86cd799439011',
            'user.registered',
            NotificationChannel::Email,
            'artem@example.com',
            [],
            NotificationStatus::Sent,
            1,
            '2026-01-01T00:00:00+00:00',
            '2026-01-01T00:00:00+00:00',
            '2026-01-01T00:00:00+00:00',
            null,
            false,
        );

        $collection = NotificationResource::collection(new NotificationListDto([$notification], 1));

        self::assertSame(1, $collection['total']);
        self::assertCount(1, $collection['data']);
        self::assertSame('sent', $collection['data'][0]['status']);
    }
}
