<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateNotificationDto;
use App\Dto\RetryMessageDto;
use App\Messaging\RetryPublisherInterface;
use App\Repository\NotificationRepositoryInterface;
use Throwable;

/** Оркестратор отправки+ретраев: единственное место, которое решает markSent/markRetrying/markFailed. */
final class NotificationProcessor implements NotificationProcessorInterface
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notifications,
        private readonly NotificationSenderInterface $sender,
        private readonly RetryPolicy $retryPolicy,
        private readonly RetryPublisherInterface $retryPublisher,
    ) {
    }

    public function process(CreateNotificationDto $dto, int $attempt, ?string $notificationId): void
    {
        $notification = $notificationId !== null ? $this->notifications->findById($notificationId) : null;
        $notification ??= $this->notifications->create($dto);

        try {
            $this->sender->send($dto);
            $this->notifications->markSent($notification->id);
        } catch (Throwable $e) {
            $this->handleFailure($notification->id, $dto, $attempt, $e);
        }
    }

    private function handleFailure(string $id, CreateNotificationDto $dto, int $attempt, Throwable $e): void
    {
        error_log("Notification [{$id}] attempt {$attempt} failed: " . $e->getMessage());

        $message = new RetryMessageDto($id, $dto->event, $dto->payload, $attempt);

        if ($this->retryPolicy->isExhausted($attempt)) {
            $this->notifications->markFailed($id, $e->getMessage(), deadLettered: true);
            $this->retryPublisher->sendToDlq($message);

            return;
        }

        $this->notifications->markRetrying($id, $e->getMessage());
        $this->retryPublisher->scheduleRetry($message);
    }
}
