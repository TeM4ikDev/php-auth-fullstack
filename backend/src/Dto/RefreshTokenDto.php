<?php

declare(strict_types=1);

namespace App\Dto;

final class RefreshTokenDto
{
    public function __construct(
        public readonly int $userId,
        public readonly string $sessionId,
    ) {
    }
}
