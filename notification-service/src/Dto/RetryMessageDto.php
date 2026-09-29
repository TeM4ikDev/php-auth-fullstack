<?php

declare(strict_types=1);

namespace App\Dto;

final class RetryMessageDto
{
    public function __construct(
        public readonly string $notificationId,
        public readonly string $event,
        public readonly array $payload,
        public readonly int $failedAttempt,
    ) {
    }
}
