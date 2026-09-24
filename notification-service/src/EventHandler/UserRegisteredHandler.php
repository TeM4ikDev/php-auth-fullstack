<?php

declare(strict_types=1);

namespace App\EventHandler;

use App\Dto\CreateNotificationDto;
use App\Enum\NotificationChannel;
use App\Service\NotificationService;

final class UserRegisteredHandler implements EventHandlerInterface
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(array $payload): void
    {
        $this->notifications->recordAndSend(new CreateNotificationDto(
            'user.registered',
            NotificationChannel::Email,
            (string) $payload['email'],
            $payload,
        ));
    }
}
