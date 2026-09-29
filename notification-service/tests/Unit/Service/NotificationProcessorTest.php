<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Config\RetryConfig;
use App\Dto\CreateNotificationDto;
use App\Dto\NotificationDto;
use App\Dto\RetryMessageDto;
use App\Enum\NotificationChannel;
use App\Enum\NotificationStatus;
use App\Messaging\RetryPublisherInterface;
use App\Repository\NotificationRepositoryInterface;
use App\Service\NotificationProcessor;
use App\Service\NotificationSenderInterface;
use App\Service\RetryPolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class NotificationProcessorTest extends TestCase
{
    #[Test]
    public function a_successful_send_marks_the_notification_sent_and_schedules_nothing(): void
    {
        $notification = $this->notification();

        $notifications = $this->createMock(NotificationRepositoryInterface::class);
        $notifications->method('create')->willReturn($notification);
        $notifications->expects(self::once())->method('markSent')->with($notification->id)->willReturn($notification);
        $notifications->expects(self::never())->method('markRetrying');
        $notifications->expects(self::never())->method('markFailed');

        $sender = $this->createStub(NotificationSenderInterface::class);
        $sender->method('send');

        $retryPublisher = $this->createMock(RetryPublisherInterface::class);
        $retryPublisher->expects(self::never())->method('scheduleRetry');
        $retryPublisher->expects(self::never())->method('sendToDlq');

        $processor = new NotificationProcessor($notifications, $sender, $this->retryPolicy(), $retryPublisher);

        $processor->process($this->dto(), 1, null);
    }

    #[Test]
    public function a_failed_send_schedules_a_retry_when_attempts_remain(): void
    {
        $notification = $this->notification();

        $notifications = $this->createMock(NotificationRepositoryInterface::class);
        $notifications->method('create')->willReturn($notification);
        $notifications->expects(self::once())->method('markRetrying')->with($notification->id, self::isString())->willReturn($notification);
        $notifications->expects(self::never())->method('markFailed');

        $sender = $this->createStub(NotificationSenderInterface::class);
        $sender->method('send')->willThrowException(new RuntimeException('smtp down'));

        $retryPublisher = $this->createMock(RetryPublisherInterface::class);
        $retryPublisher->expects(self::once())
            ->method('scheduleRetry')
            ->with(self::callback(
                static fn (RetryMessageDto $m): bool => $m->notificationId === $notification->id && $m->failedAttempt === 1,
            ));
        $retryPublisher->expects(self::never())->method('sendToDlq');

        $processor = new NotificationProcessor($notifications, $sender, $this->retryPolicy(), $retryPublisher);

        $processor->process($this->dto(), 1, null);
    }

    #[Test]
    public function a_failed_send_goes_to_the_dlq_once_attempts_are_exhausted(): void
    {
        $notification = $this->notification();

        $notifications = $this->createMock(NotificationRepositoryInterface::class);
        $notifications->method('findById')->willReturn($notification);
        $notifications->expects(self::once())->method('markFailed')->with($notification->id, self::isString(), true)->willReturn($notification);
        $notifications->expects(self::never())->method('markRetrying');
        $notifications->expects(self::never())->method('create');

        $sender = $this->createStub(NotificationSenderInterface::class);
        $sender->method('send')->willThrowException(new RuntimeException('smtp down'));

        $retryPublisher = $this->createMock(RetryPublisherInterface::class);
        $retryPublisher->expects(self::once())
            ->method('sendToDlq')
            ->with(self::callback(
                static fn (RetryMessageDto $m): bool => $m->notificationId === $notification->id && $m->failedAttempt === 4,
            ));
        $retryPublisher->expects(self::never())->method('scheduleRetry');

        $processor = new NotificationProcessor($notifications, $sender, $this->retryPolicy(), $retryPublisher);

        $processor->process($this->dto(), 4, $notification->id);
    }

    #[Test]
    public function a_retried_message_reuses_the_existing_document_instead_of_creating_a_new_one(): void
    {
        $notification = $this->notification();

        $notifications = $this->createMock(NotificationRepositoryInterface::class);
        $notifications->expects(self::once())->method('findById')->with($notification->id)->willReturn($notification);
        $notifications->expects(self::never())->method('create');
        $notifications->method('markSent')->willReturn($notification);

        $sender = $this->createStub(NotificationSenderInterface::class);
        $sender->method('send');

        $processor = new NotificationProcessor(
            $notifications,
            $sender,
            $this->retryPolicy(),
            $this->createStub(RetryPublisherInterface::class),
        );

        $processor->process($this->dto(), 2, $notification->id);
    }

    private function retryPolicy(): RetryPolicy
    {
        return new RetryPolicy(new RetryConfig(3, [5_000, 25_000, 125_000]));
    }

    private function dto(): CreateNotificationDto
    {
        return new CreateNotificationDto(
            'user.registered',
            NotificationChannel::Email,
            'artem@example.com',
            ['email' => 'artem@example.com', 'name' => 'Artem'],
        );
    }

    private function notification(): NotificationDto
    {
        return new NotificationDto(
            '507f1f77bcf86cd799439011',
            'user.registered',
            NotificationChannel::Email,
            'artem@example.com',
            ['email' => 'artem@example.com'],
            NotificationStatus::Pending,
            0,
            '2026-01-01T00:00:00+00:00',
            '2026-01-01T00:00:00+00:00',
            null,
            null,
            false,
        );
    }
}
