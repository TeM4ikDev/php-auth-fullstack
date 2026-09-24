<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\NotificationChannel;
use App\Enum\NotificationStatus;

final class NotificationDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $event,
        public readonly NotificationChannel $channel,
        public readonly string $recipient,
        public readonly array $payload,
        public readonly NotificationStatus $status,
        public readonly int $attempts,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $sentAt,
    ) {
    }
}
