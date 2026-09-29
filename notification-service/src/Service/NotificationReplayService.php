<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\NotificationDto;
use App\Enum\NotificationStatus;
use App\Http\Exception\NotFoundException;
use App\Messaging\RetryPublisherInterface;
use App\Repository\NotificationRepositoryInterface;
use App\Service\Exception\NotReplayableException;

final class NotificationReplayService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notifications,
        private readonly RetryPublisherInterface $retryPublisher,
    ) {
    }

    public function replay(string $id): NotificationDto
    {
        $notification = $this->notifications->findById($id);

        if ($notification === null) {
            throw new NotFoundException(sprintf('Notification [%s] not found.', $id));
        }

        if ($notification->status !== NotificationStatus::Failed) {
            throw new NotReplayableException();
        }

        $reset = $this->notifications->resetForReplay($id);

        $this->retryPublisher->republish($id, $reset->event, $reset->payload);

        return $reset;
    }
}
