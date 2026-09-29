<?php

declare(strict_types=1);

namespace App\Dto;

final class SessionDto
{
    public function __construct(
        public readonly string $sessionId,
        public readonly int $userId,
        public readonly string $createdAt,
    ) {
    }
}
