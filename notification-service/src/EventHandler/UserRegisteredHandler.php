<?php

declare(strict_types=1);

namespace App\EventHandler;

use App\Dto\CreateNotificationDto;
use App\Enum\NotificationChannel;
use App\Service\NotificationProcessorInterface;

final class UserRegisteredHandler implements EventHandlerInterface
{
    public function __construct(private readonly NotificationProcessorInterface $processor)
    {
    }

    public function handle(array $payload, int $attempt, ?string $notificationId): void
    {
        $this->processor->process(
            new CreateNotificationDto('user.registered', NotificationChannel::Email, (string) $payload['email'], $payload),
            $attempt,
            $notificationId,
        );
    }
}
