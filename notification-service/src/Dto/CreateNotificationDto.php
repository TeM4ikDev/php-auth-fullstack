<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\NotificationChannel;

final class CreateNotificationDto
{
    public function __construct(
        public readonly string $event,
        public readonly NotificationChannel $channel,
        public readonly string $recipient,
        public readonly array $payload,
    ) {
    }
}
